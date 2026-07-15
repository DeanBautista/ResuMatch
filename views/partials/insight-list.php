<?php
/**
 * partials/insight-list.php
 *
 * Reusable "icon + heading + bulleted list" card. Used for both the
 * Strengths list and the Gaps to Address list on the results page — they
 * share the exact same structure and only differ in heading text, icon
 * variant, and item data.
 *
 * Expects (set by the including file before including this file):
 *   string $listVariant   'strengths' | 'gaps'  — controls icons/colors
 *   string $listHeading   e.g. 'Strengths' or 'Gaps to Address'
 *   array  $listItems     array of strings (may contain <strong> tags,
 *                          must be pre-sanitized before reaching this file)
 *
 * Renders nothing if $listItems is empty, so the caller doesn't need to
 * wrap the include in its own empty-check.
 *
 * IMPORTANT: because $listVariant/$listHeading/$listItems are plain global
 * variables (not function params), reset or overwrite them before each
 * include if you use this partial more than once per page (see
 * results.php for the pattern).
 */

if (empty($listItems)) {
    return;
}

// Icon + color config per variant. Add a new variant here if a third kind
// of insight list is ever needed (e.g. "Suggestions").
$variants = [
    'strengths' => [
        'headingIconColor' => 'text-gray-400',
        'headingIcon' => '<path d="M1 8.25a1.25 1.25 0 112.5 0v7.5a1.25 1.25 0 11-2.5 0v-7.5zM11 3V1.7c0-.268.14-.526.395-.607A2 2 0 0114 3c0 .995-.182 1.948-.514 2.826-.204.54.166 1.174.744 1.174h2.52c1.243 0 2.261 1.01 2.146 2.247a23.864 23.864 0 01-1.341 5.974C17.153 16.323 16.072 17 14.9 17h-3.192a3 3 0 01-1.341-.317l-2.734-1.366A3 3 0 006.292 15H5V8h.963c.685 0 1.258-.483 1.612-1.068a4.011 4.011 0 012.166-1.73c.432-.143.853-.386 1.011-.814.16-.432.248-.9.248-1.388z" />',
        'itemIconColor' => 'text-green-500',
        'itemIcon' => '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />',
    ],
    'gaps' => [
        'headingIconColor' => 'text-orange-400',
        'headingIcon' => '<path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />',
        'itemIconColor' => 'text-red-400',
        'itemIcon' => '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />',
    ],
];

$style = $variants[$listVariant] ?? $variants['strengths'];
?>
<div class="h-full bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex flex-col">
    <p class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-gray-500 mb-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 <?= $style['headingIconColor'] ?>" viewBox="0 0 20 20" fill="currentColor">
            <?= $style['headingIcon'] ?>
        </svg>
        <?= htmlspecialchars($listHeading) ?>
    </p>
    <ul class="space-y-2.5">
        <?php foreach ($listItems as $item): ?>
            <li data-reveal class="flex items-start gap-2 text-sm text-gray-700 leading-relaxed">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 mt-0.5 <?= $style['itemIconColor'] ?>" viewBox="0 0 20 20" fill="currentColor">
                    <?= $style['itemIcon'] ?>
                </svg>
                <span><?= $item /* pre-sanitized server-side before storage */ ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
</div>