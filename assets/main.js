document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('.menu-toggle');
  const menu = document.querySelector('.top-menu ul');
  if (toggle && menu) {
    toggle.addEventListener('click', () => {
      menu.classList.toggle('show');
    });
  }

  const buttons = document.querySelectorAll('.board-btn');
  const cards = document.querySelectorAll('.link-cards .card');
  const filter = (cat) => {
    cards.forEach(card => {
      card.style.display = (cat === 'all' || card.dataset.cat === cat) ? '' : 'none';
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
    filter(activeBtn.dataset.cat);
    activeBtn.classList.add('active');
    buttons.forEach(btn => {
      btn.addEventListener('click', () => {
        buttons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        filter(btn.dataset.cat);
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
          const original = btn.textContent;
          btn.textContent = '✅';
          setTimeout(() => { btn.textContent = original; }, 2000);
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
});
