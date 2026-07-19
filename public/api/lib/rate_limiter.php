<?php

/**
 * rate_limiter.php
 * IP-based rate limiting for the resume-analyze endpoint (unauthenticated).
 * Table: rate_limits (ip_address, request_count, window_start)
 */

function enforceRateLimit(PDO $pdo, int $limit = 20, int $windowSeconds = 3600): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // Upsert: increment count if still within window, otherwise reset window
    $stmt = $pdo->prepare("
        INSERT INTO rate_limits (ip_address, request_count, window_start)
        VALUES (INET6_ATON(:ip), 1, NOW())
        ON DUPLICATE KEY UPDATE
            request_count = IF(window_start < NOW() - INTERVAL :window SECOND, 1, request_count + 1),
            window_start  = IF(window_start < NOW() - INTERVAL :window SECOND, NOW(), window_start)
    ");
    $stmt->execute(['ip' => $ip, 'window' => $windowSeconds]);

    // Check current count after upsert
    $check = $pdo->prepare("
        SELECT request_count 
        FROM rate_limits 
        WHERE ip_address = INET6_ATON(:ip)
    ");
    $check->execute(['ip' => $ip]);
    $count = (int) $check->fetchColumn();

    if ($count > $limit) {
        http_response_code(429);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Too many requests. Please try again later.',
        ]);
        exit;
    }
}