<?php
/**
 * export-pdf.php
 *
 * AJAX-triggered endpoint for the "Export as PDF" button on results.php.
 * Called via fetch() from js/results/results-recommendations.js — the
 * browser stays on results.php; this endpoint just returns a PDF blob
 * which the JS turns into a download.
 *
 * Data source: identical to results.php — reads $_SESSION['last_analysis']
 * if present, otherwise falls back to the same static example data, so
 * "Export as PDF" always produces *something* consistent with whatever
 * is currently on screen. No analysis logic is duplicated here; this
 * file only re-derives the same display variables results.php already
 * derives (verdict styling, bar colors, breakdown rows, etc.), then
 * feeds them to dompdf-safe partials instead of the Tailwind ones.
 *
 * Requires dompdf. Install with:
 *   composer require dompdf/dompdf
 *
 * If Composer isn't set up yet in this project, run this once in the
 * project root:
 *   composer init --no-interaction && composer require dompdf/dompdf
 */

session_start();

// dompdf builds its entire render tree in memory and embeds font subsets,
// which regularly exceeds PHP's default 128M limit on anything but the
// simplest documents — this can cause a silent truncated/corrupt PDF
// output (fatal error thrown mid-stream, after headers were already sent,
// so it never appears as a visible error page). Raise it for this script only.
ini_set('memory_limit', '512M');

// vendor/ lives at the project root (one level above public/, where this
// file lives), since Composer installs relative to composer.json's
// location, not relative to the web document root.
require __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// ---------------------------------------------------------------------
// DATA — same shape/logic as results.php (kept in sync intentionally;
// if you refactor results.php's data section into a shared function,
// e.g. loadResultsData(), swap both call sites to use it).
// ---------------------------------------------------------------------
$live = $_SESSION['last_analysis'] ?? null;

if ($live) {
    $jobTitle  = $live['jobTitle'] ?? 'This Role';
    $company   = $live['company'] ?? '';
    $checkedAt = 'Generated ' . date('M j, Y g:i A');

    $matchScore = (int) ($live['matchScore'] ?? 0);
    $verdict    = $live['verdict'] ?? 'Weak Match';
    $summary    = $live['summary'] ?? '';

    $subScores = [
        'skills'     => (int) ($live['subScores']['skills']     ?? 0),
        'experience' => (int) ($live['subScores']['experience'] ?? 0),
        'education'  => (int) ($live['subScores']['education']  ?? 0),
        'keywords'   => (int) ($live['subScores']['keywords']   ?? 0),
    ];

    $strengths = $live['strengths'] ?? [];
    $gaps      = $live['gaps'] ?? [];

    $skills = [
        'matched'          => $live['skills']['matched'] ?? [],
        'missingRequired'  => $live['skills']['missingRequired'] ?? [],
        'missingPreferred' => $live['skills']['missingPreferred'] ?? [],
    ];

    $atsKeywords = [
        'missing'   => $live['atsKeywords']['missing'] ?? [],
        'underused' => $live['atsKeywords']['underused'] ?? [],
    ];

    $experience = [
        'requiredYears'      => $live['experience']['requiredYears'] ?? 0,
        'detectedYears'      => $live['experience']['detectedYears'] ?? 0,
        'experienceNotes'    => $live['experience']['experienceNotes'] ?? '',
        'relevantHighlights' => $live['experience']['relevantHighlights'] ?? [],
        'gaps'               => $live['experience']['gaps'] ?? [],
    ];

    $education = [
        'required'         => $live['education']['required'] ?? '',
        'detected'         => $live['education']['detected'] ?? '',
        'meetsRequirement' => $live['education']['meetsRequirement'] ?? false,
    ];

    $recommendations  = $live['recommendations'] ?? [];
    $formattingIssues = $live['formattingIssues'] ?? [];
} else {
    // Same static fallback as results.php, trimmed to what this file needs.
    $jobTitle  = 'Senior Product Designer';
    $company   = 'Acme Co.';
    $checkedAt = 'Generated ' . date('M j, Y g:i A');

    $matchScore = 84;
    $verdict    = 'Strong Match';
    $summary    = 'Your experience aligns well with the core requirements, particularly in <strong>design systems</strong> and <strong>prototyping</strong>. Minor gaps detected in specific management tools.';

    $subScores = ['skills' => 90, 'experience' => 85, 'education' => 100, 'keywords' => 72];

    $strengths = [
        'Strong evidence of <strong>Design Systems</strong> management (5+ years).',
        'Direct match for required <strong>Figma</strong> and prototyping skills.',
    ];
    $gaps = [
        'Missing explicit mention of <strong>Agile/Scrum</strong> methodologies.',
        'No direct experience listed for <strong>Jira</strong> or tracking tools.',
    ];

    $skills = [
        'matched'          => ['Figma', 'Design Systems', 'Prototyping', 'UI Design'],
        'missingRequired'  => ['Agile/Scrum', 'Jira', 'User Research'],
        'missingPreferred' => ['React', 'Motion Design'],
    ];

    $atsKeywords = [
        'missing'   => [['keyword' => 'Design Systems', 'jdFrequency' => 4], ['keyword' => 'Accessibility', 'jdFrequency' => 2]],
        'underused' => [['keyword' => 'Prototyping', 'resumeCount' => 1, 'jdFrequency' => 3]],
    ];

    $experience = [
        'requiredYears'      => 5,
        'detectedYears'      => 4,
        'experienceNotes'    => '',
        'relevantHighlights' => ['3 years at Tier-1 tech companies', 'Led design for 2 major product launches'],
        'gaps'               => ['Short of 5-year senior requirement', 'No direct Fintech sector experience'],
    ];

    $education = [
        'required'         => "Bachelor's in Design",
        'detected'         => "Bachelor's in Design",
        'meetsRequirement' => true,
    ];

    $recommendations = [
        ['action' => "Add 'Stakeholder Management' and 'User Research' to your skills section", 'section' => 'Skills'],
        ['action' => 'Quantify your impact at Acme Co with specific metrics (e.g., % growth)', 'section' => 'Experience'],
        ['action' => 'Ensure your job titles match the JD keywords where appropriate', 'section' => 'Experience'],
        ['action' => "Remove the 'References available upon request' section to save space", 'section' => 'Formatting'],
        ['action' => "Update your location to 'Remote' or 'New York, NY' to match requirements", 'section' => 'Contact Info'],
    ];

    $formattingIssues = [
        ['message' => '2-column layout detected: some ATS systems may struggle with reading order.', 'severity' => 'warning'],
        ['message' => 'Non-standard font: Ensure you use web-safe fonts for optimal parsing.', 'severity' => 'info'],
        ['message' => 'Missing contact information: your phone number was not detected.', 'severity' => 'warning'],
    ];
}

