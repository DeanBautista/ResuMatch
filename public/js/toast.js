function showToast(message, title = 'Something went wrong') {
  const toastContainer = document.getElementById('toastContainer');

  const toast = document.createElement('div');
  toast.className = 'pointer-events-auto flex items-start gap-3 bg-white border border-red-200 shadow-lg rounded-xl px-4 py-3 w-80 max-w-[90vw] opacity-0 translate-x-4 transition-all duration-300';

  toast.innerHTML = `
    <div class="shrink-0 w-8 h-8 rounded-full bg-red-100 flex items-center justify-center mt-0.5">
      <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
      </svg>
    </div>
    <div class="flex-1">
      <p class="text-sm font-semibold text-gray-900">${title}</p>
      <p class="text-xs text-gray-600 mt-0.5">${message}</p>
    </div>
    <button type="button" aria-label="Dismiss" class="shrink-0 text-gray-400 hover:text-gray-600 transition">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>
  `;

  toastContainer.appendChild(toast);

  requestAnimationFrame(() => {
    toast.classList.remove('opacity-0', 'translate-x-4');
  });

  function dismiss() {
    toast.classList.add('opacity-0', 'translate-x-4');
    setTimeout(() => toast.remove(), 300);
  }

  toast.querySelector('button').addEventListener('click', dismiss);
  setTimeout(dismiss, 5000);
}