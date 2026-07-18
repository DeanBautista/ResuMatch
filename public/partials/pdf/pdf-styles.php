<?php
/**
 * partials/pdf/pdf-styles.php
 *
 * dompdf-compatible CSS. Deliberately avoids:
 *   - flexbox / grid          -> uses <table> layouts instead
 *   - CSS gradients            -> flat background colors instead
 *   - multi-layer box-shadow   -> single-layer, or plain borders instead
 *   - Tailwind CDN (JIT @ runtime -> not available to dompdf at all)
 *
 * Color values are copied from the Tailwind classes used in the live
 * partials (e.g. text-green-700, bg-yellow-200, border-gray-300) so the
 * palette matches even though the box model doesn't render identically.
 */
?>
<style>
    @page {
        margin: 28px 32px;
    }
    body {
        font-family: DejaVu Sans, sans-serif;
        color: #111827; /* gray-900 */
        font-size: 11px;
    }
    h1, h2, h3, p, ul, li, table { margin: 0; padding: 0; }

    .pdf-header {
        margin-bottom: 14px;
    }
    .pdf-header .job-title {
        font-size: 18px;
        font-weight: bold;
        color: #111827;
    }
    .pdf-header .job-meta {
        font-size: 10px;
        color: #6b7280; /* gray-500 */
        margin-top: 2px;
    }

    .pdf-section-heading {
        font-size: 14px;
        font-weight: bold;
        color: #111827;
        margin-top: 18px;
        margin-bottom: 8px;
    }
    .pdf-subheading {
        font-size: 10px;
        font-weight: bold;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        color: #6b7280;
        margin-top: 14px;
        margin-bottom: 6px;
    }
    .muted-italic {
        color: #9ca3af; /* gray-400 */
        font-style: italic;
    }

    /* ---- shared card look: single border, no shadow stacking ---- */
    .section-table, .breakdown-table, .ats-table, .reco-table, .formatting-table, .years-table {
        width: 100%;
        border: 1px solid #d1d5db; /* gray-300 */
        border-radius: 6px;
        margin-bottom: 12px;
    }
    .section-table td, .breakdown-table td { padding: 10px 12px; vertical-align: top; }

    /* ---- score + breakdown ---- */
    .score-col { width: 35%; text-align: center; border-right: 1px solid #e5e7eb; }
    .summary-col { width: 65%; }
    .score-circle-wrap {
        position: relative;
        width: 70px; height: 70px;
        margin: 0 auto;
    }
    .score-circle {
        position: absolute;
        top: 0; left: 0;
        width: 58px; height: 58px;
        border: 6px solid #d97706; /* amber-600, approximates conic ring */
        border-radius: 50%;
    }
    /* dompdf's line-height/table-display centering inside bordered circles
       is unreliable across nested box types. Absolute-positioning the text
       over the circle, both same fixed size, is the most robust way to
       guarantee overlap in dompdf. */
    .score-num {
        position: absolute;
        top: 24px; left: 0;
        width: 70px;
        text-align: center;
        font-size: 16px; font-weight: bold;
        line-height: 1;
    }
    .verdict-badge {
        /* dompdf does not reliably support width: fit-content. Using
           display: table centers the box via margin:auto while sizing
           it to its content, similar effect to fit-content in browsers. */
        display: table;
        margin: 8px auto 0;
        padding: 3px 10px;
        border-radius: 10px;
        font-size: 9px;
        font-weight: bold;
        white-space: nowrap;
    }
    .verdict-strong   { background: #dcfce7; color: #15803d; }
    .verdict-moderate { background: #fef08a; color: #713f12; }
    .verdict-weak     { background: #fed7aa; color: #7c2d12; }
    .verdict-poor     { background: #fecaca; color: #7f1d1d; }

    .ai-label { font-size: 9px; font-weight: bold; color: #ea580c; letter-spacing: 0.5px; margin-bottom: 4px; }
    .ai-summary { font-size: 11px; line-height: 1.5; color: #1f2937; }

    .breakdown-heading { font-size: 9px; font-weight: bold; color: #9ca3af; letter-spacing: 0.5px; padding-bottom: 6px; }
    .breakdown-label { width: 28%; font-size: 10px; color: #374151; padding: 4px 12px; }
    .breakdown-bar-cell { width: 72%; padding: 4px 12px; }
    .bar-outer { width: 85%; height: 6px; background: #f3f4f6; border-radius: 4px; display: inline-block; }
    .bar-fill-div { height: 6px; border-radius: 4px; }
    .bar-green  { background: #22c55e; }
    .bar-orange { background: #fb923c; }
    .bar-red    { background: #ef4444; }
    .bar-pct { font-size: 10px; color: #374151; padding-left: 6px; }

    /* ---- strengths / gaps ---- */
    .insight-col { width: 50%; padding: 10px 12px; vertical-align: top; }
    .insight-heading { font-size: 10px; font-weight: bold; letter-spacing: 0.4px; margin-bottom: 6px; }
    .strengths-heading { color: #15803d; }
    .gaps-heading { color: #c2410c; }
    .insight-list { margin-left: 14px; }
    .insight-item { font-size: 10.5px; line-height: 1.6; margin-bottom: 3px; }

    /* ---- skills pills ---- */
    .skill-col { width: 33.33%; padding: 10px 12px; vertical-align: top; }
    .skill-col-heading { font-size: 9px; font-weight: bold; letter-spacing: 0.4px; margin-bottom: 6px; }
    .missing-required-heading { color: #b91c1c; }
    .matched-heading { color: #15803d; }
    .missing-preferred-heading { color: #b45309; }
    .pill {
        display: inline-block;
        font-size: 9px;
        padding: 2px 8px;
        border-radius: 10px;
        margin: 0 4px 4px 0;
        border: 1px solid;
    }
    .pill-missing-required  { background: #fee2e2; color: #b91c1c; border-color: #fecaca; }
    .pill-matched           { background: #dcfce7; color: #15803d; border-color: #bbf7d0; }
    .pill-missing-preferred { background: #fef3c7; color: #b45309; border-color: #fde68a; }

    /* ---- ATS keyword table ---- */
    .ats-heading { font-size: 9px; font-weight: bold; color: #9ca3af; letter-spacing: 0.5px; padding: 10px 12px 4px; }
    .ats-keyword { font-size: 10.5px; padding: 5px 12px; border-top: 1px solid #f3f4f6; }
    .ats-detail { font-size: 9.5px; color: #9ca3af; text-align: right; padding: 5px 12px; border-top: 1px solid #f3f4f6; }
    .badge-high-priority {
        font-size: 8px; font-weight: bold; color: #b91c1c;
        background: #fee2e2; padding: 1px 6px; border-radius: 8px; margin-left: 6px;
    }

    /* ---- experience / education ---- */
    .exp-col { width: 65%; padding: 12px; vertical-align: top; border-right: 1px solid #e5e7eb; }
    .edu-col { width: 35%; padding: 12px; vertical-align: top; background: #ecfccb; }
    .years-row { margin-bottom: 8px; }
    .years-box { display: inline-block; width: 38%; text-align: center; }
    .years-arrow { display: inline-block; width: 18%; text-align: center; font-size: 14px; color: #9ca3af; }
    .years-label { font-size: 8px; color: #9ca3af; letter-spacing: 0.4px; }
    .years-value { font-size: 13px; font-weight: bold; color: #111827; }
    .exp-notes { font-size: 10px; color: #4b5563; margin: 6px 0; }
    .highlight-gaps-row { margin-top: 6px; }
    .highlight-col-div { display: inline-block; width: 48%; vertical-align: top; }
    .mini-heading { font-size: 9px; font-weight: bold; margin-bottom: 4px; }
    .highlights-heading { color: #15803d; }
    .exp-gaps-heading { color: #c2410c; }
    .mini-list { margin-left: 12px; }
    .mini-item { font-size: 9.5px; line-height: 1.5; color: #374151; }

    .edu-heading { font-size: 9px; font-weight: bold; color: #b45309; letter-spacing: 0.4px; margin-bottom: 8px; }
    .edu-sub-label { font-size: 8px; color: #6b7280; margin-top: 6px; }
    .edu-value { font-size: 10.5px; font-weight: bold; color: #1f2937; }
    .badge-verified {
        display: inline-block; margin-top: 8px; font-size: 8px; font-weight: bold;
        color: #15803d; background: #dcfce7; padding: 2px 8px; border-radius: 8px;
    }
    .badge-not-met {
        display: inline-block; margin-top: 8px; font-size: 8px; font-weight: bold;
        color: #b91c1c; background: #fee2e2; padding: 2px 8px; border-radius: 8px;
    }

    /* ---- recommendations ---- */
    .reco-table td { padding: 8px 12px; border-top: 1px solid #f3f4f6; font-size: 10.5px; vertical-align: middle; }
    .reco-num-cell { width: 28px; }
    .reco-num {
        display: table; width: 16px; height: 16px;
        background: #111827; border-radius: 50%;
    }
    .reco-num-inner {
        display: table-cell; vertical-align: middle; text-align: center;
        color: #fff; font-size: 9px; font-weight: bold;
    }
    .reco-action { color: #1e40af; padding-left: 6px; }

    /* ---- formatting issues ---- */
    .formatting-table td { padding: 6px 12px; font-size: 10px; vertical-align: top; }
    .issue-icon-cell { width: 20px; }
    .issue-icon {
        display: inline-block; width: 12px; height: 12px; line-height: 10px; text-align: center;
        border: 1.5px solid; border-radius: 50%; font-size: 8px; font-weight: bold;
    }
    .issue-warning { color: #ef4444; border-color: #ef4444; }
    .issue-info    { color: #9ca3af; border-color: #9ca3af; }
    .issue-message { color: #4b5563; }

    .pdf-footer {
        margin-top: 20px;
        padding-top: 8px;
        border-top: 1px solid #e5e7eb;
        font-size: 8px;
        color: #9ca3af;
        text-align: center;
    }
</style>