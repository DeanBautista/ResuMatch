<?php
/**
 * partials/pdf/pdf-strengths-gaps-skills.php
 *
 * dompdf-safe re-render of insight-list.php (strengths + gaps) and
 * results-skills-breakdown.php (matched/missing skill pills + ATS
 * keyword rows).
 *
 * Expects the same variables already in scope from export-pdf.php:
 *   $strengths, $gaps, $skills, $atsKeywords, $keywordHighPriorityThreshold
 */
?>
<table class="section-table" cellspacing="0" cellpadding="0">
    <tr>
        <td class="insight-col">
            <p class="insight-heading strengths-heading">STRENGTHS</p>
            <?php if (empty($strengths)): ?>
                <p class="muted-italic">No specific strengths listed.</p>
            <?php else: ?>
                <ul class="insight-list">
                    <?php foreach ($strengths as $item): ?>
                        <li class="insight-item strengths-item"><?= $item /* pre-sanitized upstream */ ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </td>
        <td class="insight-col">
            <p class="insight-heading gaps-heading">GAPS TO ADDRESS</p>
            <?php if (empty($gaps)): ?>
                <p class="muted-italic">No specific gaps listed.</p>
            <?php else: ?>
                <ul class="insight-list">
                    <?php foreach ($gaps as $item): ?>
                        <li class="insight-item gaps-item"><?= $item /* pre-sanitized upstream */ ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </td>
    </tr>
</table>

<h2 class="pdf-section-heading">Skills Breakdown</h2>

<table class="section-table" cellspacing="0" cellpadding="0">
    <tr>
        <td class="skill-col">
            <p class="skill-col-heading missing-required-heading">MISSING REQUIRED SKILLS</p>
            <?php foreach ($skills['missingRequired'] as $s): ?>
                <span class="pill pill-missing-required"><?= htmlspecialchars($s) ?></span>
            <?php endforeach; ?>
        </td>
        <td class="skill-col">
            <p class="skill-col-heading matched-heading">MATCHED SKILLS</p>
            <?php foreach ($skills['matched'] as $s): ?>
                <span class="pill pill-matched"><?= htmlspecialchars($s) ?></span>
            <?php endforeach; ?>
        </td>
        <td class="skill-col">
            <p class="skill-col-heading missing-preferred-heading">MISSING PREFERRED SKILLS</p>
            <?php foreach ($skills['missingPreferred'] as $s): ?>
                <span class="pill pill-missing-preferred"><?= htmlspecialchars($s) ?></span>
            <?php endforeach; ?>
        </td>
    </tr>
</table>

<table class="ats-table" cellspacing="0" cellpadding="0">
    <tr><td colspan="2" class="ats-heading">ATS KEYWORD ANALYSIS</td></tr>
    <?php foreach ($atsKeywords['missing'] as $kw): ?>
        <tr>
            <td class="ats-keyword">
                <?= htmlspecialchars($kw['keyword']) ?>
                <?php if (($kw['jdFrequency'] ?? 0) >= $keywordHighPriorityThreshold): ?>
                    <span class="badge-high-priority">HIGH PRIORITY</span>
                <?php endif; ?>
            </td>
            <td class="ats-detail">
                Mentioned <?= (int) ($kw['jdFrequency'] ?? 0) ?>x in JD, not found in resume
            </td>
        </tr>
    <?php endforeach; ?>
    <?php foreach ($atsKeywords['underused'] as $kw): ?>
        <tr>
            <td class="ats-keyword"><?= htmlspecialchars($kw['keyword']) ?></td>
            <td class="ats-detail">
                Mentioned <?= (int) ($kw['jdFrequency'] ?? 0) ?>x in JD, found <?= (int) ($kw['resumeCount'] ?? 0) ?>x in resume
            </td>
        </tr>
    <?php endforeach; ?>
</table>