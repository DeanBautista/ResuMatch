// Google OAuth (Google Identity Services) wiring for Sign In / Sign Up pages.
// Include the GSI script on any page that uses this file:
// <script src="https://accounts.google.com/gsi/client" async defer></script>
//
// GOOGLE_CLIENT_ID is NOT hardcoded here — it must be set on `window` by the
// PHP view BEFORE this script loads, e.g. in signin.php:
//
//   <script>
//     window.GOOGLE_CLIENT_ID = "<?= htmlspecialchars($_ENV['GOOGLE_CLIENT_ID'] ?? '', ENT_QUOTES) ?>";
//   </script>
//   <script src="https://accounts.google.com/gsi/client" async defer></script>
//   <script src="/js/auth/google-oauth.js"></script>

const GOOGLE_CLIENT_ID = window.GOOGLE_CLIENT_ID;

function handleCredentialResponse(response) {
  console.log("Raw Google credential response:", response);

  const overlay = document.getElementById("authLoadingOverlay");
  if (overlay) {
    overlay.classList.remove("hidden");
    overlay.classList.add("flex");
  }

  fetch("/api/auth/google.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ credential: response.credential })
  })
    .then((res) => {
      if (!res.ok) throw new Error("Authentication failed");
      return res.json();
    })
    .then((data) => {
      console.log("Backend response after login:", data);
      window.location.href = "/";
    })
    .catch((err) => {
      console.error("Login failed:", err);
      if (overlay) {
        overlay.classList.add("hidden");
        overlay.classList.remove("flex");
      }
      if (window.showToast) {
        window.showToast("Couldn't sign in with Google. Please try again.", "error");
      }
    });
}

let tokenClient;

function initGoogleSignIn(buttonId) {
  if (!GOOGLE_CLIENT_ID) {
    console.error(
      "GOOGLE_CLIENT_ID is missing. Make sure window.GOOGLE_CLIENT_ID is set (from .env via PHP) before google-oauth.js loads."
    );
    return;
  }

  if (!window.google || !google.accounts || !google.accounts.oauth2) {
    // GSI script not yet loaded; retry shortly.
    return setTimeout(() => initGoogleSignIn(buttonId), 100);
  }

  const btn = document.getElementById(buttonId);
  if (!btn) return;

  btn.addEventListener("click", () => {
    google.accounts.id.initialize({
      client_id: GOOGLE_CLIENT_ID,
      callback: handleCredentialResponse
    });

    // Try One Tap first; if it can't display, fall back to the popup picker.
    google.accounts.id.prompt((notification) => {
      if (notification.isNotDisplayed() || notification.isSkippedMoment()) {
        launchOAuthPopup();
      }
    });
  });
}

function launchOAuthPopup() {
  if (!tokenClient) {
    tokenClient = google.accounts.oauth2.initTokenClient({
      client_id: GOOGLE_CLIENT_ID,
      scope: "openid email profile",
      callback: (tokenResponse) => {
        console.log("Raw Google access token response:", tokenResponse);

        fetch("/api/auth/google.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ access_token: tokenResponse.access_token })
        })
          .then((res) => {
            if (!res.ok) throw new Error("Authentication failed");
            return res.json();
          })
          .then((data) => {
            console.log("Backend response after login:", data);
            window.location.href = "/";
          })
          .catch((err) => {
            console.error("Login failed:", err);
            if (window.showToast) {
              window.showToast("Couldn't sign in with Google. Please try again.", "error");
            }
          });
      }
    });
  }
  tokenClient.requestAccessToken();
}

document.addEventListener("DOMContentLoaded", () => {
  initGoogleSignIn("googleSignInBtn"); // Sign in page
  initGoogleSignIn("googleSignUpBtn"); // Sign up page
});