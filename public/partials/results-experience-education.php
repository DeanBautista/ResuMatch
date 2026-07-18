<?php
/**
 * partials/results-experience-education.php
 *
 * "Experience & Education" section — renders below the ATS Keyword
 * Analysis card (i.e. after results-skills-breakdown.php).
 *
 * Expects two variables already in scope (set in results.php, shaped to
 * match /api/analyze.php's parsed Gemini response 1:1):
 *
 *   $experience = [
 *       'requiredYears'       => <number|null>,
 *       'detectedYears'       => <number|null>,
 *       'experienceNotes'     => <string>,
 *       'relevantHighlights'  => [<string>, ...],
 *       'gaps'                => [<string>, ...],
 *   ];
 *
 *   $education = [
 *       'required'         => <string|null>,
 *       'detected'         => <string|null>,
 *       'meetsRequirement' => <bool|null>,
 *   ];
 *
 * Everything that looks conditional in the mock (red vs neutral "detected
 * years" text, the arrow glyph, whether "VERIFIED MATCH" shows) is derived
 * below from these two arrays — nothing is authored per instance. Swap in
 * the real decoded API response and this partial needs no changes.
 *
 * Layout: a single markup tree handles desktop, tablet, AND mobile via
 * Tailwind responsive classes (mobile-first: base styles = mobile, `sm:`
 * kicks in for tablet, `lg:` for desktop two-column). No separate
 * duplicated mobile markup block — one DOM, breakpoints reshape it.
 */

/**
 * Format a years value for display. Returns null-safe strings so the UI
 * never prints "null years" if the model couldn't determine a figure.
 */
function formatYears($years): string
{
    if ($years === null) {
        return '—';
    }
    // Trim trailing .0 (e.g. 4.0 -> "4 years", 4.5 -> "4.5 years")
    $formatted = (floor($years) == $years) ? (string) (int) $years : (string) $years;
    return $formatted . ($formatted === '1' ? ' year' : ' years');
}

/**
 * Whether the detected experience falls short of what's required.
 * Null-safe: if either value is unknown, we can't claim a shortfall.
 */
function experienceFallsShort($detectedYears, $requiredYears): bool
{
    if ($detectedYears === null || $requiredYears === null) {
        return false;
    }
    return $detectedYears < $requiredYears;
}

$requiredYearsLabel = $experience['requiredYears'] !== null
    ? formatYears($experience['requiredYears']) . '+'
    : 'Not specified';

$detectedYearsLabel = $experience['detectedYears'] !== null
    ? '~' . formatYears($experience['detectedYears'])
    : 'Undetermined';

$experienceShortfall = experienceFallsShort($experience['detectedYears'], $experience['requiredYears']);

