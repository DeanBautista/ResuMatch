<?php
session_start();
/**
 * /api/analyze.php
 *
 * Minimal backend endpoint that receives extracted resume text +
 * job description from the client and forwards them to an LLM.
 *
 * Primary provider: Groq (llama-3.3-70b-versatile).
 * Fallback provider: Gemini (gemini-3.5-flash, pinned — not the
 * "-latest" alias, so it can't silently repoint under us), used
 * automatically if Groq returns a 429 (rate limit) or the request
 * otherwise fails.
 *
 * Both API keys stay server-side only.
 *
 * Expects JSON POST body:
 *   { "resumeText": "...", "jobDescription": "...", "jobTitle": "...", "company": "..." }
 *
 * Returns JSON:
 *   { "ok": true, "provider": "gemini"|"groq", "raw": "...", "parsed": {...} }
 *   { "ok": false, "error": "..." }
 */
require __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

header('Content-Type: application/json');

// --- CORS (same-origin by default; loosen only if you actually need it) ---
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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

error_log("Job Description: " . $jobDescription);

// Basic size guard so we don't blow past either provider's context/quota,
// or rack up cost on a bad request (e.g. someone accidentally sending a
// huge file). Lowered from 50,000 -> 10,000: real resumes/JDs are almost
// always well under this, and it keeps a single request comfortably
// inside Groq's tight 6,000 TPM free-tier budget when fallback fires.
const MAX_CHARS = 10000;
if (strlen($resumeText) > MAX_CHARS || strlen($jobDescription) > MAX_CHARS) {
    http_response_code(413);
    echo json_encode(['ok' => false, 'error' => 'Input too large. Please keep resume and job description under ' . MAX_CHARS . ' characters each.']);
    exit;
}

// --- API keys ---
$geminiKey = $_ENV['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY');
$groqKey   = $_ENV['GROQ_API_KEY']   ?? getenv('GROQ_API_KEY');

if (!$geminiKey && !$groqKey) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server misconfigured: no API keys set (GEMINI_API_KEY / GROQ_API_KEY).']);
    exit;
}

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
9. Formatting analysis: scan the RESUME text itself (not the JD) for signals
   that could hurt ATS parsing — e.g. evidence of multi-column layout,
   tables, headers/footers, images/icons standing in for text, unusual
   section headers, missing contact info (no email or phone number
   detected in the text), or excessive use of special characters/symbols.
   Only flag what's actually inferable from the extracted text — e.g. if
   text appears jumbled or out of logical order, that's evidence of a
   multi-column layout; don't assume issues that plain extracted text
   can't reveal.

