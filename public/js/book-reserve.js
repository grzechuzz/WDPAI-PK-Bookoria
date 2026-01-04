(() => {
  console.log("book-reserve.js loaded");
  const list = document.getElementById('branchesList');
  const hidden = document.getElementById('selectedBranchId');
  const btn = document.getElementById('reserveBtn');
  if (!list || !hidden) return;

  const setSelected = (row) => {
    const id = row.getAttribute('data-branch-id');
    if (!id) return;

    list.querySelectorAll('.branch-row.is-selected').forEach((r) => {
      r.classList.remove('is-selected');
      r.setAttribute('aria-pressed', 'false');
    });

    row.classList.add('is-selected');
    row.setAttribute('aria-pressed', 'true');
    hidden.value = id;

    if (btn) btn.disabled = false;
  };

  list.addEventListener('click', (e) => {
    const row = e.target.closest('.branch-row[data-branch-id]');
    if (!row) return;
    setSelected(row);
  });

  list.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    const row = e.target.closest('.branch-row[data-branch-id]');
    if (!row) return;
    e.preventDefault();
    setSelected(row);
  });
})();
