<?php
/**
 * /api/analyze.php
 *
 * Minimal backend endpoint that receives extracted resume text +
 * job description from the client and forwards them to Gemini.
 * The Gemini API key stays server-side only.
 *
 * Expects JSON POST body:
 *   { "resumeText": "...", "jobDescription": "..." }
 *
 * Returns JSON:
 *   { "ok": true, "raw": "<gemini text response>" }
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

$resumeText = trim($body['resumeText'] ?? '');
$jobDescription = trim($body['jobDescription'] ?? '');

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

$prompt = <<<PROMPT
You are an ATS resume-matching assistant. Compare the RESUME against the
JOB DESCRIPTION below and respond ONLY with valid JSON (no markdown fences,
no preamble) in this exact shape:

{
  "matchScore": <integer 0-100>,
  "matchingKeywords": [<string>, ...],
  "missingKeywords": [<string>, ...],
  "summary": "<2-3 sentence plain-language summary>"
}

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
// Strip accidental ```json fences just in case the model adds them.
$cleaned = preg_replace('/^```json\s*|\s*```$/m', '', trim($rawText));
$parsed = json_decode($cleaned, true);

echo json_encode([
    'ok' => true,
    'raw' => $rawText,
    'parsed' => $parsed, // null if it wasn't valid JSON — client can fall back to raw
]);