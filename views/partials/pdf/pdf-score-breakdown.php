<?php
/**
 * partials/pdf/pdf-score-breakdown.php
 *
 * dompdf-safe re-render of results-score-card.php + results-breakdown.php.
 * No flexbox, no CSS gradients, no box-shadow with multiple layers —
 * dompdf only reliably supports: block/inline-block, floats, tables,
 * solid backgrounds, single-layer borders/shadows.
 *
 * Expects the same variables already in scope from export-pdf.php:
 *   $matchScore, $verdict, $verdictStyle, $summary, $breakdownRows
 */
?>
<table class="section-table" cellspacing="0" cellpadding="0">
    <tr>
        <td class="score-col">
            <div class="score-circle-wrap">
                <div class="score-circle"></div>
                <span class="score-num"><?= (int) $matchScore ?>%</span>
            </div>
            <div class="verdict-badge <?= $verdictStyle['pdfClass'] ?>">
                <?= htmlspecialchars(strtoupper($verdict)) ?>
            </div>
        </td>
        <td class="summary-col">
            <p class="ai-label">AI ANALYSIS</p>
            <p class="ai-summary"><?= $summary /* pre-sanitized upstream, same as results.php */ ?></p>
        </td>
    </tr>
</table>

<table class="breakdown-table" cellspacing="0" cellpadding="0">
    <tr><td colspan="2" class="breakdown-heading">BREAKDOWN</td></tr>
    <?php foreach ($breakdownRows as $row): ?>
        <?php $pct = max(0, min(100, (int) $row['value'])); ?>
        <tr>
            <td class="breakdown-label"><?= htmlspecialchars($row['label']) ?></td>
            <td class="breakdown-bar-cell">
                <div class="bar-outer">
                    <div class="bar-fill-div <?= scoreBarColorPdf($pct) ?>" style="width: <?= $pct ?>%;"></div>
                </div>
                <span class="bar-pct"><?= $pct ?>%</span>
            </td>
        </tr>
    <?php endforeach; ?>
</table>