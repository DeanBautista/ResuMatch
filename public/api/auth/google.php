<?php

session_start();

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../lib/db.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
$dotenv->safeLoad();

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || (!isset($input['credential']) && !isset($input['access_token']))) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing credential or access_token']);
    exit;
}

try {
    if (isset($input['credential'])) {
        // ID token flow (One Tap / google.accounts.id)
        $payload = verifyGoogleIdToken($input['credential'], $_ENV['GOOGLE_CLIENT_ID']);
    } else {
        // Access token flow (popup / google.accounts.oauth2)
        $payload = fetchGoogleUserInfo($input['access_token']);
    }

    // --- LOGGING: shows in your terminal (php -S output) via error_log,
    // and you can also see it by var_dump-ing the JSON response in the browser console.
    error_log('[Google OAuth] User payload: ' . json_encode($payload));

    $googleId = $payload['sub'];
    $email    = $payload['email'];
    $name     = $payload['name'] ?? '';

    $pdo = getPDO();

    $stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ?");
    $stmt->execute([$googleId]);
    $user = $stmt->fetch();

    if (!$user) {
        $stmt = $pdo->prepare("INSERT INTO users (google_id, email, name) VALUES (?, ?, ?)");
        $stmt->execute([$googleId, $email, $name]);
        $userId = $pdo->lastInsertId();
        error_log("[Google OAuth] Created new user id={$userId} email={$email}");
    } else {
        $userId = $user['id'];
        error_log("[Google OAuth] Existing user id={$userId} email={$email}");
    }

    $_SESSION['user_id'] = $userId;
    $_SESSION['email']   = $email;
    $_SESSION['name']    = $name;
    error_log('[DEBUG google.php] session id=' . session_id() . ' data=' . json_encode($_SESSION));
    echo json_encode([
        'status' => 'success',
        'user'   => ['id' => $userId, 'email' => $email, 'name' => $name],
    ]);

} catch (Exception $e) {
    error_log('[Google OAuth] Error: ' . $e->getMessage());
    http_response_code(401);
    echo json_encode(['error' => 'Authentication failed', 'message' => $e->getMessage()]);
}

/**
 * Verify a Google ID token (JWT) by asking Google's tokeninfo endpoint.
 * Simpler than manual JWT signature verification; fine for most apps.
 */
function verifyGoogleIdToken(string $idToken, string $expectedClientId): array
{
    $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken);
    $response = file_get_contents($url);

    if ($response === false) {
        throw new Exception('Could not reach Google tokeninfo endpoint');
    }

    $data = json_decode($response, true);

    if (isset($data['error'])) {
        throw new Exception('Invalid ID token: ' . $data['error']);
    }

    if ($data['aud'] !== $expectedClientId) {
        throw new Exception('Token audience mismatch');
    }

    return $data; // contains sub, email, name, picture, etc.
}

/**
 * Fetch user info using an OAuth2 access token (popup flow).
 */
function fetchGoogleUserInfo(string $accessToken): array
{
    $ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer {$accessToken}"]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception('Failed to fetch user info from Google');
    }

    return json_decode($response, true); // contains sub, email, name, picture, etc.
}