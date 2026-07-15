<?php
/**
 * views/results.php
 *
 * Renders the AI match analysis result page.
 *
 * NOTE: This file duplicates the same HTML boilerplate (doctype, head,
 * body wrapper, footer, script includes) found in home.php. That
 * duplication is intentional only in the sense that it mirrors home.php's
 * current structure — it is NOT good long-term practice. Once a shared
 * layout partial exists (e.g. partials/layout-top.php / layout-bottom.php),
 * this boilerplate should be removed from here and from home.php and
 * replaced with includes of that shared layout instead.
 *
 * Content (title through Skills Breakdown card) is split into partials/ — see:
 *   partials/results-header.php          title, timestamp, re-run button
 *   partials/results-score-card.php      match %, verdict badge, AI summary
 *   partials/results-breakdown.php       sub-score progress bars
 *   partials/insight-list.php            reusable strengths/gaps list card
 *   partials/results-skills-breakdown.php matched/missing skills + ATS keyword rows
 *
 * Data below is static/hardcoded but shaped to match exactly what
 * /api/analyze.php returns (see $data['parsed']), so the data section can
 * later be swapped for the real decoded API response with no markup
 * changes.
 */

// ---------------------------------------------------------------------
// STATIC DATA (shape mirrors the Gemini JSON response from analyze.php)
// ---------------------------------------------------------------------
$jobTitle = 'Senior Product Designer';
$company  = 'Acme Co.';
$checkedAt = 'Checked just now';

$matchScore = 84;
$verdict    = 'Strong Match'; // Strong Match | Moderate Match | Weak Match | Poor Match
$summary    = 'Your experience aligns well with the core requirements, particularly in <strong>design systems</strong> and <strong>prototyping</strong>. Minor gaps detected in specific management tools.';

$subScores = [
    'skills'     => 90,
    'experience' => 85,
    'education'  => 100,
    'keywords'   => 72,
];

$strengths = [
    'Strong evidence of <strong>Design Systems</strong> management (5+ years).',
    'Direct match for required <strong>Figma</strong> and prototyping skills.',
];

$gaps = [
    'Missing explicit mention of <strong>Agile/Scrum</strong> methodologies.',
    'No direct experience listed for <strong>Jira</strong> or tracking tools.',
];

// Mirrors analyze.php's `skills` object exactly:
//   skills.matched / skills.missingRequired / skills.missingPreferred
$skills = [
    'matched'          => ['Figma', 'Design Systems', 'Prototyping', 'UI Design'],
    'missingRequired'  => ['Agile/Scrum', 'Jira', 'User Research'],
    'missingPreferred' => ['React', 'Motion Design'],
];

// Mirrors analyze.php's `atsKeywords` object exactly:
//   atsKeywords.missing[]   -> { keyword, jdFrequency }
//   atsKeywords.underused[] -> { keyword, resumeCount, jdFrequency }
$atsKeywords = [
    'missing' => [
        ['keyword' => 'Design Systems', 'jdFrequency' => 4],
        ['keyword' => 'Accessibility',  'jdFrequency' => 2],
    ],
    'underused' => [
        ['keyword' => 'Prototyping', 'resumeCount' => 1, 'jdFrequency' => 3],
    ],
];

// ---------------------------------------------------------------------
// DERIVED / CONDITIONAL LOGIC
// ---------------------------------------------------------------------

// Verdict badge color follows the same score bands used server-side in
// analyze.php's rubric (80-100 Strong, 60-79 Moderate, 40-59 Weak, 0-39 Poor).
$verdictStyles = [
    'Strong Match'   => ['bg' => 'bg-lime-200', 'text' => 'text-lime-900'],
    'Moderate Match' => ['bg' => 'bg-yellow-200', 'text' => 'text-yellow-900'],
    'Weak Match'     => ['bg' => 'bg-orange-200', 'text' => 'text-orange-900'],
    'Poor Match'     => ['bg' => 'bg-red-200', 'text' => 'text-red-900'],
];
$verdictStyle = $verdictStyles[$verdict] ?? $verdictStyles['Weak Match'];

// Each breakdown bar's fill color is conditional on its own score, not a
// fixed color per row — a 72% keyword score should read as "attention
// needed" even though skills/experience/education are strong.
function scoreBarColor(int $score): string
{
    if ($score >= 80) return 'bg-lime-500';
    if ($score >= 60) return 'bg-orange-400';
    return 'bg-red-500';
}

$breakdownRows = [
    ['label' => 'Skills',     'value' => $subScores['skills']],
    ['label' => 'Experience', 'value' => $subScores['experience']],
    ['label' => 'Education',  'value' => $subScores['education']],
    ['label' => 'Keywords',   'value' => $subScores['keywords']],
];

// jdFrequency at/above this = "HIGH PRIORITY" badge in the ATS keyword
// list. Kept here (not inside the partial) so it's a single tunable knob
// alongside the rest of the page's scoring config.
$keywordHighPriorityThreshold = 3;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Match Results — <?= htmlspecialchars($jobTitle) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/css/animations.css">
</head>
<body class="min-h-screen bg-gradient-to-b from-[#dfe3ee] via-[#eee0e6] to-[#f5dfdd]">

<?php include 'partials/header.php'; ?>

<main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

    <?php include 'partials/results-header.php'; ?>

    <!-- ================= SCORE + BREAKDOWN ================= -->
    <div class="grid grid-cols-1 lg:grid-cols-[calc(65%-1rem)_35%] gap-4 mb-6">
        <?php include 'partials/results-score-card.php'; ?>
        <?php include 'partials/results-breakdown.php'; ?>
    </div>

    <!-- ================= STRENGTHS + GAPS ================= -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <?php
        $listVariant = 'strengths';
        $listHeading = 'Strengths';
        $listItems   = $strengths;
        include 'partials/insight-list.php';
        ?>

        <?php
        $listVariant = 'gaps';
        $listHeading = 'Gaps to Address';
        $listItems   = $gaps;
        include 'partials/insight-list.php';
        ?>
    </div>

    <!-- ================= SKILLS BREAKDOWN ================= -->
    <?php include 'partials/results-skills-breakdown.php'; ?>

</main>

<footer class="border-t border-gray-200/60">
  <div class="max-w-6xl mx-auto px-6 lg:px-10 py-6 text-center">
    <nav class="flex items-center justify-center gap-6 text-sm text-gray-700">
      <a href="/privacy" class="hover:text-gray-900">Privacy</a>
      <a href="/terms" class="hover:text-gray-900">Terms</a>
      <a href="/support" class="hover:text-gray-900">Support</a>
    </nav>
    <p class="text-xs text-gray-500 mt-3">&copy; <?= date('Y') ?> Match AI. All rights reserved.</p>
  </div>
</footer>

<?php include 'partials/toast.php'; ?>
<?php include 'partials/loading-overlay.php'; ?>

<script src="/js/toast.js"></script>
<script src="/js/loading-overlay.js"></script>

</body>
</html>