============================================================
OUTPUT JSON SHAPE (produce exactly this structure)
============================================================
{
  "matchScore": <integer 0-100>,
  "verdict": "<Strong Match|Moderate Match|Weak Match|Poor Match>",
  "summary": "<2-3 sentence plain-language take on overall fit>",

  "subScores": {
    "skills": <integer 0-100>,
    "experience": <integer 0-100>,
    "education": <integer 0-100>,
    "keywords": <integer 0-100>,
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
  "gaps": [<string>, ...],

  "recommendations": [
    { "action": "<specific tactical instruction>", "section": "<resume section it applies to>" }
  ],

  "formattingIssues": [
    { "message": "<specific, plain-language description of the issue>", "severity": "<warning|info>" }
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
/**
 * Calls Gemini's generateContent endpoint.
 * Returns ['httpCode' => int, 'rawText' => string|null, 'error' => string|null]
 */
function callGemini(string $apiKey, string $prompt): array
{
    $model = 'gemini-3.5-flash'; // pinned explicitly — not the '-latest' alias,
                                  // so this can't silently repoint to a different
                                  // model (and a different quota tier) later.

    $payload = [
        'contents' => [
            ['parts' => [['text' => $prompt]]],
        ],
        'generationConfig' => [
            'response_mime_type' => 'application/json',
            'temperature' => 0.2,
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
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20, // was 30 — Gemini can genuinely take longer than
                                // that on large structured-JSON responses.
    ]);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);
    curl_close($ch);

    if ($curlErrno === CURLE_OPERATION_TIMEDOUT) {
    return [
        'httpCode' => 408,
        'rawText'  => null,
        'error'    => 'Gemini request timed out.'
    ];
}

    if ($curlError) {
        return ['httpCode' => 0, 'rawText' => null, 'error' => "Upstream request failed: {$curlError}"];
    }

    $data = json_decode($response, true);

    if ($httpCode !== 200) {
        $msg = $data['error']['message'] ?? "Gemini API returned HTTP {$httpCode}";
        return ['httpCode' => $httpCode, 'rawText' => null, 'error' => $msg];
    }

    $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

    if ($rawText === null) {
        return ['httpCode' => $httpCode, 'rawText' => null, 'error' => 'Unexpected Gemini response shape.'];
    }

    return ['httpCode' => $httpCode, 'rawText' => $rawText, 'error' => null];
}

/**
 * Calls Groq's OpenAI-compatible chat completions endpoint.
 * Same prompt, same expected JSON-only output — the model is just asked
 * explicitly (via a system message) to return raw JSON, since Groq's
 * OpenAI-compatible API handles JSON mode slightly differently than Gemini.
 * Returns ['httpCode' => int, 'rawText' => string|null, 'error' => string|null]
 */
function callGroq(string $apiKey, string $prompt): array
{
    $model = 'llama-3.3-70b-versatile'; // pinned, open-source, solid at structured JSON tasks

    $payload = [
        'model' => $model,
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You output ONLY valid JSON. No markdown fences, no commentary, no text before or after the JSON object.',
            ],
            [
                'role' => 'user',
                'content' => $prompt,
            ],
        ],
        'temperature' => 0.2,
        'response_format' => ['type' => 'json_object'],
    ];

    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
    ]);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);
    curl_close($ch);

    if ($curlErrno === CURLE_OPERATION_TIMEDOUT) {
        return [
            'httpCode' => 408,
            'rawText'  => null,
            'error'    => 'Groq request timed out.'
        ];
    }

    if ($curlError) {
        return ['httpCode' => 0, 'rawText' => null, 'error' => "Upstream request failed: {$curlError}"];
    }

    $data = json_decode($response, true);

    if ($httpCode !== 200) {
        $msg = $data['error']['message'] ?? "Groq API returned HTTP {$httpCode}";
        return ['httpCode' => $httpCode, 'rawText' => null, 'error' => $msg];
    }

    $rawText = $data['choices'][0]['message']['content'] ?? null;

    if ($rawText === null) {
        return ['httpCode' => $httpCode, 'rawText' => null, 'error' => 'Unexpected Groq response shape.'];
    }

    return ['httpCode' => $httpCode, 'rawText' => $rawText, 'error' => null];
}

// --- Try Groq first, fall back to Gemini on rate limit / failure ---

$provider = null;
$result   = null;

if ($groqKey) {
    $result = callGroq($groqKey, $prompt);

    if ($result['rawText'] !== null) {
        $provider = 'groq';
    } else {
        // Groq failed. If it was a rate limit (429) or any other failure,
        // and we have a Gemini key configured, fall back automatically.
        error_log('[analyze.php] Groq failed (HTTP ' . $result['httpCode'] . '): ' . $result['error'] . ' — falling back to Gemini if configured.');
    }
}

if ($provider === null && $geminiKey) {
    error_log('[AI Router] Switching AI agent: Groq -> Gemini');
    $result = callGemini($geminiKey, $prompt);
    if ($result['rawText'] !== null) {
        $provider = 'gemini';
    }
}

// Both providers failed (or only one was configured and it failed)
if ($provider === null) {
    $httpCode = $result['httpCode'] ?? 502;
    $errorMsg = $result['error'] ?? 'Both Groq and Gemini failed or are unconfigured.';

    // Surface rate-limit errors distinctly so the frontend can show a
    // friendlier "please wait a moment" message instead of a raw dump.
    if ($httpCode === 429) {
        http_response_code(429);
        echo json_encode([
            'ok' => false,
            'error' => 'The analysis service is busy right now (rate limit on both providers). Please wait a minute and try again.',
            'retryable' => true,
        ]);
        exit;
    }

    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => $errorMsg]);
    exit;
}

$rawText = $result['rawText'];

// Try to parse the model's JSON reply so the client gets structured data.
// Strip accidental ```json fences just in case a model adds them despite
// being told not to — cheap safety net either provider might need.
$cleaned = preg_replace('/^```json\s*|\s*```$/m', '', trim($rawText));
$parsed = json_decode($cleaned, true);

if ($parsed === null && json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'ok' => true,
        'provider' => $provider,
        'raw' => $rawText,
        'parsed' => null,
        'parseError' => 'Model response was not valid JSON: ' . json_last_error_msg(),
    ]);
    exit;
}

if (is_array($parsed)) {
    $parsed['jobTitle'] = $jobTitle;
    $parsed['company']  = $company;
}

$_SESSION['last_analysis']    = $parsed;
$_SESSION['last_analysis_at'] = time();

echo json_encode([
    'ok' => true,
    'provider' => $provider,
    'raw' => $rawText,
    'parsed' => $parsed,
]);