<?php
/**
 * partials/results-breakdown.php
 *
 * The "Breakdown" card: one progress bar per sub-score.
 *
 * Expects (set by results.php before including this file):
 *   array $subScores   ['skills' => 90, 'experience' => 80, 'education' => 70, 'keywords' => 60]
 *
 * $breakdownRows is built from $subScores below, and each bar's color is
 * picked independently based on its own value via scoreBarColor() (not a
 * fixed color per row — a 72% keyword score should read as "attention
 * needed" even when the other three are strong). Both live here now since
 * this card is the only place either is used.
 */

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
?>
<div class="border border-gray-300 shadow-[0_2px_8px_rgba(30,64,175,0.06),0_12px_32px_rgba(30,64,175,0.10)] bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-7 h-full">
    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-5">Breakdown</p>
    <div class="space-y-5">
        <?php foreach ($breakdownRows as $row): ?>
            <div data-reveal>
                <div class="flex items-center justify-between text-sm mb-1.5">
                    <span class="text-gray-700"><?= htmlspecialchars($row['label']) ?></span>
                    <span class="font-semibold text-gray-900"><?= (int) $row['value'] ?>%</span>
                </div>
                <div class="h-2.5 w-full rounded-full bg-gray-100 overflow-hidden">
                    <div data-reveal="bar-fill" class="h-full rounded-full <?= scoreBarColor($row['value']) ?>" style="width: <?= (int) $row['value'] ?>%"></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>