<?php
/**
 * partials/results-breakdown.php
 *
 * The "Breakdown" card: one progress bar per sub-score.
 *
 * Expects (set by results.php before including this file):
 *   array $breakdownRows   [ ['label' => 'Skills', 'value' => 90], ... ]
 *
 * Uses scoreBarColor() (defined in results.php) to pick each bar's color
 * independently based on its own value, not a fixed color per row.
 */
?>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-7 h-full">
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