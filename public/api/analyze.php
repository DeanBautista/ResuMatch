<?php
/**
 * /api/analyze.php
 *
 * Minimal backend endpoint that receives extracted resume text +
 * job description from the client and forwards them to Gemini.
 * The Gemini API key stays server-side only.
 *
 * Expects JSON POST body:
 *   { "resumeText": "...", "jobDescription": "...", "jobTitle": "...", "company": "..." }
 *
 * Returns JSON:
 *   { "ok": true, "raw": "<gemini text response>", "parsed": {...} }
 *   { "ok": false, "error": "..." }
 */
require __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

header('Content-Type: application/json');

// --- CORS (same-origin by default; loosen only if you actually need it) ---
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$apiKey = $_ENV['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed. Use POST.']);
    exit;
}

// --- Read + validate input ---
$body = json_decode(file_get_contents('php://input'), true);

$resumeText     = trim($body['resumeText'] ?? '');
$jobDescription = trim($body['jobDescription'] ?? '');
$jobTitle       = trim($body['jobTitle'] ?? '');
$company        = trim($body['company'] ?? '');

if ($resumeText === '' || $jobDescription === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing resumeText or jobDescription.']);
    exit;
}

// Basic size guard so we don't blow past Gemini's context / rack up cost
// on a bad request (e.g. someone accidentally sending a huge file).
const MAX_CHARS = 50000;
if (strlen($resumeText) > MAX_CHARS || strlen($jobDescription) > MAX_CHARS) {
    http_response_code(413);
    echo json_encode(['ok' => false, 'error' => 'Input too large.']);
    exit;
}

// --- API key: already loaded from .env near the top of this file ---
if (!$apiKey) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server misconfigured: GEMINI_API_KEY not set.']);
    exit;
}

// --- Build the Gemini request ---
$model = 'gemini-flash-latest'; // auto-tracks Google's current recommended fast model

/**
 * The prompt below is intentionally long and structured. Two things matter
 * most for getting consistent, UI-ready output from an LLM doing this kind
 * of evaluative task:
 *
 * 1. A scoring RUBRIC — without one, the model's 0-100 score is basically
 *    vibes, and will drift wildly between calls on similar inputs. Giving it
 *    explicit sub-scores + weights makes the number defensible and gives you
 *    the "Score Breakdown Visualization" section for free.
 *
 * 2. Anti-hallucination rules — resume/JD matching is exactly the kind of
 *    task where a model will confidently invent a "3 years of React" if the
 *    resume merely mentions React once. We explicitly forbid inference of
 *    years of experience or degrees that aren't stated.
 */
$prompt = <<<PROMPT
You are an expert ATS (Applicant Tracking System) analyst and technical
recruiter with 15 years of experience screening resumes against job
descriptions. Analyze the RESUME against the JOB DESCRIPTION below with the
same rigor a hiring manager would use, but grounded strictly in what is
textually present in each document — never invent or infer facts that are
not stated.

============================================================
SCORING RUBRIC (use this to compute matchScore and subScores)
============================================================
Compute four sub-scores (0-100 each), then combine them into the overall
matchScore using these weights:
  - skillsMatch      (weight 40%): proportion of required + preferred skills
    from the JD that appear in the resume (required skills count more).
  - experienceMatch   (weight 25%): how well the candidate's years of
    experience and role history align with what the JD asks for.
  - keywordMatch      (weight 20%): proportion of important ATS keywords/
    phrases from the JD (tools, certifications, methodologies, domain terms)
    that appear in the resume, verbatim or as close synonyms.
  - educationMatch    (weight 15%): if the JD specifies a required or
    preferred education level/field, how well the resume's stated education
    satisfies it. If the JD does not mention education requirements at all,
    set educationMatch to 100 and educationApplicable to false.

matchScore = round(
  skillsMatch*0.40 + experienceMatch*0.25 + keywordMatch*0.20 + educationMatch*0.15
)

Map matchScore to a verdict:
  - 80-100 -> "Strong Match"
  - 60-79  -> "Moderate Match"
  - 40-59  -> "Weak Match"
  - 0-39   -> "Poor Match"

============================================================
STRICT GROUND RULES
============================================================
1. Only use information explicitly present in RESUME and JOB DESCRIPTION.
   Do not assume skills, tools, years of experience, or degrees that are not
   stated or clearly implied by dates/titles actually written in the resume.
2. For "years of experience": calculate detectedYears only from explicit
   dates or explicit duration statements in the resume (e.g. "2019-2023" or
   "5 years"). If dates are absent or ambiguous, set detectedYears to null
   and note the ambiguity in experienceNotes — do not guess.
