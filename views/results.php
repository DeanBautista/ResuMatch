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
 * Data below is live when available: analyze.php stores its parsed Gemini
 * response in $_SESSION['last_analysis'] right before redirecting here, and
 * this file reads it back out. If nothing is in session (e.g. someone loads
 * /results directly without running an analysis first), it falls back to a
 * static/hardcoded example so the page still renders standalone.
 */

session_start();

// ---------------------------------------------------------------------
// DATA — live session data if present, else static fallback
// ---------------------------------------------------------------------
$live = $_SESSION['last_analysis'] ?? null;

if ($live) {
    $jobTitle  = $live['jobTitle'] ?? 'This Role';
    $company   = $live['company'] ?? '';
    $checkedAt = 'Checked just now';

    $matchScore = (int) ($live['matchScore'] ?? 0);
    $verdict    = $live['verdict'] ?? 'Weak Match'; // Strong Match | Moderate Match | Weak Match | Poor Match
    $summary    = $live['summary'] ?? ''; // pre-sanitized server-side (analyze.php) before storage

    $subScores = [
        'skills'     => (int) ($live['subScores']['skills']     ?? 0),
        'experience' => (int) ($live['subScores']['experience'] ?? 0),
        'education'  => (int) ($live['subScores']['education']  ?? 0),
        'keywords'   => (int) ($live['subScores']['keywords']   ?? 0),
    ];

    $strengths = $live['strengths'] ?? [];
    $gaps      = $live['gaps'] ?? [];

    // Mirrors analyze.php's `skills` object exactly:
    //   skills.matched / skills.missingRequired / skills.missingPreferred
    $skills = [
        'matched'          => $live['skills']['matched'] ?? [],
        'missingRequired'  => $live['skills']['missingRequired'] ?? [],
        'missingPreferred' => $live['skills']['missingPreferred'] ?? [],
    ];

    // Mirrors analyze.php's `atsKeywords` object exactly:
    //   atsKeywords.missing[]   -> { keyword, jdFrequency }
    //   atsKeywords.underused[] -> { keyword, resumeCount, jdFrequency }
    $atsKeywords = [
        'missing'   => $live['atsKeywords']['missing'] ?? [],
        'underused' => $live['atsKeywords']['underused'] ?? [],
    ];

    // Mirrors analyze.php's `experience` object exactly:
    //   experience.requiredYears / detectedYears / experienceNotes /
    //   experience.relevantHighlights[] / experience.gaps[]
    $experience = [
        'requiredYears'      => $live['experience']['requiredYears'] ?? 0,
        'detectedYears'      => $live['experience']['detectedYears'] ?? 0,
        'experienceNotes'    => $live['experience']['experienceNotes'] ?? '',
        'relevantHighlights' => $live['experience']['relevantHighlights'] ?? [],
        'gaps'               => $live['experience']['gaps'] ?? [],
    ];

    // Mirrors analyze.php's `education` object exactly:
    //   education.required / education.detected / education.meetsRequirement
    $education = [
        'required'         => $live['education']['required'] ?? '',
        'detected'         => $live['education']['detected'] ?? '',
        'meetsRequirement' => $live['education']['meetsRequirement'] ?? false,
    ];

    // Mirrors analyze.php's `recommendations[]` exactly:
    //   recommendations[].action / recommendations[].section
    $recommendations = $live['recommendations'] ?? [];

    // Mirrors analyze.php's `formattingIssues[]` exactly:
    //   formattingIssues[].message / formattingIssues[].severity
    $formattingIssues = $live['formattingIssues'] ?? [];
} else {
    // ---------------------------------------------------------------------
    // STATIC FALLBACK DATA (shape mirrors the Gemini JSON response from
    // analyze.php) — used only when /results is loaded with no analysis
    // in session, so the page still renders standalone during development.
    // ---------------------------------------------------------------------
    $jobTitle  = 'Senior Product Designer';
    $company   = 'Acme Co.';
    $checkedAt = 'Checked just now';

    $matchScore = 84;
    $verdict    = 'Strong Match';
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

    $skills = [
        'matched'          => ['Figma', 'Design Systems', 'Prototyping', 'UI Design'],
        'missingRequired'  => ['Agile/Scrum', 'Jira', 'User Research'],
        'missingPreferred' => ['React', 'Motion Design'],
    ];

    $atsKeywords = [
        'missing' => [
            ['keyword' => 'Design Systems', 'jdFrequency' => 4],
            ['keyword' => 'Accessibility',  'jdFrequency' => 2],
        ],
        'underused' => [
            ['keyword' => 'Prototyping', 'resumeCount' => 1, 'jdFrequency' => 3],
        ],
    ];

    $experience = [
        'requiredYears'      => 5,
        'detectedYears'      => 4,
        'experienceNotes'    => '',
        'relevantHighlights' => [
            '3 years at Tier-1 tech companies',
            'Led design for 2 major product launches',
        ],
        'gaps' => [
            'Short of 5-year senior requirement',
            'No direct Fintech sector experience',
        ],
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
// DERIVED / CONDITIONAL LOGIC (applies to both live and fallback data)
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
<title>Match Results — <?= htmlspecialchars($jobTitle) ?></title>
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
<script src="/js/scroll-reveal.js" defer></script>

</body>
</html>