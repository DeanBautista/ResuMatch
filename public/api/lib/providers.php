<?php
/**
 * api/lib/providers.php
 *
 * Low-level HTTP calls to Groq and Gemini. Extracted from analyze.php so
 * run_analysis.php (shared by analyze.php and rerun.php) can use them
 * without duplication.
 */

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
            'topP' => 0,          // sharpens greedy decoding further
            'topK' => 1,
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
        CURLOPT_TIMEOUT => 20, // Gemini can genuinely take longer than 30s
                                // on large structured-JSON responses... kept at 20.
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
        'temperature' => 0,
        'seed' => 42, 
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