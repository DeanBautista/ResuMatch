<?php
/**
 * api/delete-history.php
 *
 * Deletes a single match_history row belonging to the logged-in user.
 * Used by the delete icon on /history cards.
 *
 * Auth: requires $_SESSION['user_id'], same as get-history.php /
 * save-history.php. 401 if not signed in.
 *
 * Expects: POST, JSON body { "id": <int> }
 *
 * Ownership: the DELETE is scoped by both id AND user_id, so a signed-in
 * user can never delete another user's row even if they guess/tamper
 * with the id.
 *
 * Returns JSON:
 *   { "ok": true }                     on success
 *   { "ok": false, "error": "..." }    on failure
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
    echo json_encode(['ok' => false, 'error' => 'You must be signed in to delete history.']);
    exit;
}

$userId = $_SESSION['user_id'];

// --- Parse & validate input ---
$body = json_decode(file_get_contents('php://input'), true);
$id   = $body['id'] ?? null;

if (!is_numeric($id)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'A valid id is required.']);
    exit;
}

try {
    $pdo = getPDO();

    $stmt = $pdo->prepare('DELETE FROM match_history WHERE id = :id AND user_id = :user_id');
    $stmt->execute([
        ':id'      => $id,
        ':user_id' => $userId,
    ]);

    if ($stmt->rowCount() === 0) {
        // Either it never existed, or it belongs to someone else — either
        // way, don't confirm which, just say not found.
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'History entry not found.']);
        exit;
    }

    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    error_log('[delete-history] Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not delete. Please try again.']);
}