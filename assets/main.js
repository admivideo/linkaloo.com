document.addEventListener('DOMContentLoaded', () => {
  if (window.feather) {
    feather.replace();
  }
  const toggle = document.querySelector('.menu-toggle');
  const menu = document.querySelector('.top-menu ul');
  if (toggle && menu) {
    toggle.addEventListener('click', () => {
      menu.classList.toggle('show');
    });
  }

  const buttons = document.querySelectorAll('.board-btn');
  const cards = document.querySelectorAll('.link-cards .card');
  const searchInput = document.querySelector('.search-input');
  let currentCat = 'all';
  const filter = (cat, query = '') => {
    cards.forEach(card => {
      const inCat = (cat === 'all' || card.dataset.cat === cat);
      const matches = card.textContent.toLowerCase().includes(query);
      card.style.display = (inCat && matches) ? '' : 'none';
    });
  };

  if (buttons.length) {
    const params = new URLSearchParams(window.location.search);
    const initial = params.get('cat');
    let activeBtn = buttons[0];
    if (initial) {
      const found = Array.from(buttons).find(b => b.dataset.cat === initial);
      if (found) activeBtn = found;
    }
    currentCat = activeBtn.dataset.cat;
    filter(currentCat, searchInput ? searchInput.value.toLowerCase() : '');
    activeBtn.classList.add('active');
    buttons.forEach(btn => {
      btn.addEventListener('click', () => {
        buttons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentCat = btn.dataset.cat;
        filter(currentCat, searchInput ? searchInput.value.toLowerCase() : '');
      });
    });
  }

  const slider = document.querySelector('.board-slider');
  const left = document.querySelector('.board-scroll.left');
  const right = document.querySelector('.board-scroll.right');
  if (slider && left && right) {
    const step = 100;
    left.addEventListener('click', () => slider.scrollBy({left: -step, behavior: 'smooth'}));
    right.addEventListener('click', () => slider.scrollBy({left: step, behavior: 'smooth'}));
  }

  const searchToggle = document.querySelector('.search-toggle');
  if (searchToggle && searchInput) {
    searchToggle.addEventListener('click', () => {
      searchInput.classList.toggle('show');
      if (searchInput.classList.contains('show')) {
        searchInput.focus();
      } else {
        searchInput.value = '';
        filter(currentCat, '');
      }
    });
    searchInput.addEventListener('input', () => {
      const q = searchInput.value.toLowerCase();
      filter(currentCat, q);
    });
  }

  document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.id;
      fetch('delete_link.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + encodeURIComponent(id)
      }).then(res => res.json()).then(data => {
        if (data.success) {
          const card = btn.closest('.card');
          if (card) card.remove();
        }
      });
    });
  });

  document.querySelectorAll('.move-select').forEach(sel => {
    sel.addEventListener('change', () => {
      const id = sel.dataset.id;
      const categoria = sel.value;
      fetch('move_link.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + encodeURIComponent(id) + '&categoria_id=' + encodeURIComponent(categoria)
      }).then(res => res.json()).then(data => {
        if (data.success) {
          const card = sel.closest('.card');
          if (card) {
            card.dataset.cat = categoria;
            const active = document.querySelector('.board-btn.active');
            if (active) filter(active.dataset.cat);
          }
        }
      });
    });
  });

  document.querySelectorAll('.share-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
      const url = btn.dataset.url;
      if (navigator.share) {
        try {
          await navigator.share({ url });
        } catch (_) {}
      } else if (navigator.clipboard) {
        try {
          await navigator.clipboard.writeText(url);
          const original = btn.innerHTML;
          btn.innerHTML = feather.icons['check'].toSvg();
          setTimeout(() => { btn.innerHTML = original; }, 2000);
        } catch (_) {}
      }
    });
  });

  const MAX_DESC = 250;
  document.querySelectorAll('.card-body p').forEach(p => {
    const text = p.textContent.trim();
    if (text.length > MAX_DESC) {
      p.textContent = text.slice(0, MAX_DESC - 3) + '...';
    }
  });

  const toggleFormsBtn = document.querySelector('.toggle-forms');
  const controlForms = document.querySelector('.control-forms');
  if (toggleFormsBtn && controlForms) {
    toggleFormsBtn.addEventListener('click', () => {
      controlForms.classList.toggle('show');
    });
  }
});
