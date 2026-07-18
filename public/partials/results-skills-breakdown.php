<?php
/**
 * partials/results-skills-breakdown.php
 *
 * "Skills Breakdown" card — renders below the Strengths / Gaps section.
 *
 * Expects three variables to already be in scope (set in results.php,
 * shaped to match /api/analyze.php's parsed Gemini response 1:1):
 *
 *   $skills = [
 *       'matched'          => [<string>, ...],
 *       'missingRequired'  => [<string>, ...],
 *       'missingPreferred' => [<string>, ...],
 *   ];
 *
 *   $atsKeywords = [
 *       'missing'   => [ ['keyword' => <string>, 'jdFrequency' => <int>], ... ],
 *       'underused' => [ ['keyword' => <string>, 'resumeCount' => <int>, 'jdFrequency' => <int>], ... ],
 *   ];
 *
 *   $keywordHighPriorityThreshold = <int>  // jdFrequency >= this => "HIGH PRIORITY" badge
 *
 * Nothing about which keyword gets flagged as high priority, or what its
 * row text says, is hardcoded per-row — it's all derived below from the
 * data shape above, the same way analyze.php produces it. Swap in the real
 * decoded API response and this partial needs no changes.
 */

$keywordHighPriorityThreshold = $keywordHighPriorityThreshold ?? 3;

/**
 * Merge "missing" + "underused" ATS keywords into one normalized list for
 * rendering, each row carrying only what it needs:
 *   - keyword
 *   - jdFrequency
 *   - resumeCount   (0 for fully missing keywords)
 *   - highPriority  (derived from jdFrequency, not authored per row)
 *   - statusLabel   (derived from resumeCount vs jdFrequency)
 *
 * Sorted by jdFrequency desc so the most JD-emphasized keywords surface
 * first regardless of source-array order.
 */
function buildKeywordRows(array $atsKeywords, int $highPriorityThreshold): array
{
    $rows = [];

    foreach ($atsKeywords['missing'] ?? [] as $item) {
        $rows[] = [
            'keyword'      => $item['keyword'],
            'jdFrequency'  => (int) $item['jdFrequency'],
            'resumeCount'  => 0,
            'highPriority' => (int) $item['jdFrequency'] >= $highPriorityThreshold,
            'statusLabel'  => sprintf(
                'Mentioned %dx in JD, not found in resume',
                (int) $item['jdFrequency']
            ),
        ];
    }

    foreach ($atsKeywords['underused'] ?? [] as $item) {
        $rows[] = [
            'keyword'      => $item['keyword'],
            'jdFrequency'  => (int) $item['jdFrequency'],
            'resumeCount'  => (int) $item['resumeCount'],
            'highPriority' => (int) $item['jdFrequency'] >= $highPriorityThreshold,
            'statusLabel'  => sprintf(
                'Mentioned %dx in JD, found %dx in resume',
                (int) $item['jdFrequency'],
                (int) $item['resumeCount']
            ),
        ];
    }

    usort($rows, fn($a, $b) => $b['jdFrequency'] <=> $a['jdFrequency']);

    return $rows;
}

$keywordRows = buildKeywordRows($atsKeywords, $keywordHighPriorityThreshold);

/**
 * Pill style per skill category — same "derive style from category/score"
 * pattern as scoreBarColor() in results.php, kept local to this partial
 * since it's the only place skill pills render.
 */
function skillPillClasses(string $category): string
{
    return match ($category) {
        'missingRequired'  => 'bg-red-100 text-red-700 border border-red-200',
        'matched'          => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
        'missingPreferred' => 'bg-amber-50 text-amber-700 border border-amber-300',
        default            => 'bg-gray-100 text-gray-700 border border-gray-200',
    };
}
?>
<section class="mt-4" aria-labelledby="skills-breakdown-heading">
    <h2 id="skills-breakdown-heading" class="text-lg sm:text-xl font-bold text-gray-900 mb-3">
        Skills Breakdown
    </h2>

    <!-- ============ SKILL PILL GROUPS ============ -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 mb-4">

        <!-- Missing Required Skills -->
        <div class="border border-gray-300 shadow-[0_2px_8px_rgba(30,64,175,0.06),0_12px_32px_rgba(30,64,175,0.10)] bg-white rounded-xl border border-gray-200/70 p-4 sm:p-5">
            <h3 class="text-xs font-semibold tracking-wide text-red-600 uppercase mb-3">
                Missing Required Skills
            </h3>
            <?php if (empty($skills['missingRequired'])): ?>
                <p class="text-sm text-gray-400 italic">None — all required skills matched.</p>
            <?php else: ?>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($skills['missingRequired'] as $skill): ?>
                        <span data-reveal class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= skillPillClasses('missingRequired') ?>">
                            <?= htmlspecialchars($skill) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Matched Skills -->
        <div class="border border-gray-300 shadow-[0_2px_8px_rgba(30,64,175,0.06),0_12px_32px_rgba(30,64,175,0.10)] bg-white rounded-xl border border-gray-200/70 p-4 sm:p-5">
            <h3 class="text-xs font-semibold tracking-wide text-emerald-600 uppercase mb-3">
                Matched Skills
            </h3>
            <?php if (empty($skills['matched'])): ?>
                <p class="text-sm text-gray-400 italic">No matched skills detected.</p>
            <?php else: ?>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($skills['matched'] as $skill): ?>
                        <span data-reveal class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= skillPillClasses('matched') ?>">
                            <?= htmlspecialchars($skill) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Missing Preferred Skills -->
        <div class="border border-gray-300 shadow-[0_2px_8px_rgba(30,64,175,0.06),0_12px_32px_rgba(30,64,175,0.10)] bg-white rounded-xl border border-gray-200/70 p-4 sm:p-5 sm:col-span-2 xl:col-span-1">
            <h3 class="text-xs font-semibold tracking-wide text-amber-600 uppercase mb-3">
                Missing Preferred Skills
            </h3>
            <?php if (empty($skills['missingPreferred'])): ?>
                <p class="text-sm text-gray-400 italic">None — all preferred skills matched.</p>
            <?php else: ?>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($skills['missingPreferred'] as $skill): ?>
                        <span data-reveal class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= skillPillClasses('missingPreferred') ?>">
                            <?= htmlspecialchars($skill) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ============ ATS KEYWORD ANALYSIS ============ -->
    <div class="border border-gray-300 shadow-[0_2px_8px_rgba(30,64,175,0.06),0_12px_32px_rgba(30,64,175,0.10)] bg-white rounded-xl border border-gray-200/70 p-4 sm:p-5">
        <h3 class="text-xs font-semibold tracking-wide text-gray-500 uppercase mb-3">
            ATS Keyword Analysis
        </h3>

        <?php if (empty($keywordRows)): ?>
            <p class="text-sm text-gray-400 italic">No keyword gaps detected.</p>
        <?php else: ?>
            <ul class="divide-y divide-gray-100">
                <?php foreach ($keywordRows as $row): ?>
                    <li data-reveal class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 sm:gap-4 py-3">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-mono text-sm text-gray-900">
                                <?= htmlspecialchars($row['keyword']) ?>
                            </span>
                            <?php if ($row['highPriority']): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold tracking-wide uppercase bg-lime-200 text-lime-900">
                                    High Priority
                                </span>
                            <?php endif; ?>
                        </div>
                        <span class="text-sm text-gray-500 sm:text-right">
                            <?= htmlspecialchars($row['statusLabel']) ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>