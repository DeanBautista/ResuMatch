function showToast(message, type = 'error') {
  const toastContainer = document.getElementById('toastContainer');

  const config = {
    success: {
      title: 'Success',
      border: 'border-green-200',
      bg: 'bg-green-100',
      iconColor: 'text-green-600',
      icon: `
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M5 13l4 4L19 7"/>
      `
    },
    error: {
      title: 'Something went wrong',
      border: 'border-red-200',
      bg: 'bg-red-100',
      iconColor: 'text-red-600',
      icon: `
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M12 9v3.75m0 3.75h.008v.008H12v-.008z"/>
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M12 3a9 9 0 100 18 9 9 0 000-18z"/>
      `
    },
    info: {
      title: 'Information',
      border: 'border-blue-200',
      bg: 'bg-blue-100',
      iconColor: 'text-blue-600',
      icon: `
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M12 9h.01M11 12h1v4h1"/>
        <circle cx="12" cy="12" r="9"/>
      `
    }
  };

  const current = config[type] || config.error;

  const toast = document.createElement('div');
  toast.className = `pointer-events-auto flex items-start gap-3 bg-white border ${current.border} shadow-lg rounded-xl px-4 py-3 w-80 max-w-[90vw] opacity-0 translate-x-4 transition-all duration-300`;

  toast.innerHTML = `
    <div class="shrink-0 w-8 h-8 rounded-full ${current.bg} flex items-center justify-center mt-0.5">
      <svg class="w-4 h-4 ${current.iconColor}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        ${current.icon}
      </svg>
    </div>

    <div class="flex-1">
      <p class="text-sm font-semibold text-gray-900">${current.title}</p>
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