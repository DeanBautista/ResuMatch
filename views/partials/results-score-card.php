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
 */
?>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-7 h-full flex flex-col md:flex-row items-center text-center lg:items-start lg:text-left">
    <div class="my-auto lg:mx-14 md:mx-8 flex flex-col items-center">
        <div class="text-5xl font-bold text-gray-900"><?= (int) $matchScore ?><span class="text-xl align-top">%</span></div>
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