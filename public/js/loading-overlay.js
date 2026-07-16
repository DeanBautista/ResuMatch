/**
 * loading-overlay.js
 * Controls the full-screen #loadingOverlay partial (views/partials/loading-overlay.php).
 * Exposes window.MatchAI.loading.{show, hide, setPhase} for other scripts to call.
 */

window.MatchAI = window.MatchAI || {};

(function () {
  "use strict";

  const overlay = document.getElementById("loadingOverlay");
  if (!overlay) {
    console.warn("[loading-overlay] #loadingOverlay not found on page.");
    return;
  }

  const messageEl = document.getElementById("loadingMessage");
  const barEl = document.getElementById("loadingBar");
  const phaseEl = document.getElementById("loadingPhase");
  const checkEl = overlay.querySelector(".checkmark-path");
  const iconPathEl = document.getElementById("loadingIconPath");  

  const PHASES = [
    { label: "Reading your resume…", pct: 20 },
    { label: "Scanning for ATS keywords…", pct: 50 },
    { label: "Comparing against the job description…", pct: 75 },
    { label: "Finalizing your match score…", pct: 100 },
  ];

  let phaseIndex = 0;
  let lockScrollY = 0;

  function setPhase(index) {
    phaseIndex = Math.max(0, Math.min(index, PHASES.length - 1));
    const phase = PHASES[phaseIndex];

    messageEl.classList.add("is-fading");
    window.setTimeout(() => {
      messageEl.textContent = phase.label;
      messageEl.classList.remove("is-fading");
    }, 180);

    barEl.style.width = phase.pct + "%";
    phaseEl.textContent = `Phase ${phaseIndex + 1}/${PHASES.length}`;

    if (phaseIndex === PHASES.length - 1) {
      checkEl && checkEl.classList.add("is-visible");
      iconPathEl && iconPathEl.classList.add("is-hidden");
    } else {
      checkEl && checkEl.classList.remove("is-visible");
      iconPathEl && iconPathEl.classList.remove("is-hidden");
    }
  }

  function show() {
    phaseIndex = 0;
    checkEl && checkEl.classList.remove("is-visible");
    iconPathEl && iconPathEl.classList.remove("is-hidden");
    barEl.style.width = "0%";
    messageEl.textContent = PHASES[0].label;
    phaseEl.textContent = `Phase 1/${PHASES.length}`;

    lockScrollY = window.scrollY;
    document.body.style.position = "fixed";
    document.body.style.top = `-${lockScrollY}px`;
    document.body.style.left = "0";
    document.body.style.right = "0";

    overlay.classList.remove("hidden", "is-closing");
    overlay.classList.add("flex");
  }

  function hide() {
    overlay.classList.add("is-closing");
    window.setTimeout(() => {
      overlay.classList.add("hidden");
      overlay.classList.remove("flex", "is-closing");

      document.body.style.position = "";
      document.body.style.top = "";
      document.body.style.left = "";
      document.body.style.right = "";
      window.scrollTo(0, lockScrollY);
    }, 250);
  }

  window.MatchAI.loading = { show, hide, setPhase, PHASES };
})();