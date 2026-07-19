document.addEventListener('DOMContentLoaded', () => {
  const searchInput   = document.getElementById('historySearchInput');
  const list          = document.getElementById('historyList');
  const items          = Array.from(document.querySelectorAll('.history-item'));
  const emptyState     = document.getElementById('historyEmptyState');

  const sortBtn   = document.getElementById('sortDropdownBtn');
  const sortMenu  = document.getElementById('sortDropdownMenu');
  const sortLabel = document.getElementById('sortDropdownLabel');

  const sortLabels = {
    recent:  'Most recent',
    oldest:  'Oldest',
    highest: 'Highest match',
    lowest:  'Lowest match',
  };

  let currentSort = 'recent';

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
      applySort();
      applyFilter();
    });
  });

  function applySort() {
    const sorted = [...items].sort((a, b) => {
      const scoreA = parseInt(a.dataset.score, 10);
      const scoreB = parseInt(b.dataset.score, 10);
      const dateA  = parseInt(a.dataset.date, 10); // days ago
      const dateB  = parseInt(b.dataset.date, 10);

      switch (currentSort) {
        case 'oldest':  return dateA - dateB;
        case 'highest': return scoreB - scoreA;
        case 'lowest':  return scoreA - scoreB;
        case 'recent':
        default:        return dateB - dateA;
      }
    });
    sorted.forEach((item) => list.appendChild(item));
  }

  // --- Search filter ---
  function applyFilter() {
    const query = searchInput.value.trim().toLowerCase();
    let visibleCount = 0;

    items.forEach((item) => {
      const matches = item.dataset.title.includes(query);
      item.classList.toggle('hidden', !matches);
      if (matches) visibleCount++;
    });

    emptyState.classList.toggle('hidden', visibleCount !== 0);
  }

  searchInput.addEventListener('input', applyFilter);
});