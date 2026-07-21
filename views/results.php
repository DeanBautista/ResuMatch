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
 *   partials/results-experience-education.php years comparison, highlights/gaps, education match
 *   partials/results-recommendations.php      numbered recommendations w/ copy, ATS formatting risks
 *
 * DATA SOURCES (checked in this order):
 *   1. /results/{id} — $GLOBALS['routeParams']['id'] is set by index.php's
 *      '/results/:id' route. Row is loaded from match_history, scoped to
 *      the logged-in user (user_id must match session — see below). If the
 *      row doesn't exist or isn't owned by the current user, redirect to
 *      '/' (matches index.php's existing behavior for invalid access).
 *   2. /results (no id) — $_SESSION['last_analysis'], set by
 *      lib/run_analysis.php right before redirecting here from a fresh
 *      analyze.php/rerun.php run. If it's not set, there's nothing real to
 *      show, so redirect to '/' instead of rendering placeholder data.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../public/api/lib/db.php';

// ---------------------------------------------------------------------
// DATA — DB row (by id) if present, else live session data. If neither
// is available there is nothing real to render, so we redirect home
// rather than ever showing placeholder/fixture data.
// ---------------------------------------------------------------------
$routeId = $GLOBALS['routeParams']['id'] ?? null;
$live    = null;

if ($routeId !== null) {
    // --- /results/{id} : load from match_history, scoped to owner ---
    if (empty($_SESSION['user_id'])) {
        // Shouldn't happen — index.php's guard already blocks unauthenticated
        // access to /results — but fail safe rather than leak data.
        header('Location: /');
        exit;
    }

    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT * FROM match_history WHERE id = :id AND user_id = :user_id LIMIT 1');
        $stmt->execute([
            ':id'      => $routeId,
            ':user_id' => $_SESSION['user_id'],
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('[results] DB error loading id ' . $routeId . ': ' . $e->getMessage());
        $row = false;
    }

    if (!$row) {
        // Not found, or belongs to another user — redirect home, same as
        // index.php's guard behavior for invalid/unauthorized access.
        header('Location: /');
        exit;
    }

    /**
     * Small helper: reverse of save-history.php's toJsonColumn() — decode
     * a JSON-typed column back into a PHP array, defaulting to [] for
     * NULL/invalid values so downstream code can iterate safely.
     */
    $fromJsonColumn = function ($value): array {
        if ($value === null) {
            return [];
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    };

    // Reassemble the same shape run_analysis.php stores in
    // $_SESSION['last_analysis'], from the flat DB columns, so the rest of
    // this file and all partials work unmodified regardless of source.
    $live = [
        'jobTitle'   => $row['job_title'],
        'company'    => $row['company'],
        'matchScore' => $row['match_score'],
        'verdict'    => $row['verdict'],
        'summary'    => $row['summary'],
        'subScores'  => [
            'skills'     => $row['skills_score'],
            'experience' => $row['experience_score'],
            'education'  => $row['education_score'],
            'keywords'   => $row['keywords_score'],
        ],
        'strengths' => $fromJsonColumn($row['strengths']),
        'gaps'      => $fromJsonColumn($row['gaps']),
        'skills'    => [
            'matched'          => $fromJsonColumn($row['skills_matched']),
            'missingRequired'  => $fromJsonColumn($row['skills_missing_required']),
            'missingPreferred' => $fromJsonColumn($row['skills_missing_preferred']),
        ],
        'atsKeywords' => [
            'missing'   => $fromJsonColumn($row['ats_keywords_missing']),
            'underused' => $fromJsonColumn($row['ats_keywords_underused']),
        ],
        'experience' => [
            'requiredYears'      => $row['required_years'],
            'detectedYears'      => $row['detected_years'],
            'experienceNotes'    => $row['experience_notes'],
            'relevantHighlights' => $fromJsonColumn($row['experience_highlights']),
            'gaps'               => $fromJsonColumn($row['experience_gaps']),
        ],
        'education' => [
            'required'         => $row['education_required'],
            'detected'         => $row['education_detected'],
            'meetsRequirement' => (bool) $row['education_meets_requirement'],
        ],
        'recommendations'   => $fromJsonColumn($row['recommendations']),
        'formattingIssues'  => $fromJsonColumn($row['formatting_issues']),
        // Not currently persisted — match_history has no job_description
        // column yet, so this is null for history rows until that's added.
        // Left here so the rest of the page (and the JD panel) works
        // unmodified the moment it's populated from $row['job_description'].
        'jobDescription'    => $row['job_description'] ?? null,
    ];

    $checkedAtOverride = $row['created_at']; // real timestamp, not "just now"
} else {
    $live = $_SESSION['last_analysis'] ?? null;

    if (!$live) {
        // Plain /results with nothing in session — no real analysis to
        // show. Redirect home instead of rendering fixture/placeholder
        // data.
        header('Location: /');
        exit;
    }

    // run_analysis.php stores the JD text separately, in
    // last_analysis_input (alongside resumeText), not on $parsed itself —
    // fold it into $live so the rest of this file only has one shape to
    // deal with regardless of source.
    $live['jobDescription'] = $_SESSION['last_analysis_input']['jobDescription'] ?? null;
}

// $live is guaranteed to be set past this point: either reassembled from
// the DB row above, or pulled from session — both branches redirect away
// (and exit) if they don't have real data. Its sub-fields are trusted to
// match the shape analyze.php / run_analysis.php / the DB reassembly above
// produce exactly, so nothing below applies a fallback default — a missing
// key here means an upstream shape mismatch, not a value to paper over.
$jobTitle  = $live['jobTitle'];
$company   = $live['company'];
$checkedAt = isset($checkedAtOverride) ? date('M j, Y', strtotime($checkedAtOverride)) : 'Checked just now';

// Null (not just empty string) when unavailable, so the panel can tell
// "not stored for this check" apart from "stored but blank".
$jobDescription = $live['jobDescription'];
if ($jobDescription === '') {
    $jobDescription = null;
}

$matchScore = (int) $live['matchScore'];
$verdict    = $live['verdict']; // Strong Match | Moderate Match | Weak Match | Poor Match
$summary    = $live['summary']; // pre-sanitized server-side (analyze.php) before storage

$subScores = [
    'skills'     => (int) $live['subScores']['skills'],
    'experience' => (int) $live['subScores']['experience'],
    'education'  => (int) $live['subScores']['education'],
    'keywords'   => (int) $live['subScores']['keywords'],
];

$strengths = $live['strengths'];
$gaps      = $live['gaps'];

// Mirrors analyze.php's `skills` object exactly:
//   skills.matched / skills.missingRequired / skills.missingPreferred
$skills = [
    'matched'          => $live['skills']['matched'],
    'missingRequired'  => $live['skills']['missingRequired'],
    'missingPreferred' => $live['skills']['missingPreferred'],
];

// Mirrors analyze.php's `atsKeywords` object exactly:
//   atsKeywords.missing[]   -> { keyword, jdFrequency }
//   atsKeywords.underused[] -> { keyword, resumeCount, jdFrequency }
$atsKeywords = [
    'missing'   => $live['atsKeywords']['missing'],
    'underused' => $live['atsKeywords']['underused'],
];

// Mirrors analyze.php's `experience` object exactly:
//   experience.requiredYears / detectedYears / experienceNotes /
//   experience.relevantHighlights[] / experience.gaps[]
$experience = [
    'requiredYears'      => $live['experience']['requiredYears'],
    'detectedYears'      => $live['experience']['detectedYears'],
    'experienceNotes'    => $live['experience']['experienceNotes'],
    'relevantHighlights' => $live['experience']['relevantHighlights'],
    'gaps'               => $live['experience']['gaps'],
];

// Mirrors analyze.php's `education` object exactly:
//   education.required / education.detected / education.meetsRequirement
$education = [
    'required'         => $live['education']['required'],
    'detected'         => $live['education']['detected'],
    'meetsRequirement' => (bool) $live['education']['meetsRequirement'],
];

// Mirrors analyze.php's `recommendations[]` exactly:
//   recommendations[].action / recommendations[].section
$recommendations = $live['recommendations'];

// Mirrors analyze.php's `formattingIssues[]` exactly:
//   formattingIssues[].message / formattingIssues[].severity
$formattingIssues = $live['formattingIssues'];

// ---------------------------------------------------------------------
// DERIVED / CONDITIONAL LOGIC
// ---------------------------------------------------------------------

// Verdict badge color follows the same score bands used server-side in
// analyze.php's rubric (80-100 Strong, 60-79 Moderate, 40-59 Weak, 0-39 Poor).
$verdictStyles = [
    'Strong Match'   => ['bg' => 'bg-green-100', 'text' => 'text-green-700'],
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
    if ($score >= 80) return 'bg-green-500';
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
<link rel="icon" type="image/svg+xml" href="/site-icon.svg">
<title>ResuMatch Results — <?= htmlspecialchars($jobTitle) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/css/animations.css">
<link rel="stylesheet" href="/css/results-reveal.css">
</head>
<body class="min-h-screen bg-[radial-gradient(circle_at_15%_-10%,#cfe0fb_0%,transparent_45%),radial-gradient(circle_at_100%_0%,#e3edfd_0%,transparent_50%),linear-gradient(160deg,#dbeafe_0%,#eaf3fd_45%,#f3f8fe_100%)] bg-fixed">

<?php include 'partials/header.php'; ?>

<main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

    <?php include 'partials/results-header.php'; ?>

    <!-- ================= SCORE + BREAKDOWN ================= -->
    <div class="grid grid-cols-1 lg:grid-cols-[calc(65%-1rem)_35%] gap-4 mb-6">
        <div data-reveal="score-card">
            <?php include 'partials/results-score-card.php'; ?>
        </div>
        <div data-reveal-group="breakdown">
            <?php include 'partials/results-breakdown.php'; ?>
        </div>
    </div>

    <!-- ================= STRENGTHS + GAPS ================= -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 items-stretch">
        <div class="h-full" data-reveal-group="insight-list">
            <?php
            $listVariant = 'strengths';
            $listHeading = 'Strengths';
            $listItems   = $strengths;
            include 'partials/insight-list.php';
            ?>
        </div>

        <div class="h-full" data-reveal-group="insight-list">
            <?php
            $listVariant = 'gaps';
            $listHeading = 'Gaps to Address';
            $listItems   = $gaps;
            include 'partials/insight-list.php';
            ?>
        </div>
    </div>

    <!-- ================= SKILLS BREAKDOWN ================= -->
    <div data-reveal-group="skill-chips">
        <?php include 'partials/results-skills-breakdown.php'; ?>
    </div>

    <!-- ================= EXPERIENCE & EDUCATION ================= -->
    <div data-reveal-group="exp-edu">
        <?php include 'partials/results-experience-education.php'; ?>
    </div>

    <!-- ================= ACTIONABLE RECOMMENDATIONS ================= -->
    <div data-reveal-group="recommendations">
        <?php include 'partials/results-recommendations.php'; ?>
    </div>

    <!-- ================= JOB DESCRIPTION (floating button + panel) ================= -->
    <?php include 'partials/results-jd-panel.php'; ?>

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

<script src="<?= $GLOBALS['assetBase'] ?>/js/toast.js"></script>
<script src="<?= $GLOBALS['assetBase'] ?>/js/loading-overlay.js"></script>
<script src="<?= $GLOBALS['assetBase'] ?>/js/scroll-reveal.js" defer></script>

</body>
</html>