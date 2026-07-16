    <?php
    session_start();
    /**
     * /api/rerun.php
     *
     * Powers the "Re-run check" button on the results page. Re-runs the
     * analysis using the resume/JD/job title/company that were stored in
     * session by the last successful call to analyze.php — the user does
     * not need to re-paste anything.
     *
     * Expects: POST, no body required (everything needed is in session).
     *
     * Returns the same JSON shape as analyze.php:
     *   { "ok": true, "provider": "gemini"|"groq", "raw": "...", "parsed": {...} }
     *   { "ok": false, "error": "..." }
     */
    require __DIR__ . '/../../vendor/autoload.php';
    require __DIR__ . '/lib/providers.php';
    require __DIR__ . '/lib/run_analysis.php';

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

    $input = $_SESSION['last_analysis_input'] ?? null;

    if (!$input || empty($input['resumeText']) || empty($input['jobDescription'])) {
        http_response_code(409);
        echo json_encode([
            'ok' => false,
            'error' => 'Nothing to re-run — no previous analysis found in this session. Please analyze a resume first.',
        ]);
        exit;
    }

    $result = runResumeAnalysis(
        $input['resumeText'],
        $input['jobDescription'],
        $input['jobTitle'] ?? '',
        $input['company'] ?? ''
    );

    http_response_code($result['httpStatus']);
    echo json_encode($result['body']);