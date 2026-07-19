<?php
/**
 * api/get-history.php
 *
 * Returns the logged-in user's match history (list view) as JSON, newest
 * first. Used by /history to populate the history cards via AJAX.
 *
 * Auth: requires $_SESSION['user_id'], same as save-history.php. 401 if
 * not signed in.
 *
 * Expects: GET
 *
 * Returns JSON:
 *   { "ok": true, "history": [ { ...row }, ... ] }   on success
 *   { "ok": false, "error": "..." }                    on failure
 *
 * Only the columns the history cards actually need are selected (id,
 * job_title, company, match_score, verdict, created_at). results.php
 * already loads the full row by id when a card is clicked, so there's no
 * need to duplicate resume_text / job_description / etc. here.
 */

session_start();

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/lib/db.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

header('Content-Type: application/json');

header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed. Use GET.']);
    exit;
}

// --- Auth check ---
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'You must be signed in to view history.']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $pdo = getPDO();

    $sql = "SELECT
                id,
                job_title,
                company,
                match_score,
                verdict,
                created_at
            FROM match_history
            WHERE user_id = :user_id
            ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $userId]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $history = array_map(function ($row) {
        return [
            'id'         => $row['id'],
            'jobTitle'   => $row['job_title'],
            'company'    => $row['company'],
            'matchScore' => $row['match_score'] !== null ? (int) $row['match_score'] : null,
            'verdict'    => $row['verdict'],
            'createdAt'  => $row['created_at'],
        ];
    }, $rows);

    echo json_encode(['ok' => true, 'history' => $history]);
} catch (Exception $e) {
    error_log('[get-history] Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not load history. Please try again.']);
}