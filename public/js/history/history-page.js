document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('historySearchInput');
  const list         = document.getElementById('historyList');
  const emptyState   = document.getElementById('historyEmptyState');
  const countLabel   = document.querySelector('h1 span'); // "N checks"

  const sortBtn   = document.getElementById('sortDropdownBtn');
  const sortMenu  = document.getElementById('sortDropdownMenu');
  const sortLabel = document.getElementById('sortDropdownLabel');

  const deleteModal    = document.getElementById('deleteConfirmModal');
  const deleteBackdrop = document.getElementById('deleteConfirmBackdrop');
  const deleteCancelBtn = document.getElementById('deleteConfirmCancel');
  const deleteAcceptBtn = document.getElementById('deleteConfirmAccept');

  const sortLabels = {
    recent:  'Most recent',
    oldest:  'Oldest',
    highest: 'Highest match',
    lowest:  'Lowest match',
  };

  let currentSort = 'recent';
  let historyData = []; // raw rows from the API
  let pendingDeleteId = null; // id awaiting confirmation in the modal

  // --- Sort dropdown toggle ---
  sortBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    sortMenu.classList.toggle('hidden');
  });

  document.addEventListener('click', (e) => {
    if (!sortMenu.contains(e.target) && e.target !== sortBtn) {
      sortMenu.classList.add('hidden');
    }
  });

  document.querySelectorAll('.sort-option').forEach((btn) => {
    btn.addEventListener('click', () => {
      currentSort = btn.dataset.value;
      sortLabel.textContent = sortLabels[currentSort];
      sortMenu.classList.add('hidden');
      render();
    });
  });

  searchInput.addEventListener('input', render);

  // --- Delete modal wiring ---
  function openDeleteModal(id) {
    pendingDeleteId = id;
    deleteModal.classList.remove('hidden');
  }

  function closeDeleteModal() {
    pendingDeleteId = null;
    deleteModal.classList.add('hidden');
  }

  deleteCancelBtn.addEventListener('click', closeDeleteModal);
  deleteBackdrop.addEventListener('click', closeDeleteModal);

  deleteAcceptBtn.addEventListener('click', async () => {
    if (pendingDeleteId === null) return;

    const id = pendingDeleteId;
    const originalLabel = deleteAcceptBtn.textContent;
    deleteAcceptBtn.disabled = true;
    deleteAcceptBtn.textContent = 'Deleting…';

    try {
      const res = await fetch('/api/delete-history.php', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id }),
      });
      const data = await res.json();

      if (!res.ok || !data.ok) {
        throw new Error(data.error || 'Could not delete.');
      }

      historyData = historyData.filter((row) => row.id !== id);

      const card = list.querySelector(`[data-id="${id}"]`);
      closeDeleteModal();

      if (card) {
        card.style.transition = 'opacity 200ms ease, transform 200ms ease';
        card.style.opacity = '0';
        card.style.transform = 'scale(0.98)';
        setTimeout(() => render(), 200);
      } else {
        render();
      }
    } catch (err) {
      console.error('[history] delete failed:', err);
      closeDeleteModal();
      if (typeof window.showToast === 'function') {
        window.showToast(err.message || 'Could not delete. Please try again.', 'error');
      } else {
        alert(err.message || 'Could not delete. Please try again.');
      }
    } finally {
      deleteAcceptBtn.disabled = false;
      deleteAcceptBtn.textContent = originalLabel;
    }
  });

  // --- Fetch history on load ---
  fetchHistory();

  async function fetchHistory() {
    try {
      const res = await fetch('/api/get-history.php', {
        method: 'GET',
        credentials: 'include', // send session cookie
      });
      const data = await res.json();

      if (!res.ok || !data.ok) {
        showError(data.error || 'Could not load history.');
        return;
      }

      historyData = data.history;
      render();
    } catch (err) {
      console.error('[history] fetch failed:', err);
      showError('Could not load history. Please check your connection and try again.');
    }
  }

  function showError(message) {
    list.innerHTML = '';
    emptyState.classList.remove('hidden');
    emptyState.querySelector('p').textContent = message;
  }

  // --- Verdict -> badge styling ---
  function verdictBadge(verdict) {
    const v = (verdict || '').toLowerCase();
    if (v.includes('strong')) {
      return {
        label: 'Strong Match',
        dot: `<span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-green-100 shrink-0">
                <svg class="w-2.5 h-2.5 text-green-600" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
              </span>`,
        textClass: 'text-green-700',
      };
    }
    if (v.includes('moderate')) {
      return { label: 'Moderate Match', dot: '', textClass: 'text-amber-700' };
    }
    if (v.includes('weak') || v.includes('low')) {
      return { label: 'Weak Match', dot: '', textClass: 'text-red-700' };
    }
    return { label: verdict || 'Unrated', dot: '', textClass: 'text-gray-600' };
  }

  // --- Relative date formatting ---
  function timeAgo(isoDate) {
    const then = new Date(isoDate.replace(' ', 'T'));
    const now  = new Date();
    const diffMs   = now - then;
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

    if (diffDays <= 0) return 'Checked today';
    if (diffDays === 1) return 'Checked 1 day ago';
    if (diffDays < 7) return `Checked ${diffDays} days ago`;
    const weeks = Math.floor(diffDays / 7);
    if (weeks === 1) return 'Checked 1 week ago';
    if (weeks < 5) return `Checked ${weeks} weeks ago`;
    const months = Math.floor(diffDays / 30);
    if (months <= 1) return 'Checked 1 month ago';
    return `Checked ${months} months ago`;
  }

  function cardHTML(row) {
    const badge = verdictBadge(row.verdict);
    const title = [row.jobTitle, row.company].filter(Boolean).join(' @ ') || 'Untitled check';
    const score = row.matchScore !== null ? `${row.matchScore}%` : '—';

    return `
      <div class="history-item group relative border border-gray-300 bg-white rounded-2xl shadow-[0_2px_8px_rgba(30,64,175,0.06),0_12px_32px_rgba(30,64,175,0.10)] hover:shadow-[0_4px_12px_rgba(30,64,175,0.10),0_16px_36px_rgba(30,64,175,0.14)] transition-shadow duration-300 p-5 sm:p-6 flex items-center gap-4 sm:gap-5" data-id="${row.id}">
        <a href="/results/${row.id}" class="absolute inset-0 rounded-2xl" aria-label="View ${escapeHTML(title)} results"></a>

        <div class="shrink-0 w-14 h-14 sm:w-16 sm:h-16 rounded-full border border-gray-200 flex items-center justify-center font-bold text-gray-900 text-sm sm:text-base pointer-events-none">
          ${score}
        </div>
        <div class="min-w-0 flex-1 pointer-events-none">
          <p class="font-semibold text-gray-900 text-base sm:text-lg truncate">${escapeHTML(title)}</p>
          <p class="mt-1 text-sm text-gray-500 flex items-center gap-1.5 flex-wrap">
            ${badge.dot}
            <span class="${badge.textClass} font-medium">${escapeHTML(badge.label)}</span>
            <span class="text-gray-300">&middot;</span>
            <span>${timeAgo(row.createdAt)}</span>
          </p>
        </div>

        <button
          type="button"
          class="delete-history-btn relative z-10 shrink-0 p-2 rounded-full text-gray-400 hover:text-red-600 hover:bg-red-50 transition"
          data-id="${row.id}"
          aria-label="Delete this check"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9.5 7V4.5a1 1 0 011-1h3a1 1 0 011 1V7M4 7h16"/>
          </svg>
        </button>

        <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-700 group-hover:translate-x-0.5 transition shrink-0 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
      </div>`;
  }

  function escapeHTML(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  function render() {
    const query = searchInput.value.trim().toLowerCase();

    let rows = historyData.filter((row) => {
      const haystack = [row.jobTitle, row.company].filter(Boolean).join(' ').toLowerCase();
      return haystack.includes(query);
    });

    rows.sort((a, b) => {
      const scoreA = a.matchScore ?? -1;
      const scoreB = b.matchScore ?? -1;
      const dateA  = new Date(a.createdAt.replace(' ', 'T'));
      const dateB  = new Date(b.createdAt.replace(' ', 'T'));

      switch (currentSort) {
        case 'oldest':  return dateA - dateB;
        case 'highest': return scoreB - scoreA;
        case 'lowest':  return scoreA - scoreB;
        case 'recent':
        default:        return dateB - dateA;
      }
    });

    list.innerHTML = rows.map(cardHTML).join('');

    list.querySelectorAll('.delete-history-btn').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const id = btn.dataset.id;
        openDeleteModal(id);
      });
    });

    emptyState.classList.toggle('hidden', rows.length !== 0);
    if (rows.length === 0) {
      emptyState.querySelector('p').textContent = historyData.length === 0
        ? "You haven't run any checks yet."
        : 'No checks match your search.';
    }

    if (countLabel) {
      countLabel.textContent = `${historyData.length} check${historyData.length === 1 ? '' : 's'}`;
    }
  }
});