<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/svg+xml" href="/site-icon.svg">
<title>ResuMatch</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="<?= $GLOBALS['assetBase'] ?>/css/animations.css">
</head>
<body class="min-h-screen bg-[radial-gradient(circle_at_15%_-10%,#cfe0fb_0%,transparent_45%),radial-gradient(circle_at_100%_0%,#e3edfd_0%,transparent_50%),linear-gradient(160deg,#dbeafe_0%,#eaf3fd_45%,#f3f8fe_100%)] bg-fixed">

<?php include 'partials/header.php'; ?>

<main class="max-w-3xl lg:max-w-5xl xl:max-w-6xl mx-auto px-6 lg:px-10">

  <section class="text-center pt-12 lg:pt-20 pb-8 lg:pb-12">
    <h1 class="text-4xl sm:text-5xl lg:text-5xl xl:text-5xl font-extrabold text-gray-900 leading-tight lg:max-w-4xl lg:mx-auto">
      Know your match before you hit apply.
    </h1>
    <p class="mt-4 lg:mt-6 text-gray-600 max-w-xl lg:max-w-2xl lg:text-lg mx-auto">
      Compare your resume against any job description to instantly identify ATS keyword gaps and matching score.
    </p>
  </section>

  <section class="border border-gray-300 bg-white rounded-2xl shadow-[0_2px_8px_rgba(30,64,175,0.06),0_12px_32px_rgba(30,64,175,0.10)] p-6 sm:p-8 lg:p-10 lg:grid lg:grid-cols-2 lg:gap-6 lg:items-start">

    <div id="dropzoneWrapper" class="relative h-48">

      <!-- Empty state -->
      <label id="dropzoneEmpty" class="absolute inset-0 flex flex-col items-center justify-center border-2 border-dashed border-gray-300 rounded-xl text-center px-4 cursor-pointer hover:border-gray-400 transition">
        <input type="file" id="resumeInput" accept=".pdf,.docx" class="hidden">
        <svg class="w-8 h-8 text-gray-400 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L7 9m5-5l5 5M5 20h14"/>
        </svg>
        <p class="text-gray-800 font-medium">Drag &amp; drop your resume or click to upload</p>
        <p class="text-xs text-gray-400 mt-1 tracking-wide">PDF OR DOCX &middot; MAX 5MB</p>
      </label>

      <!-- Filled state -->
      <div id="dropzoneFilled" class="hidden absolute inset-0 flex-col items-center justify-center border-2 border-solid border-gray-900 bg-gray-50 rounded-xl px-4">
        <button type="button" id="removeFileBtn" aria-label="Remove file" class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
        <div id="fileIconWrapper" class="w-12 h-12 mb-3 rounded-lg flex items-center justify-center">
          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v11a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z"/>
          </svg>
        </div>
        <p id="fileName" class="text-gray-900 font-medium text-sm truncate max-w-[85%] mx-auto text-center"></p>
        <p class="text-xs text-gray-500 mt-1 flex items-center justify-center gap-2">
          <span id="fileTypeLabel" class="uppercase tracking-wide font-medium text-gray-600"></span>
          <span>&middot;</span>
          <span id="fileSizeLabel"></span>
        </p>
        <p class="text-xs text-green-600 font-medium mt-2 flex items-center justify-center gap-1">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
          </svg>
          File attached
        </p>
      </div>
    </div>

    <textarea
      id="jobDescriptionInput"
      class="w-full mt-5 lg:mt-0 border border-gray-300 rounded-lg p-4 text-sm text-gray-600 h-28 lg:h-48 resize-none focus:outline-none focus:ring-2 focus:ring-gray-800"
      placeholder="e.g. We are looking for a Senior Product Designer with 5+ years of experience..."></textarea>

    <button id="analyzeMatchBtn" class="w-full lg:w-auto lg:px-12 mt-5 lg:col-span-2 lg:mx-auto bg-gray-900 hover:bg-black text-white font-medium py-4 rounded-full flex items-center justify-center gap-2 transition">
      Analyze Match
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
      </svg>
    </button>
  </section>

  <p class="text-center text-sm text-gray-600 mt-6 flex items-center justify-center gap-2">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 10-8 0v4h8z"/>
    </svg>
    Your data is processed securely and never shared with employers.
  </p>

  <!-- 
  Only the three feature-card icon wrappers changed.
  Replace the corresponding block in your original file with this section.