3. Distinguish REQUIRED vs PREFERRED/NICE-TO-HAVE skills in the JD. Look for
   explicit language ("required", "must have", "minimum qualifications" vs
   "preferred", "nice to have", "bonus", "a plus"). If the JD does not make
   this distinction, treat all listed skills as required.
4. Keywords are not the same as skills: skills are competencies (e.g.
   "project management"); ATS keywords include specific tools, certs,
   acronyms, and repeated phrases the JD emphasizes (e.g. "PMP", "Agile",
   "Salesforce", "SOC 2"). A term can appear in both lists if relevant.
5. For each missing ATS keyword, note how many times the JD mentions it
   (frequency) so the client can flag emphasis (e.g. mentioned 4x in JD).
6. Recommendations must be specific and directly actionable — reference an
   exact keyword, skill, or resume section. Never output vague advice like
   "improve your resume" or "add more detail."
7. Strengths and gaps must be specific to THIS resume/JD pairing, not
   generic resume advice.
8. Output ONLY valid JSON. No markdown code fences, no commentary, no text
   before or after the JSON object.

============================================================
OUTPUT JSON SHAPE (produce exactly this structure)
============================================================
{
  "matchScore": <integer 0-100>,
  "verdict": "<Strong Match|Moderate Match|Weak Match|Poor Match>",
  "summary": "<2-3 sentence plain-language take on overall fit>",

  "subScores": {
    "skillsMatch": <integer 0-100>,
    "experienceMatch": <integer 0-100>,
    "keywordMatch": <integer 0-100>,
    "educationMatch": <integer 0-100>,
    "educationApplicable": <boolean>
  },

  "skills": {
    "matched": [<string>, ...],
    "missingRequired": [<string>, ...],
    "missingPreferred": [<string>, ...]
  },

  "atsKeywords": {
    "missing": [
      { "keyword": "<string>", "jdFrequency": <integer> }
    ],
    "underused": [
      { "keyword": "<string>", "resumeCount": <integer>, "jdFrequency": <integer> }
    ]
  },

  "experience": {
    "requiredYears": <number or null>,
    "detectedYears": <number or null>,
    "experienceNotes": "<string, e.g. explanation if years could not be determined>",
    "relevantHighlights": [<string>, ...],
    "gaps": [<string>, ...]
  },

  "education": {
    "required": "<string describing JD requirement, or null if none stated>",
    "detected": "<string describing what resume states, or null if none stated>",
    "meetsRequirement": <boolean or null>
  },

  "strengths": [<string>, ...],
  "weaknesses": [<string>, ...],

  "recommendations": [
    { "action": "<specific tactical instruction>", "section": "<resume section it applies to>" }
  ]
}

============================================================
JOB TITLE (if provided): {$jobTitle}
COMPANY (if provided): {$company}

RESUME:
{$resumeText}

JOB DESCRIPTION:
{$jobDescription}
PROMPT;

$payload = [
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt],
            ],
        ],
    ],
    // Ask Gemini to return JSON directly — this is more reliable than
    // relying on prompt instructions alone, and removes most need for the
    // fence-stripping fallback below.
    'generationConfig' => [
        'response_mime_type' => 'application/json',
        'temperature' => 0.2, // low temperature: consistent scoring, not creative writing
    ],
];

$url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'x-goog-api-key: ' . $apiKey,
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 30,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => "Upstream request failed: {$curlError}"]);
    exit;
}

$data = json_decode($response, true);

if ($httpCode !== 200) {
    $msg = $data['error']['message'] ?? "Gemini API returned HTTP {$httpCode}";
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

// Gemini's response text lives at candidates[0].content.parts[0].text
$rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

if ($rawText === null) {
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'Unexpected Gemini response shape.', 'debug' => $data]);
    exit;
}

// Try to parse the model's JSON reply so the client gets structured data.
// Strip accidental ```json fences just in case the model adds them
// (response_mime_type: application/json should prevent this, but this is
// a cheap safety net for cases where it's ignored).
$cleaned = preg_replace('/^```json\s*|\s*```$/m', '', trim($rawText));
$parsed = json_decode($cleaned, true);

// Defensive: if parsing failed, surface that clearly instead of silently
// returning null to the client (which would otherwise look like a bug in
// the frontend rather than an upstream formatting issue).
if ($parsed === null && json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'ok' => true,
        'raw' => $rawText,
        'parsed' => null,
        'parseError' => 'Gemini response was not valid JSON: ' . json_last_error_msg(),
    ]);
    exit;
}

echo json_encode([
    'ok' => true,
    'raw' => $rawText,
    'parsed' => $parsed,
]);