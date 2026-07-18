<?php
/**
 * partials/pdf/pdf-experience-education.php
 *
 * dompdf-safe re-render of results-experience-education.php.
 *
 * Expects the same variables already in scope from export-pdf.php:
 *   $experience, $education
 */
?>
<h2 class="pdf-section-heading">Experience &amp; Education</h2>

<table class="section-table" cellspacing="0" cellpadding="0">
    <tr>
        <td class="exp-col">
            <div class="years-row">
                <div class="years-box">
                    <p class="years-label">REQUIRED</p>
                    <p class="years-value"><?= htmlspecialchars((string) $experience['requiredYears']) ?>+</p>
                </div>
                <div class="years-arrow">&rarr;</div>
                <div class="years-box">
                    <p class="years-label">DETECTED</p>
                    <p class="years-value">~<?= htmlspecialchars((string) $experience['detectedYears']) ?></p>
                </div>
            </div>

            <?php if (!empty($experience['experienceNotes'])): ?>
                <p class="exp-notes"><?= htmlspecialchars($experience['experienceNotes']) ?></p>
            <?php endif; ?>

            <div class="highlight-gaps-row">
                <div class="highlight-col-div">
                    <p class="mini-heading highlights-heading">Highlights</p>
                    <ul class="mini-list">
                        <?php foreach ($experience['relevantHighlights'] as $h): ?>
                            <li class="mini-item highlight-item"><?= htmlspecialchars($h) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="highlight-col-div">
                    <p class="mini-heading exp-gaps-heading">Gaps</p>
                    <ul class="mini-list">
                        <?php foreach ($experience['gaps'] as $g): ?>
                            <li class="mini-item gap-item"><?= htmlspecialchars($g) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </td>

        <td class="edu-col">
            <p class="edu-heading">EDUCATION MATCH</p>
            <p class="edu-sub-label">JD REQUIRES</p>
            <p class="edu-value"><?= htmlspecialchars($education['required']) ?></p>
            <p class="edu-sub-label">RESUME SHOWS</p>
            <p class="edu-value"><?= htmlspecialchars($education['detected']) ?></p>
            <?php if ($education['meetsRequirement']): ?>
                <span class="badge-verified">VERIFIED MATCH</span>
            <?php else: ?>
                <span class="badge-not-met">NOT MET</span>
            <?php endif; ?>
        </td>
    </tr>
</table>