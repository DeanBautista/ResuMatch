/**
 * resume-extract.js
 *
 * Client-side text extraction for uploaded resumes (PDF or DOCX).
 * On "Analyze Match" click, this reads the uploaded file, extracts
 * plain text using PDF.js (for .pdf) or Mammoth.js (for .docx),
 * and stores the result on `window.MatchAI.resumeText` for you to
 * send to Gemini (or wherever) afterward.
 *
 * Depends on:
 *   - pdf.js         (window.pdfjsLib)
 *   - mammoth.js      (window.mammoth)
 *   - loading-overlay.js  (window.MatchAI.loading) — full-screen loading UI
 * Loaded via CDN <script> tags — see snippet at bottom of match.html.
 */

window.MatchAI = window.MatchAI || {};

(function () {
  "use strict";

  // Holds the most recently extracted resume text + metadata.
  // Use this from your own Gemini-calling code, e.g.:
  //   const { resumeText, jobDescription } = window.MatchAI;
  window.MatchAI.resumeText = "";
  window.MatchAI.resumeFileMeta = null;
  window.MatchAI.jobDescription = "";

  // ---- DOM refs (match the IDs already in match.html) ----
  const analyzeBtn = document.querySelector("main button.bg-gray-900");
  const resumeInput = document.getElementById("resumeInput");
  const jdTextarea = document.querySelector("main textarea");

  if (!analyzeBtn || !resumeInput) {
    console.warn("[resume-extract] Required elements not found on page.");
    return;
  }

  // ---- Helpers ----

  function getFile() {
    return resumeInput.files && resumeInput.files[0]
      ? resumeInput.files[0]
      : null;
  }

  function readFileAsArrayBuffer(file) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = () => resolve(reader.result);
      reader.onerror = () => reject(reader.error || new Error("File read failed"));
      reader.readAsArrayBuffer(file);
    });
  }

  async function extractPdfText(arrayBuffer) {
    if (!window.pdfjsLib) {
      throw new Error("pdf.js not loaded (window.pdfjsLib missing)");
    }
    const loadingTask = window.pdfjsLib.getDocument({ data: arrayBuffer });
    const pdf = await loadingTask.promise;

    const pageTexts = [];
    for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
      const page = await pdf.getPage(pageNum);
      const content = await page.getTextContent();
      const strings = content.items.map((item) => item.str);
      pageTexts.push(strings.join(" "));
    }
    return pageTexts.join("\n\n").trim();
  }

  async function extractDocxText(arrayBuffer) {
    if (!window.mammoth) {
      throw new Error("mammoth.js not loaded (window.mammoth missing)");
    }
    const result = await window.mammoth.extractRawText({ arrayBuffer });
    return (result.value || "").trim();
  }

  function showToast(message, type = "info") {
    // Uses your existing toast.js if available, otherwise falls back to alert.
    if (typeof window.showToast === "function") {
      window.showToast(message, type);
    } else {
      console[type === "error" ? "error" : "log"]("[resume-extract]", message);
      if (type === "error") alert(message);
    }
  }

  // Small helper so fast phases are still perceivable (avoids a jarring flash).
  function wait(ms) {
    return new Promise((resolve) => window.setTimeout(resolve, ms));
  }

  // ---- Main flow ----

  async function handleAnalyzeClick(event) {
    event.preventDefault();

    const file = getFile();
    const jobDescription = jdTextarea ? jdTextarea.value.trim() : "";

    if (!file) {
      showToast("Please upload a resume (PDF or DOCX) first.", "error");
      return;
    }
    if (!jobDescription) {
      showToast("Please paste the job description first.", "error");
      return;
    }

    const isPdf =
      file.type === "application/pdf" || /\.pdf$/i.test(file.name);
    const isDocx =
      file.type ===
        "application/vnd.openxmlformats-officedocument.wordprocessingml.document" ||
      /\.docx$/i.test(file.name);

    if (!isPdf && !isDocx) {
      showToast("Unsupported file type. Please upload a PDF or DOCX.", "error");
      return;
    }

    const loading = window.MatchAI.loading;
    loading && loading.show();
    analyzeBtn.disabled = true;
    analyzeBtn.classList.add("opacity-70", "cursor-not-allowed");

    try {
      // Phase 1/4 — Reading your resume…
      loading && loading.setPhase(0);
      const arrayBuffer = await readFileAsArrayBuffer(file);
      const text = isPdf
        ? await extractPdfText(arrayBuffer)
        : await extractDocxText(arrayBuffer);

      if (!text) {
        throw new Error(
          "No text could be extracted. The file may be scanned/image-based."
        );
      }

      // Store for later use (e.g. sending to Gemini)
      window.MatchAI.resumeText = text;
      window.MatchAI.jobDescription = jobDescription;
      window.MatchAI.resumeFileMeta = {
        name: file.name,
        type: isPdf ? "pdf" : "docx",
        size: file.size,
        extractedAt: new Date().toISOString(),
        charCount: text.length,
      };

      // Fire a custom event so other scripts (e.g. your Gemini call) can react
      document.dispatchEvent(
        new CustomEvent("resume:extracted", {
          detail: {
            resumeText: text,
            jobDescription,
            fileMeta: window.MatchAI.resumeFileMeta,
          },
        })
      );

      console.log("[resume-extract] Extraction complete:", window.MatchAI.resumeFileMeta);
      console.log("[resume-extract] Preview:", text.slice(0, 300) + (text.length > 300 ? "…" : ""));

      // Phase 2/4 — Scanning for ATS keywords…
      loading && loading.setPhase(1);
      await wait(500);

      // Send extracted text to backend -> Gemini (phases 3 & 4 happen inside callAnalyze)
      await callAnalyze(text, jobDescription);
    } catch (err) {
      console.error("[resume-extract] Extraction failed:", err);
      showToast(
        "Couldn't read that file. Please try a different PDF/DOCX or re-export it.",
        "error"
      );
      loading && loading.hide();
    } finally {
      analyzeBtn.disabled = false;
      analyzeBtn.classList.remove("opacity-70", "cursor-not-allowed");
    }
  }

  analyzeBtn.addEventListener("click", handleAnalyzeClick);

  // ---- Backend call: sends extracted text to /api/analyze.php -> Gemini ----
  // Result is stored on window.MatchAI.lastAnalysis and logged to console.
  // Build your results UI off the `resume:analyzed` event or that variable.

  async function callAnalyze(resumeText, jobDescription) {
    const loading = window.MatchAI.loading;

    try {
      // Phase 3/4 — Comparing against the job description…
      loading && loading.setPhase(2);

      const res = await fetch("/api/analyze.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ resumeText, jobDescription }),
      });

      const data = await res.json();
      window.MatchAI.lastAnalysis = data;

      document.dispatchEvent(
        new CustomEvent("resume:analyzed", { detail: data })
      );

      if (data.ok) {
        // Phase 4/4 — Finalizing your match score…
        loading && loading.setPhase(3);
        await wait(600);
        console.log("[resume-extract] Analysis complete:", data);
        showToast("Analysis complete!", "success");
      } else {
        console.error("[resume-extract] Backend returned an error:", data.error);
        showToast(`Backend error: ${data.error}`, "error");
      }

      return data;
    } catch (err) {
      console.error("[resume-extract] Analyze request failed:", err);
      showToast("Couldn't reach /api/analyze.php. Is the backend running?", "error");
      return null;
    } finally {
      loading && loading.hide();
    }
  }
})();