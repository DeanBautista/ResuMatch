<?php
/**
 * partials/results-score-card.php
 *
 * The "hero" score card: match percentage, verdict badge, and the
 * AI-generated plain-language summary.
 *
 * Expects (set by results.php before including this file):
 *   int    $matchScore
 *   string $verdict        e.g. 'Strong Match'
 *   array  $verdictStyle   ['bg' => 'bg-lime-200', 'text' => 'text-lime-900']
 *   string $summary        may contain <strong> tags, must be pre-sanitized
 *
 * Animation: the ring fills from empty to $matchScore and the number
 * counts up alongside it, driven by js/scroll-reveal.js the same way
 * results-breakdown.php's bars are — the real stroke-dashoffset lives
 * in the inline style attribute, scroll-reveal.js zeroes it out then
 * restores it once the element is visible. See results-reveal.css
 * additions required, noted below.
 */

// Map the verdict's Tailwind bg class to a ring stroke color + soft track color.
// Falls back to gray if a new/unmapped verdict color shows up.
$ringColorMap = [
    'lime'    => ['stroke' => '#65a30d', 'track' => '#ecfccb'], // strong match
    'emerald' => ['stroke' => '#059669', 'track' => '#d1fae5'],
    'green'   => ['stroke' => '#16a34a', 'track' => '#dcfce7'],
    'amber'   => ['stroke' => '#d97706', 'track' => '#fef3c7'], // partial match
    'yellow'  => ['stroke' => '#ca8a04', 'track' => '#fef9c3'],
    'orange'  => ['stroke' => '#ea580c', 'track' => '#ffedd5'],
    'red'     => ['stroke' => '#dc2626', 'track' => '#fee2e2'], // weak match
    'gray'    => ['stroke' => '#6b7280', 'track' => '#f3f4f6'],
];

// Extract the color family name from something like "bg-lime-200"
preg_match('/bg-([a-z]+)-\d+/', $verdictStyle['bg'], $m);
$colorKey = $m[1] ?? 'gray';
$ring = $ringColorMap[$colorKey] ?? $ringColorMap['gray'];

// Ring geometry
$radius = 54;
$circumference = 2 * M_PI * $radius;
$clampedScore = max(0, min(100, (int) $matchScore));
$targetOffset = $circumference * (1 - $clampedScore / 100);
?>
<div class="border border-gray-300 shadow-[0_2px_8px_rgba(30,64,175,0.06),0_12px_32px_rgba(30,64,175,0.10)] bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-7 h-full flex flex-col md:flex-row items-center text-center lg:items-start lg:text-left">
    <div class="my-auto lg:mx-14 md:mx-8 flex flex-col items-center">

        <div class="relative flex items-center justify-center" style="width:132px;height:132px;" data-reveal>
            <svg width="132" height="132" viewBox="0 0 132 132" class="-rotate-90">
                <circle
                    cx="66" cy="66" r="<?= $radius ?>"
                    fill="none"
                    stroke="<?= $ring['track'] ?>"
                    stroke-width="10"
                />
                <circle
                    data-reveal="ring-fill"
                    cx="66" cy="66" r="<?= $radius ?>"
                    fill="none"
                    stroke="<?= $ring['stroke'] ?>"
                    stroke-width="10"
                    stroke-linecap="round"
                    stroke-dasharray="<?= $circumference ?>"
                    style="stroke-dashoffset: <?= $targetOffset ?>;"
                />
            </svg>
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="text-4xl font-bold text-gray-900">
                    <span data-reveal="score-number" data-reveal-target="<?= $clampedScore ?>">0</span><span class="text-lg align-top">%</span>
                </div>
            </div>
        </div>

        <span class="mt-3 inline-block whitespace-nowrap rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide <?= $verdictStyle['bg'] ?> <?= $verdictStyle['text'] ?>">
            <?= htmlspecialchars($verdict) ?>
        </span>
    </div>

    <div class="mt-6 w-full text-left">
        <p class="flex items-center gap-1.5 text-sm font-semibold uppercase tracking-wide text-orange-500 mb-2.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                <path d="M9 4.5a.75.75 0 01.721.544l.813 2.846a3.75 3.75 0 002.576 2.576l2.846.813a.75.75 0 010 1.442l-2.846.813a3.75 3.75 0 00-2.576 2.576l-.813 2.846a.75.75 0 01-1.442 0l-.813-2.846a3.75 3.75 0 00-2.576-2.576l-2.846-.813a.75.75 0 010-1.442l2.846-.813A3.75 3.75 0 007.75 7.89l.813-2.846A.75.75 0 019 4.5z" />
            </svg>
            AI Analysis
        </p>
        <p class="text-lg text-gray-600 leading-relaxed"><?= $summary /* pre-sanitized server-side before storage */ ?></p>
    </div>
</div>