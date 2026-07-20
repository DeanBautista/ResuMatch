<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In · Match AI</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/css/animations.css">
</head>
<body class="min-h-screen bg-[radial-gradient(circle_at_15%_-10%,#cfe0fb_0%,transparent_45%),radial-gradient(circle_at_100%_0%,#e3edfd_0%,transparent_50%),linear-gradient(160deg,#dbeafe_0%,#eaf3fd_45%,#f3f8fe_100%)] bg-fixed">

<?php include 'partials/header.php'; ?>

<main class="max-w-md mx-auto px-6 flex flex-col justify-center min-h-[calc(100vh-96px)]">

  <div class="text-center mb-8">
    <div class="w-12 h-12 rounded-xl bg-gray-900 flex items-center justify-center mx-auto mb-5">
      <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
    </div>
    <h1 class="text-3xl font-extrabold text-gray-900 leading-tight">
      Welcome back
    </h1>
    <p class="mt-3 text-gray-600">
      Sign in to see how your resume stacks up.
    </p>
  </div>

  <section class="border border-gray-300 bg-white rounded-2xl shadow-[0_2px_8px_rgba(30,64,175,0.06),0_12px_32px_rgba(30,64,175,0.10)] p-6 sm:p-8">

    <button id="googleSignInBtn" type="button"
      class="w-full bg-gray-900 hover:bg-black text-white font-medium py-4 rounded-full flex items-center justify-center gap-3 transition">
      <svg class="w-5 h-5 shrink-0" viewBox="0 0 48 48">
        <path fill="#FFFFFF" d="M44.5 20H24v8.5h11.8C34.7 33.9 30 37 24 37c-7.2 0-13-5.8-13-13s5.8-13 13-13c3.1 0 5.9 1.1 8.1 2.9l6.4-6.4C34.6 4.1 29.6 2 24 2 11.8 2 2 11.8 2 24s9.8 22 22 22c11 0 21-8 21-22 0-1.3-.1-2.7-.5-4z" opacity="0"/>
        <path fill="#4285F4" d="M45 24c0-1.6-.1-2.8-.4-4H24v8.5h11.9c-.5 2.9-2.1 5.3-4.5 7v5.7h7.3C43 37 45 31.1 45 24z"/>
        <path fill="#34A853" d="M24 46c6 0 11.1-2 14.8-5.4l-7.3-5.7c-2 1.4-4.6 2.2-7.5 2.2-5.7 0-10.6-3.9-12.3-9.1H4.2v5.9C7.9 41 15.4 46 24 46z"/>
        <path fill="#FBBC05" d="M11.7 27.9c-.4-1.4-.7-2.8-.7-4.4s.3-3 .7-4.4v-5.9H4.2C2.8 16.4 2 20.1 2 24s.8 7.6 2.2 10.7l7.5-5.9z"/>
        <path fill="#EA4335" d="M24 10.6c3.3 0 6.2 1.1 8.5 3.4l6.4-6.4C34.9 3.9 29.9 2 24 2 15.4 2 7.9 7 4.2 14.6l7.5 5.9c1.7-5.2 6.6-9.9 12.3-9.9z"/>
      </svg>
      Continue with Google
    </button>

    <p class="text-xs text-gray-400 text-center mt-5 leading-relaxed">
      By continuing, you agree to Match AI's
      <a href="/terms" class="text-gray-600 hover:text-gray-900 underline underline-offset-2">Terms</a>
      and
      <a href="/privacy" class="text-gray-600 hover:text-gray-900 underline underline-offset-2">Privacy Policy</a>.
    </p>
  </section>

  <p class="text-center text-sm text-gray-600 mt-3 flex justify-center">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 10-8 0v4h8z"/>
    </svg>
    Your data is processed securely and never shared with employers.
  </p>

</main>

<?php include 'partials/toast.php'; ?>
<?php include 'partials/loading-overlay.php'; ?>

<script src="<?= $GLOBALS['assetBase'] ?>/toast.js"></script>
<script src="<?= $GLOBALS['assetBase'] ?>/loading-overlay.js"></script>
<script>
  window.GOOGLE_CLIENT_ID = "<?= htmlspecialchars($_ENV['GOOGLE_CLIENT_ID'] ?? '', ENT_QUOTES) ?>";
</script>
<script src="https://accounts.google.com/gsi/client" async defer></script>
<script src="<?= $GLOBALS['assetBase'] ?>/js/auth/google-oauth.js"></script>

</body>
</html>