// ---------------------------------------------------------------------
// DERIVED — pdf-flavored versions of results.php's helper logic.
// (Same bands/thresholds as results.php's scoreBarColor() /
// $verdictStyles — kept numerically identical, only the output class
// names differ because the PDF stylesheet uses its own class names.)
// ---------------------------------------------------------------------
$verdictStylesPdf = [
    'Strong Match'   => ['pdfClass' => 'verdict-strong'],
    'Moderate Match' => ['pdfClass' => 'verdict-moderate'],
    'Weak Match'     => ['pdfClass' => 'verdict-weak'],
    'Poor Match'     => ['pdfClass' => 'verdict-poor'],
];
$verdictStyle = $verdictStylesPdf[$verdict] ?? $verdictStylesPdf['Weak Match'];

function scoreBarColorPdf(int $score): string
{
    if ($score >= 80) return 'bar-green';
    if ($score >= 60) return 'bar-orange';
    return 'bar-red';
}

if (!function_exists('formattingIssueStyle')) {
    function formattingIssueStyle(string $severity): array
    {
        return match ($severity) {
            'warning' => ['icon' => '!', 'pdfClass' => 'issue-warning'],
            'info'    => ['icon' => 'i', 'pdfClass' => 'issue-info'],
            default   => ['icon' => 'i', 'pdfClass' => 'issue-info'],
        };
    }
}

$breakdownRows = [
    ['label' => 'Skills',     'value' => $subScores['skills']],
    ['label' => 'Experience', 'value' => $subScores['experience']],
    ['label' => 'Education',  'value' => $subScores['education']],
    ['label' => 'Keywords',   'value' => $subScores['keywords']],
];

$keywordHighPriorityThreshold = 3;

// ---------------------------------------------------------------------
// RENDER — build the full print-safe HTML document by including each
// pdf partial in turn, buffering their output into a single string.
// ---------------------------------------------------------------------
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<?php include __DIR__ . '/../views/partials/pdf/pdf-styles.php'; ?>
</head>
<body>
    <div class="pdf-header">
        <p class="job-title"><?= htmlspecialchars($jobTitle) ?><?= $company ? ' — ' . htmlspecialchars($company) : '' ?></p>
        <p class="job-meta"><?= htmlspecialchars($checkedAt) ?></p>
    </div>

    <?php include __DIR__ . '/../views/partials/pdf/pdf-score-breakdown.php'; ?>
    <?php include __DIR__ . '/../views/partials/pdf/pdf-strengths-gaps-skills.php'; ?>
    <?php include __DIR__ . '/../views/partials/pdf/pdf-experience-education.php'; ?>
    <?php include __DIR__ . '/../views/partials/pdf/pdf-recommendations.php'; ?>

    <div class="pdf-footer">
        Generated by Match AI &middot; <?= date('Y') ?>
    </div>
</body>
</html>
<?php
$html = ob_get_clean();

// ---------------------------------------------------------------------
// GENERATE PDF
// ---------------------------------------------------------------------
$options = new Options();
$options->set('isRemoteEnabled', false); // we don't load external images/fonts
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Call output() exactly once and reuse the result — calling it twice (once
// for Content-Length, once for echo) re-serializes the entire PDF from
// scratch each time, roughly doubling memory/CPU cost at the single most
// expensive step and increasing the odds of hitting memory_limit mid-stream.
$pdfOutput = $dompdf->output();

$filenameSafeTitle = preg_replace('/[^A-Za-z0-9\-_]+/', '-', $jobTitle);
$filename = 'match-results-' . $filenameSafeTitle . '-' . date('Ymd-His') . '.pdf';

// Stream back to the fetch() call as a downloadable blob.
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdfOutput));
echo $pdfOutput;
exit;