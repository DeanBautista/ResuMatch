<?php
/**
 * api/save-history.php
 *
 * "Save to History" endpoint — persists the most recent analysis result
 * (already sitting in $_SESSION['last_analysis'] / ['last_analysis_input'],
 * set by lib/run_analysis.php at the end of a successful analyze.php or
 * rerun.php call) into the match_history table for the logged-in user.
 *
 * Does NOT re-run analysis. Purely a session -> DB persistence step, per
 * the "just persist session data" decision — if there's nothing in
 * session (expired, or user never ran an analysis), this fails with a
 * clear 400 rather than silently doing nothing.
 *
 * Auth: requires $_SESSION['user_id'], set by api/auth/google.php on
 * login. Unauthenticated callers get 401.
 *
 * Expects: POST (no body required — everything needed is already in
 * session).
 *
 * Returns JSON:
 *   { "ok": true, "id": <int> }                         on success
 *   { "ok": false, "error": "..." }                       on failure
 *
 * NOTE ON WIRING THIS UP: this endpoint assumes $_SESSION['last_analysis']
 * and $_SESSION['last_analysis_input'] are already populated by the time
 * it's called, which happens automatically inside runResumeAnalysis()
 * whenever analyze.php or rerun.php succeeds. There's nothing extra to
 * set in results.php for THIS data to exist — it's already in session by
 * the time results.php renders. This endpoint just needs to be reachable
 * (e.g. placed at /api/save-history.php to match the existing /api/*
 * layout of analyze.php and auth/google.php) and the front-end button
 * needs to POST to it.
 */

session_start();

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/lib/db.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

header('Content-Type: application/json');

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

// --- Auth check ---
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'You must be signed in to save history.']);
    exit;
}

$userId = $_SESSION['user_id'];

// --- Must have a completed analysis sitting in session ---
$parsed = $_SESSION['last_analysis'] ?? null;
$inputs = $_SESSION['last_analysis_input'] ?? null;

if (!is_array($parsed) || !is_array($inputs)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'No analysis found to save. Please run an analysis first.']);
    exit;
}

/**
 * Small helper: json_encode(), but null stays null (not the string "null")
 * so JSON-typed columns can be genuinely NULL when the field wasn't set,
 * rather than storing the literal text "null".
 */
function toJsonColumn($value): ?string
{
    if ($value === null) {
        return null;
    }
    return json_encode($value);
}

try {
    $pdo = getPDO();

    $sql = "INSERT INTO match_history (
                user_id,
                job_title,
                company,
                resume_text,
                job_description,
                is_valid_input,
                invalid_input_reason,
                match_score,
                verdict,
                summary,
                skills_score,
                experience_score,
                education_score,
                keywords_score,
                skills_matched,
                skills_missing_required,
                skills_missing_preferred,
                ats_keywords_missing,
                ats_keywords_underused,
                required_years,
                detected_years,
                experience_notes,
                experience_highlights,
                experience_gaps,
                education_required,
                education_detected,
                education_meets_requirement,
                strengths,
                gaps,
                recommendations,
                formatting_issues,
                created_at,
                updated_at
            ) VALUES (
                :user_id,
                :job_title,
                :company,
                :resume_text,
                :job_description,
                :is_valid_input,
                :invalid_input_reason,
                :match_score,
                :verdict,
                :summary,
                :skills_score,
                :experience_score,
                :education_score,
                :keywords_score,
                :skills_matched,
                :skills_missing_required,
                :skills_missing_preferred,
                :ats_keywords_missing,
                :ats_keywords_underused,
                :required_years,
                :detected_years,
                :experience_notes,
                :experience_highlights,
                :experience_gaps,
                :education_required,
                :education_detected,
                :education_meets_requirement,
                :strengths,
                :gaps,
                :recommendations,
                :formatting_issues,
                NOW(),
                NOW()
            )";

    $stmt = $pdo->prepare($sql);

    $subScores  = $parsed['subScores']  ?? [];
    $skills     = $parsed['skills']     ?? [];
    $atsKeywords = $parsed['atsKeywords'] ?? [];
    $experience = $parsed['experience']  ?? [];
    $education  = $parsed['education']   ?? [];

    $stmt->execute([
        ':user_id'                     => $userId,
        ':job_title'                   => $parsed['jobTitle'] ?? $inputs['jobTitle'] ?? null,
        ':company'                     => $parsed['company'] ?? $inputs['company'] ?? null,
        ':resume_text'                 => $inputs['resumeText'] ?? '',
        ':job_description'             => $inputs['jobDescription'] ?? '',
        ':is_valid_input'              => isset($parsed['isValidInput']) ? (int) (bool) $parsed['isValidInput'] : 1,
        ':invalid_input_reason'        => $parsed['invalidInputReason'] ?? null,
        ':match_score'                 => $parsed['matchScore'] ?? null,
        ':verdict'                     => $parsed['verdict'] ?? null,
        ':summary'                     => $parsed['summary'] ?? null,
        ':skills_score'                => $subScores['skills'] ?? null,
        ':experience_score'            => $subScores['experience'] ?? null,
        ':education_score'             => $subScores['education'] ?? null,
        ':keywords_score'              => $subScores['keywords'] ?? null,
        ':skills_matched'              => toJsonColumn($skills['matched'] ?? null),
        ':skills_missing_required'     => toJsonColumn($skills['missingRequired'] ?? null),
        ':skills_missing_preferred'    => toJsonColumn($skills['missingPreferred'] ?? null),
        ':ats_keywords_missing'        => toJsonColumn($atsKeywords['missing'] ?? null),
        ':ats_keywords_underused'      => toJsonColumn($atsKeywords['underused'] ?? null),
        ':required_years'              => $experience['requiredYears'] ?? null,
        ':detected_years'              => $experience['detectedYears'] ?? null,
        ':experience_notes'            => $experience['experienceNotes'] ?? null,
        ':experience_highlights'       => toJsonColumn($experience['relevantHighlights'] ?? null),
        ':experience_gaps'             => toJsonColumn($experience['gaps'] ?? null),
        ':education_required'          => $education['required'] ?? null,
        ':education_detected'          => $education['detected'] ?? null,
        ':education_meets_requirement' => isset($education['meetsRequirement'])
            ? ($education['meetsRequirement'] === null ? null : (int) (bool) $education['meetsRequirement'])
            : null,
        ':strengths'                   => toJsonColumn($parsed['strengths'] ?? null),
        ':gaps'                        => toJsonColumn($parsed['gaps'] ?? null),
        ':recommendations'             => toJsonColumn($parsed['recommendations'] ?? null),
        ':formatting_issues'           => toJsonColumn($parsed['formattingIssues'] ?? null),
    ]);

    $newId = $pdo->lastInsertId();

    echo json_encode(['ok' => true, 'id' => (int) $newId]);
} catch (Exception $e) {
    error_log('[save-history] Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not save to history. Please try again.']);
}