<?php
/**
 * partials/results-recommendations.php
 *
 * "Actionable Recommendations" section — renders below the Experience &
 * Education section. Two parts:
 *   1. Numbered recommendation rows, each with a working "Copy" button.
 *   2. A "Resume Formatting & ATS-Friendliness" card listing parser risks.
 *
 * Expects two variables already in scope (set in results.php):
 *
 *   $recommendations = [
 *       [ 'action' => <string>, 'section' => <string> ], ...
 *   ];
 *   // Mirrors analyze.php's `recommendations[]` exactly:
 *   //   recommendations[].action / recommendations[].section
 *
 *   $formattingIssues = [
 *       [ 'message' => <string>, 'severity' => 'warning'|'info' ], ...
 *   ];
 *   // Not part of analyze.php's current JSON shape — this is a natural
 *   // extension of the same rubric (ATS parsing risk detection). Follows
 *   // the same { message, severity } shape convention as the rest of the
 *   // API so it can be added to the Gemini prompt/schema later with no
 *   // markup changes here.
 *
 * Numbering, icon choice, and icon color are all derived from array
 * position / the `severity` field — never authored per row.
 */

/**
 * Icon + color for a formatting issue, derived from severity. Same
 * "derive style from data" pattern as scoreBarColor() / skillPillClasses().
 */
function formattingIssueStyle(string $severity): array
{
    return match ($severity) {
        'warning' => ['icon' => '!', 'class' => 'text-red-500 border-red-500'],
        'info'    => ['icon' => 'i', 'class' => 'text-gray-400 border-gray-400'],
        default   => ['icon' => 'i', 'class' => 'text-gray-400 border-gray-400'],
    };
}
?>
<section class="mt-4" aria-labelledby="recommendations-heading">
    <h2 id="recommendations-heading" class="text-lg sm:text-xl font-bold text-gray-900 mb-3">
        Actionable Recommendations
    </h2>

    <!-- ============ NUMBERED RECOMMENDATIONS ============ -->
    <div class="border border-gray-300 shadow-[0_2px_8px_rgba(30,64,175,0.06),0_12px_32px_rgba(30,64,175,0.10)] bg-white rounded-xl mb-4">
        <?php if (empty($recommendations)): ?>
            <p class="text-sm text-gray-400 italic p-5">No specific recommendations generated.</p>
        <?php else: ?>
            <ul class="divide-y divide-gray-100">
                <?php foreach ($recommendations as $i => $rec): ?>
                    <li data-reveal class="flex items-center gap-3 sm:gap-4 px-4 sm:px-5 py-3.5">
                        <span class="flex-none w-6 h-6 rounded-full bg-gray-900 text-white text-xs font-bold flex items-center justify-center">
                            <?= (int) $i + 1 ?>
                        </span>
                        <span class="flex-1 text-sm text-blue-800 leading-snug">
                            <?= htmlspecialchars($rec['action']) ?>
                        </span>
                        <button
                            type="button"
                            class="js-copy-recommendation flex-none inline-flex items-center px-4 py-1.5 rounded-full border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 active:bg-gray-100 transition-colors"
                            data-copy-text="<?= htmlspecialchars($rec['action']) ?>"
                        >
                            Copy
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- ============ FORMATTING & ATS-FRIENDLINESS ============ -->
    <div class="border border-gray-300 shadow-[0_2px_8px_rgba(30,64,175,0.06),0_12px_32px_rgba(30,64,175,0.10)] bg-white rounded-xl p-4 sm:p-5 mb-6">
        <h3 class="text-xs font-semibold tracking-wide text-gray-500 uppercase mb-3">
            Resume Formatting &amp; ATS-Friendliness
        </h3>

        <?php if (empty($formattingIssues)): ?>
            <p class="text-sm text-gray-400 italic">No formatting issues detected.</p>
        <?php else: ?>
            <ul class="space-y-2.5">
                <?php foreach ($formattingIssues as $issue): ?>
                    <?php $style = formattingIssueStyle($issue['severity']); ?>
                    <li data-reveal class="flex items-start gap-2">
                        <span class="flex-none mt-0.5 inline-flex items-center justify-center w-4 h-4 rounded-full border-2 text-[10px] font-bold leading-none <?= $style['class'] ?>" aria-hidden="true">
                            <?= $style['icon'] ?>
                        </span>
                        <span class="text-sm text-gray-600">
                            <?= htmlspecialchars($issue['message']) ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- ============ PAGE ACTIONS ============ -->
    <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
        <button
            type="button"
            id="js-save-history"
            data-action="save-history"
            class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-2.5 rounded-full bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800 active:bg-black transition-colors"
        >
            Save to History
        </button>
        <button
            type="button"
            id="js-export-pdf"
            data-action="export-pdf"
            class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-2.5 rounded-full border border-gray-300 text-gray-800 text-sm font-semibold hover:bg-gray-50 active:bg-gray-100 transition-colors"
        >
            Export as PDF
        </button>
    </div>
</section>

<script src="<?= $GLOBALS['assetBase'] ?>/js/results/results-recommendations.js" defer></script>