// Education card only renders its verification state when the JD actually
// specifies a requirement — mirrors analyze.php's educationApplicable flag.
$educationApplicable = !empty($education['required']);
?>
<section class="mt-4" aria-labelledby="experience-education-heading">
    <h2 id="experience-education-heading" class="text-lg sm:text-xl font-bold text-gray-900 mb-3">
        Experience &amp; Education
    </h2>

    <div class="grid grid-cols-1 lg:grid-cols-[calc(65%-1rem)_35%] gap-4">

        <!-- ============ EXPERIENCE CARD ============ -->
        <div class="border border-gray-300 shadow-[0_2px_8px_rgba(30,64,175,0.06),0_12px_32px_rgba(30,64,175,0.10)] bg-white rounded-xl p-4 sm:p-5">

            <!-- Required -> Detected comparison strip -->
            <div class="bg-gray-50 rounded-lg px-4 py-5 sm:py-6 mb-5">
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-8">
                    <div class="text-center">
                        <p class="text-[11px] font-semibold tracking-wide text-gray-400 uppercase mb-1">
                            Required
                        </p>
                        <p class="text-lg sm:text-xl font-bold text-gray-900">
                            <?= htmlspecialchars($requiredYearsLabel) ?>
                        </p>
                    </div>

                    <!-- Arrow: down on mobile (stacked), right on tablet/desktop (inline) -->
                    <span class="text-gray-400 text-xl leading-none" aria-hidden="true">
                        <span class="inline sm:hidden">↓</span>
                        <span class="hidden sm:inline">→</span>
                    </span>

                    <div class="text-center">
                        <p class="text-[11px] font-semibold tracking-wide text-gray-400 uppercase mb-1">
                            Detected
                        </p>
                        <p class="text-lg sm:text-xl font-bold <?= $experienceShortfall ? 'text-red-500' : 'text-gray-900' ?>">
                            <?= htmlspecialchars($detectedYearsLabel) ?>
                        </p>
                    </div>
                </div>

                <?php if ($experience['detectedYears'] === null && !empty($experience['experienceNotes'])): ?>
                    <p class="text-xs text-gray-400 text-center mt-3 italic">
                        <?= htmlspecialchars($experience['experienceNotes']) ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Highlights + Gaps -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="inline-flex items-center justify-center w-4 h-4 rounded-full border-2 border-emerald-500 text-emerald-500 text-[10px] leading-none" aria-hidden="true">✓</span>
                        <h3 class="text-sm font-semibold text-gray-800">Highlights</h3>
                    </div>
                    <?php if (empty($experience['relevantHighlights'])): ?>
                        <p class="text-sm text-gray-400 italic">No specific highlights detected.</p>
                    <?php else: ?>
                        <ul class="space-y-1.5">
                            <?php foreach ($experience['relevantHighlights'] as $item): ?>
                                <li data-reveal class="text-sm text-blue-700 flex gap-1.5">
                                    <span class="text-gray-300" aria-hidden="true">•</span>
                                    <span><?= htmlspecialchars($item) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="inline-flex items-center justify-center w-4 h-4 rounded-full border-2 border-orange-500 text-orange-500 text-[10px] leading-none" aria-hidden="true">!</span>
                        <h3 class="text-sm font-semibold text-gray-800">Gaps</h3>
                    </div>
                    <?php if (empty($experience['gaps'])): ?>
                        <p class="text-sm text-gray-400 italic">No gaps detected.</p>
                    <?php else: ?>
                        <ul class="space-y-1.5">
                            <?php foreach ($experience['gaps'] as $item): ?>
                                <li data-reveal class="text-sm text-blue-700 flex gap-1.5">
                                    <span class="text-gray-300" aria-hidden="true">•</span>
                                    <span><?= htmlspecialchars($item) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ============ EDUCATION MATCH CARD ============ -->
        <div class="border border-gray-300 shadow-[0_2px_8px_rgba(30,64,175,0.06),0_12px_32px_rgba(30,64,175,0.10)] bg-white rounded-xl p-4 sm:p-5">
            <h3 class="text-xs font-semibold tracking-wide text-orange-600 uppercase mb-3">
                Education Match
            </h3>

            <?php if (!$educationApplicable): ?>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-400 italic">
                        The job description does not specify an education requirement.
                    </p>
                </div>
            <?php else: ?>
                <div class="bg-lime-50 rounded-lg p-4">
                    <p class="text-[11px] font-semibold tracking-wide text-gray-400 uppercase mb-1">
                        JD Requires
                    </p>
                    <p class="text-sm font-bold text-gray-900 mb-3">
                        <?= htmlspecialchars($education['required']) ?>
                    </p>

                    <hr class="border-lime-200 mb-3">

                    <p class="text-[11px] font-semibold tracking-wide text-gray-400 uppercase mb-1">
                        Resume Shows
                    </p>
                    <p class="text-sm font-bold text-gray-900 mb-3">
                        <?= htmlspecialchars($education['detected'] ?? 'Not stated'); ?>
                    </p>

                    <?php if ($education['meetsRequirement'] === true): ?>
                        <div class="flex items-center gap-1.5 text-emerald-700">
                            <span class="inline-flex items-center justify-center w-3.5 h-3.5 rounded-full border-2 border-emerald-600 text-[9px] leading-none" aria-hidden="true">✓</span>
                            <span class="text-xs font-semibold tracking-wide uppercase">Verified Match</span>
                        </div>
                    <?php elseif ($education['meetsRequirement'] === false): ?>
                        <div class="flex items-center gap-1.5 text-red-600">
                            <span class="inline-flex items-center justify-center w-3.5 h-3.5 rounded-full border-2 border-red-600 text-[9px] leading-none" aria-hidden="true">!</span>
                            <span class="text-xs font-semibold tracking-wide uppercase">Requirement Not Met</span>
                        </div>
                    <?php else: ?>
                        <div class="flex items-center gap-1.5 text-gray-500">
                            <span class="inline-flex items-center justify-center w-3.5 h-3.5 rounded-full border-2 border-gray-400 text-[9px] leading-none" aria-hidden="true">?</span>
                            <span class="text-xs font-semibold tracking-wide uppercase">Unable To Verify</span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>