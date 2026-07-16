<?php
/**
 * partials/results-header.php
 *
 * Title row for the results page: job title/company, "checked at" timestamp,
 * and the re-run button.
 *
 * Expects (set by results.php before including this file):
 *   string $jobTitle
 *   string $company
 *   string $checkedAt
 */

// Fallback logic for the heading — handles all four combinations of
// jobTitle/company being present or empty, since the raw "X @ Y" format
// breaks visually (dangling "@") when either side is missing.
$hasTitle   = trim((string) $jobTitle) !== '';
$hasCompany = trim((string) $company) !== '';

if ($hasTitle && $hasCompany) {
    $headingText = htmlspecialchars($jobTitle) . ' @ ' . htmlspecialchars($company);
} elseif ($hasTitle) {
    $headingText = htmlspecialchars($jobTitle);
} elseif ($hasCompany) {
    $headingText = htmlspecialchars($company);
} else {
    $headingText = 'Match Results';
}
?>
<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-6 sm:mb-8">
    <div>
        <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 leading-tight">
            <?= $headingText ?>
        </h1>
        <p class="mt-1 flex items-center gap-1.5 text-sm text-gray-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
            </svg>
            <?= htmlspecialchars($checkedAt) ?>
        </p>
    </div>

    <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
        <a href="/" class="shrink-0 inline-flex items-center justify-center gap-2 w-full sm:w-auto rounded-full border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-50 hover:border-gray-400 active:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 111.414 1.414L7.414 9H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
            Analyze Another Resume
        </a>

        <button type="button" class="shrink-0 inline-flex items-center justify-center gap-2 w-full sm:w-auto rounded-full border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-50 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
        </svg>
            Re-run check
        </button>
    </div>
</div>