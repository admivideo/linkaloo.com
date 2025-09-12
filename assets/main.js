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

  const settingsToggle = document.querySelector('.settings-toggle');
  const settingsSub = document.querySelector('.settings-submenu');
  if (settingsToggle && settingsSub) {
    settingsToggle.addEventListener('click', () => {
      settingsSub.classList.toggle('show');
    });
  }

  const buttons = document.querySelectorAll('.board-btn');
  let cards = Array.from(document.querySelectorAll('.link-cards .card'));
  const searchInput = document.querySelector('.search-input');
  const boardSlider = document.querySelector('.board-slider');
  if (boardSlider) {
    const savedScroll = sessionStorage.getItem('boardScroll');
    if (savedScroll !== null) {
      boardSlider.scrollLeft = parseInt(savedScroll, 10);
    }
  }
  const scrollLeft = document.querySelector('.board-scroll.left');
  const scrollRight = document.querySelector('.board-scroll.right');
  let currentCat = 'all';
  const filter = (cat, query = '') => {
    cards.forEach(card => {
      const inCat = (cat === 'all' || card.dataset.cat === cat);
      const matches = card.classList.contains('ad-card') || card.textContent.toLowerCase().includes(query);
      card.style.display = (inCat && matches) ? '' : 'none';
    });
  };

  if (boardSlider && scrollLeft) {
    scrollLeft.addEventListener('click', () => {
      boardSlider.scrollBy({ left: -200, behavior: 'smooth' });
    });
  }
  if (boardSlider && scrollRight) {
    scrollRight.addEventListener('click', () => {
      boardSlider.scrollBy({ left: 200, behavior: 'smooth' });
    });
  }

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
          if (boardSlider) {
            sessionStorage.setItem('boardScroll', boardSlider.scrollLeft);
          }
          buttons.forEach(b => b.classList.remove('active'));
          btn.classList.add('active');
          currentCat = btn.dataset.cat;
          filter(currentCat, searchInput ? searchInput.value.toLowerCase() : '');
        });
      });
    }


  document.querySelectorAll('.share-board').forEach(btn => {
    btn.addEventListener('click', async () => {
      const { url, title, image } = btn.dataset;
      const fullTitle = title ? `${title} - tablero público de Linkadoo` : 'tablero público de Linkadoo';
      if (navigator.share) {
        const shareData = { url, title: fullTitle, text: fullTitle };
        if (image && navigator.canShare) {
          try {
            const resp = await fetch(image);
            const blob = await resp.blob();
            const name = image.split('/').pop().split('?')[0] || 'image';
            const file = new File([blob], name, { type: blob.type });
            if (navigator.canShare({ files: [file] })) {
              shareData.files = [file];
            }
          } catch (_) {}
        }
        try { await navigator.share(shareData); } catch (_) {}
      } else {
        let shareUrl = 'https://www.addtoany.com/share#url=' + encodeURIComponent(url);
        shareUrl += '&title=' + encodeURIComponent(fullTitle);
        window.open(shareUrl, '_blank', 'noopener');
      }
    });
  });

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

  const attachCardEvents = (card) => {
    const sel = card.querySelector('.move-select');
    if (sel) {
      sel.addEventListener('change', () => {
        const cardEl = sel.closest('.card');
        if (!cardEl) return;
        const id = sel.dataset.id;
        const nuevaCat = sel.value;
        const antiguaCat = cardEl.dataset.cat;
        fetch('move_link.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'id=' + encodeURIComponent(id) + '&categoria_id=' + encodeURIComponent(nuevaCat)
        }).then(res => res.json()).then(data => {
          if (data.success) {
            cardEl.dataset.cat = nuevaCat;
            const active = document.querySelector('.board-btn.active');
            if (active) filter(active.dataset.cat, searchInput ? searchInput.value.toLowerCase() : '');
          } else {
            sel.value = antiguaCat;
          }
        });
      });
    }
    const share = card.querySelector('.share-btn');
    if (share) {
      share.addEventListener('click', async () => {
        const url = share.dataset.url;
        if (navigator.share) {
          try {
            await navigator.share({ url });
          } catch (_) {}
        } else {
          const shareUrl = 'https://www.addtoany.com/share#url=' + encodeURIComponent(url);
          window.open(shareUrl, '_blank', 'noopener');
        }
      });
    }
  };

  cards.forEach(attachCardEvents);

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1 });

  cards.forEach(card => observer.observe(card));

  const detailDelete = document.querySelector('.board-detail-image .delete-btn');
  if (detailDelete) {
    detailDelete.addEventListener('click', () => {
      if (!confirm('¿Eliminar este enlace?')) return;
      const id = detailDelete.dataset.id;
      fetch('delete_link.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + encodeURIComponent(id)
      }).then(res => res.json()).then(data => {
        if (data.success) {
          window.location.href = 'panel.php';
        }
      });
    });
  }

  // Las fichas se cargan todas inicialmente; se eliminó la carga incremental.

  const MAX_DESC = 250;
  document.querySelectorAll('.card-body p').forEach(p => {
    const text = p.textContent.trim();
    if (text.length > MAX_DESC) {
      p.textContent = text.slice(0, MAX_DESC - 3) + '...';
    }
  });

  const openModalBtn = document.querySelector('.open-modal');
  const addModal = document.querySelector('.add-modal');
  if (openModalBtn && addModal) {
    const close = () => addModal.classList.remove('show');
    openModalBtn.addEventListener('click', () => {
      addModal.classList.add('show');
    });
    addModal.addEventListener('click', (e) => {
      if (e.target === addModal || e.target.classList.contains('modal-close')) {
        close();
      }
    });
  }

  document.addEventListener('click', (e) => {
    if (e.target.classList.contains('alert-close')) {
      const alert = e.target.parentElement;
      alert.remove();
    }
  });
});
