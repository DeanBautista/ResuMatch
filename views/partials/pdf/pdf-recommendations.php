<?php
/**
 * partials/pdf/pdf-recommendations.php
 *
 * dompdf-safe re-render of results-recommendations.php.
 *
 * Deliberately omits:
 *   - The "Copy" button per recommendation row (no JS in a static PDF)
 *   - The "Save to History" / "Export as PDF" page-action buttons
 * per explicit scope decision — these are UI-only actions, not content.
 *
 * Expects the same variables already in scope from export-pdf.php:
 *   $recommendations, $formattingIssues
 */
?>
<h2 class="pdf-section-heading">Actionable Recommendations</h2>

<?php if (empty($recommendations)): ?>
    <p class="muted-italic">No specific recommendations generated.</p>
<?php else: ?>
    <table class="reco-table" cellspacing="0" cellpadding="0">
        <?php foreach ($recommendations as $i => $rec): ?>
            <tr>
                <td class="reco-num-cell"><span class="reco-num"><span class="reco-num-inner"><?= (int) $i + 1 ?></span></span></td>
                <td class="reco-action"><?= htmlspecialchars($rec['action']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<h3 class="pdf-subheading">Resume Formatting &amp; ATS-Friendliness</h3>

<?php if (empty($formattingIssues)): ?>
    <p class="muted-italic">No formatting issues detected.</p>
<?php else: ?>
    <table class="formatting-table" cellspacing="0" cellpadding="0">
        <?php foreach ($formattingIssues as $issue): ?>
            <?php $style = formattingIssueStyle($issue['severity']); ?>
            <tr>
                <td class="issue-icon-cell">
                    <span class="issue-icon <?= $style['pdfClass'] ?>"><?= $style['icon'] ?></span>
                </td>
                <td class="issue-message"><?= htmlspecialchars($issue['message']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>