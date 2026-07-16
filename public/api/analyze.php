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
 *
 * NOTE: The actual Groq/Gemini calling logic + prompt live in
 * lib/providers.php and lib/run_analysis.php, shared with rerun.php
 * (the "Re-run check" button on the results page) so both endpoints
 * stay in sync instead of drifting apart as two copies.
 */
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/lib/providers.php';
require __DIR__ . '/lib/run_analysis.php';

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

// Minimum length guard: a real resume or job description is realistically
// at least a few short paragraphs. Without this, trivial input like "sa"
// or "test" still passes the empty-string check above and gets sent
// straight to the LLM, which then has nothing to actually analyze and
// will fabricate a full plausible-looking result (fake scores, fake
// skills, fake gaps) rather than erroring out, since it's instructed to
// always produce the full JSON shape. Reject too-short input here instead
// of relying on the model to notice.
const MIN_CHARS = 50;
if (strlen($resumeText) < MIN_CHARS || strlen($jobDescription) < MIN_CHARS) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'That doesn\'t look like a complete resume and job description. Please paste the full text of each (at least ' . MIN_CHARS . ' characters).',
    ]);
    exit;
}

// Basic size guard so we don't blow past either provider's context/quota,
// or rack up cost on a bad request (e.g. someone accidentally sending a
// huge file). Real resumes/JDs are almost always well under this, and it
// keeps a single request comfortably inside Groq's tight 6,000 TPM
// free-tier budget when fallback fires.
const MAX_CHARS = 10000;
if (strlen($resumeText) > MAX_CHARS || strlen($jobDescription) > MAX_CHARS) {
    http_response_code(413);
    echo json_encode(['ok' => false, 'error' => 'Input too large. Please keep resume and job description under ' . MAX_CHARS . ' characters each.']);
    exit;
}

$result = runResumeAnalysis($resumeText, $jobDescription, $jobTitle, $company);

http_response_code($result['httpStatus']);
echo json_encode($result['body']);