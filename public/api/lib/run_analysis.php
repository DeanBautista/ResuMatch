<?php
/**
 * api/lib/run_analysis.php
 *
 * Shared core of the resume/JD analysis flow, extracted out of analyze.php
 * so that analyze.php (fresh submissions) and rerun.php (re-running the
 * last analysis from session) call the exact same Groq/Gemini logic
 * instead of two copies drifting apart.
 *
 * Requires vendor/autoload.php + .env already loaded by the caller.
 *
 * @param string $resumeText
 * @param string $jobDescription
 * @param string $jobTitle
 * @param string $company
 * @return array{
 *   ok: bool,
 *   httpStatus: int,
 *   body: array   // the exact array to json_encode() and echo
 * }
 */
function runResumeAnalysis(string $resumeText, string $jobDescription, string $jobTitle, string $company): array
{
    // --- API keys ---
    $geminiKey       = $_ENV['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY');
    $groqKeyPrimary  = $_ENV['GROQ_API_KEY_PRIMARY']   ?? getenv('GROQ_API_KEY_PRIMARY');
    $groqKeySecondary = $_ENV['GROQ_API_KEY_SECONDARY'] ?? getenv('GROQ_API_KEY_SECONDARY');

    if (!$geminiKey && !$groqKeyPrimary && !$groqKeySecondary) {
        return [
            'ok' => false,
            'httpStatus' => 500,
            'body' => ['ok' => false, 'error' => 'Server misconfigured: no API keys set (GEMINI_API_KEY / GROQ_API_KEY_PRIMARY / GROQ_API_KEY_SECONDARY).'],
        ];
    }

    // Trim so we can reliably tell "not provided" apart from a real value
    // when deciding whether to trust the model's own extraction below.
    $jobTitle = trim($jobTitle);
    $company  = trim($company);

    $jobTitleLine = $jobTitle !== '' ? $jobTitle : '(not provided — extract from job description if possible)';
    $companyLine  = $company  !== '' ? $company  : '(not provided — extract from job description if possible)';

    $prompt = <<<PROMPT
You are an expert ATS (Applicant Tracking System) analyst and technical
recruiter with 15 years of experience screening resumes against job
descriptions. Analyze the RESUME against the JOB DESCRIPTION below with the
same rigor a hiring manager would use, but grounded strictly in what is
textually present in each document — never invent or infer facts that are
not stated.

============================================================
STEP 0 — INPUT VALIDITY GATE (check this BEFORE doing any scoring)
============================================================
Before applying the rubric below, check whether RESUME and JOB DESCRIPTION
are actually a resume and a job description. Reasons input can be invalid:
placeholder/test text (e.g. "sa", "test", "asdf"), text that is far too
short or fragmentary to contain real qualifications or job requirements,
random/gibberish text, or content that is clearly something else entirely
(e.g. a recipe, a poem, an unrelated article).

If EITHER field is invalid by these criteria:
  - Set "isValidInput" to false.
  - Set "invalidInputReason" to a short plain-language explanation of what's
    wrong (e.g. "The resume field only contains a couple of characters and
    has no identifiable work history, skills, or education.").
  - Set matchScore to 0, verdict to "Poor Match", and every array field
    ([...]) to an empty array. Do NOT invent scores, skills, strengths,
    gaps, or recommendations for invalid input — leave subScores at 0 and
    string fields (other than invalidInputReason) as null.
  - Set "extractedJobTitle" and "extractedCompany" to null as well.
  - Still output the full JSON shape below so the response is well-formed,
    just with these placeholder/zeroed values.

If BOTH fields are valid, real resume/JD content, set "isValidInput" to
true, "invalidInputReason" to null, and proceed with the full rubric and
ground rules below as normal.

============================================================
JOB TITLE / COMPANY EXTRACTION
============================================================
The caller may or may not supply a job title and company name explicitly
(see JOB TITLE / COMPANY below). Regardless of what is supplied:
  - Set "extractedJobTitle" to the job title as stated in the JOB
    DESCRIPTION text (e.g. from a heading, "Job Title:" line, or the first
    sentence). If JOB TITLE was already provided to you (not the
    placeholder "(not provided...)"), you may use it to confirm, but still
    report what the JD itself says — they should usually match. If the JD
    does not state a job title anywhere, set this to null.
  - Set "extractedCompany" to the company/organization name as stated in
    the JOB DESCRIPTION text (e.g. "About Us", "About [Company]", a
    letterhead-style mention, or "at Acme Corp we..."). If COMPANY was
    already provided to you, you may use it to confirm, but still report
    what the JD itself says. If the JD does not name a company anywhere
    (e.g. it's an anonymized posting, or just says "our company"), set
    this to null — do not guess or invent a name.
  - These two fields are extractions from the JD text only. Do not pull a
    company or title from the RESUME.

============================================================
SCORING RUBRIC (use this to compute matchScore and subScores — only if isValidInput is true)
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
  "isValidInput": <boolean — false if RESUME or JOB DESCRIPTION is not real, substantive content>,
  "invalidInputReason": "<string explaining what's wrong, or null if isValidInput is true>",

  "extractedJobTitle": "<job title as found in the JOB DESCRIPTION text, or null if not stated>",
  "extractedCompany": "<company name as found in the JOB DESCRIPTION text, or null if not stated>",

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
JOB TITLE (if provided): {$jobTitleLine}
COMPANY (if provided): {$companyLine}

RESUME:
{$resumeText}

JOB DESCRIPTION:
{$jobDescription}
PROMPT;

    // --- Try Groq first, fall back to Gemini on rate limit / failure ---
    $provider = null;
    $result   = null;

    if ($groqKeyPrimary) {
        $result = callGroq($groqKeyPrimary, $prompt);
        if ($result['rawText'] !== null) {
            $provider = 'groq-primary';
        } else {
            error_log('[run_analysis] Groq primary failed (HTTP ' . $result['httpCode'] . '): ' . $result['error'] . ' — trying Groq secondary if configured.');
        }
    }

    if ($provider === null && $groqKeySecondary) {
        $result = callGroq($groqKeySecondary, $prompt);
        if ($result['rawText'] !== null) {
            $provider = 'groq-secondary';
        } else {
            error_log('[run_analysis] Groq secondary failed (HTTP ' . $result['httpCode'] . '): ' . $result['error'] . ' — falling back to Gemini if configured.');
        }
    }

    if ($provider === null && $geminiKey) {
        error_log('[AI Router] Switching AI agent: Groq -> Gemini');
        $result = callGemini($geminiKey, $prompt);
        if ($result['rawText'] !== null) {
            $provider = 'gemini';
        }
    }

    // All providers failed (or none configured)
    if ($provider === null) {
        $httpCode = $result['httpCode'] ?? 502;
        $errorMsg = $result['error'] ?? 'All configured providers (Groq primary, Groq secondary, Gemini) failed.';

        if ($httpCode === 429) {
            return [
                'ok' => false,
                'httpStatus' => 429,
                'body' => [
                    'ok' => false,
                    'error' => 'The analysis service is busy right now (rate limit on all providers). Please wait a minute and try again.',
                    'retryable' => true,
                ],
            ];
        }

        return [
            'ok' => false,
            'httpStatus' => 502,
            'body' => ['ok' => false, 'error' => $errorMsg],
        ];
    }

     // Log which provider/model actually served this request — useful for
    // debugging fallback behavior and for confirming which AI is live.
    $modelUsed = match ($provider) {
        'groq-primary', 'groq-secondary' => 'llama-3.3-70b-versatile',
        'gemini' => 'gemini-3.5-flash',
        default => 'unknown',
    };
    error_log("[run_analysis] Request served by provider={$provider} model={$modelUsed}");
    
    $rawText = $result['rawText'];

    // Strip accidental ```json fences just in case a model adds them despite
    // being told not to — cheap safety net either provider might need.
    $cleaned = preg_replace('/^```json\s*|\s*```$/m', '', trim($rawText));
    $parsed = json_decode($cleaned, true);

    if ($parsed === null && json_last_error() !== JSON_ERROR_NONE) {
        return [
            'ok' => true,
            'httpStatus' => 200,
            'body' => [
                'ok' => true,
                'provider' => $provider,
                'raw' => $rawText,
                'parsed' => null,
                'parseError' => 'Model response was not valid JSON: ' . json_last_error_msg(),
            ],
        ];
    }

    if (is_array($parsed)) {
        // Prefer the caller-supplied job title/company when present (the
        // person typed them in explicitly); otherwise fall back to what
        // the model extracted from the JD text itself.
        $extractedJobTitle = $parsed['extractedJobTitle'] ?? null;
        $extractedCompany  = $parsed['extractedCompany']  ?? null;

        $parsed['jobTitle'] = $jobTitle !== '' ? $jobTitle : $extractedJobTitle;
        $parsed['company']  = $company  !== '' ? $company  : $extractedCompany;

        // Keep the raw extraction around too, in case the UI wants to show
        // "detected from job description" separately from the final value.
        $parsed['extractedJobTitle'] = $extractedJobTitle;
        $parsed['extractedCompany']  = $extractedCompany;
    }

    // If the model itself determined the resume/JD wasn't real, substantive
    // content, surface it as a distinct, actionable error instead of
    // storing/rendering it as a normal 0%/Poor Match result.
    if (is_array($parsed) && ($parsed['isValidInput'] ?? true) === false) {
        $reason = $parsed['invalidInputReason'] ?? 'The resume or job description text did not look complete or valid.';
        return [
            'ok' => false,
            'httpStatus' => 422,
            'body' => [
                'ok' => false,
                'error' => $reason,
                'invalidInput' => true,
            ],
        ];
    }

    // Persist both the parsed result AND the inputs that produced it, so
    // "Re-run check" on the results page can replay this exact analysis.
    $_SESSION['last_analysis']       = $parsed;
    $_SESSION['last_analysis_at']    = time();
    $_SESSION['last_analysis_input'] = [
        'resumeText'     => $resumeText,
        'jobDescription' => $jobDescription,
        'jobTitle'       => $parsed['jobTitle'] ?? $jobTitle,
        'company'        => $parsed['company'] ?? $company,
    ];

    return [
        'ok' => true,
        'httpStatus' => 200,
        'body' => [
            'ok' => true,
            'provider' => $provider,
            'raw' => $rawText,
            'parsed' => $parsed,
        ],
    ];
}