-->

  <section class="mt-10 mb-10 lg:mt-16 lg:mb-16 space-y-4 lg:space-y-0 lg:grid lg:grid-cols-3 lg:gap-6">

    <!-- Skills & Keywords -->
    <div class="border border-gray-300 bg-white rounded-2xl shadow-[0_2px_6px_rgba(30,64,175,0.05),0_8px_20px_rgba(30,64,175,0.08)] p-6 lg:p-7 flex gap-4 lg:flex-col lg:gap-3 hover:shadow-[0_4px_10px_rgba(30,64,175,0.08),0_14px_28px_rgba(30,64,175,0.12)] transition-shadow duration-300">
      <div class="shrink-0 w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
        <svg class="w-5 h-5 text-blue-700" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M9 8h1M7 3h7l5 5v11a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z"/>
        </svg>
      </div>
      <div>
        <h3 class="font-semibold text-gray-900 text-lg">Skills &amp; Keywords</h3>
        <p class="text-gray-600 mt-1">Deep analysis of hard and soft skills required for the role, ensuring nothing is missed.</p>
      </div>
    </div>

    <!-- Experience & Education -->
    <div class="border border-gray-300 bg-white rounded-2xl shadow-[0_2px_6px_rgba(30,64,175,0.05),0_8px_20px_rgba(30,64,175,0.08)] p-6 lg:p-7 flex gap-4 lg:flex-col lg:gap-3 hover:shadow-[0_4px_10px_rgba(30,64,175,0.08),0_14px_28px_rgba(30,64,175,0.12)] transition-shadow duration-300">
      <div class="shrink-0 w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
        <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 8l2 2 4-4"/>
        </svg>
      </div>
      <div>
        <h3 class="font-semibold text-gray-900 text-lg">Experience &amp; Education</h3>
        <p class="text-gray-600 mt-1">Validating years of experience and degree requirements against the job listing.</p>
      </div>
    </div>

    <!-- ATS Formatting -->
    <div class="border border-gray-300 bg-white rounded-2xl shadow-[0_2px_6px_rgba(30,64,175,0.05),0_8px_20px_rgba(30,64,175,0.08)] p-6 lg:p-7 flex gap-4 lg:flex-col lg:gap-3 hover:shadow-[0_4px_10px_rgba(30,64,175,0.08),0_14px_28px_rgba(30,64,175,0.12)] transition-shadow duration-300">
      <div class="shrink-0 w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center">
        <svg class="w-5 h-5 text-purple-700" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
      <div>
        <h3 class="font-semibold text-gray-900 text-lg">ATS Formatting</h3>
        <p class="text-gray-600 mt-1">Ensuring your resume structure is readable by automated tracking systems.</p>
      </div>
    </div>

  </section>
</main>

<footer class="border-t border-gray-200/60">
  <div class="max-w-3xl lg:max-w-5xl xl:max-w-6xl mx-auto px-6 lg:px-10 py-6 text-center">
    <nav class="flex items-center justify-center gap-6 text-sm text-gray-700">
      <a href="/privacy" class="hover:text-gray-900">Privacy</a>
      <a href="/terms" class="hover:text-gray-900">Terms</a>
      <a href="/support" class="hover:text-gray-900">Support</a>
    </nav>
    <p class="text-xs text-gray-500 mt-3">&copy; <?= date('Y') ?> Match AI. All rights reserved.</p>
  </div>
</footer>

<?php include 'partials/toast.php'; ?>
<?php include 'partials/loading-overlay.php'; ?>

<script src="<?= $GLOBALS['assetBase'] ?>/js/toast.js"></script>
<script src="<?= $GLOBALS['assetBase'] ?>/js/home/dropzone-upload.js"></script>

<!-- Resume text extraction (PDF.js + Mammoth.js) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
  pdfjsLib.GlobalWorkerOptions.workerSrc =
    "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js";
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.7.0/mammoth.browser.min.js"></script>
<script src="<?= $GLOBALS['assetBase'] ?>/js/loading-overlay.js"></script>
<script src="<?= $GLOBALS['assetBase'] ?>/js/home/resume-extract.js"></script>

</body>
</html>