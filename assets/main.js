document.addEventListener('DOMContentLoaded', () => {
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/service-worker.js');
  }

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
  const linkContainer = document.querySelector('.link-cards');
  const categoryOptions = document.querySelector('.form-link select') ? document.querySelector('.form-link select').innerHTML : '';
  let currentCat = 'all';
  const offsets = { all: cards.length };
  let loading = false;
  cards.forEach(c => {
    const cat = c.dataset.cat;
    offsets[cat] = (offsets[cat] || 0) + 1;
  });
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
      btn.addEventListener('click', async () => {
        buttons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentCat = btn.dataset.cat;
        filter(currentCat, searchInput ? searchInput.value.toLowerCase() : '');
        if (currentCat !== 'all' && !cards.some(c => c.dataset.cat === currentCat)) {
          await loadMore();
          filter(currentCat, searchInput ? searchInput.value.toLowerCase() : '');
        }
      });
    });
  }


  document.querySelectorAll('.share-board').forEach(btn => {
    btn.addEventListener('click', async () => {
      const url = btn.dataset.url;
      if (navigator.share) {
        try { await navigator.share({ url }); } catch (_) {}
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
    const del = card.querySelector('.delete-btn');
    if (del) {
      del.addEventListener('click', () => {
        const id = del.dataset.id;
        fetch('delete_link.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: 'id=' + encodeURIComponent(id)
        }).then(res => res.json()).then(data => {
          if (data.success) {
            const c = del.closest('.card');
            if (c) {
              c.remove();
              cards = cards.filter(x => x !== c);
            }
          }
        });
      });
    }
    const sel = card.querySelector('.move-select');
    if (sel) {
      sel.addEventListener('change', () => {
        const id = sel.dataset.id;
        const categoria = sel.value;
        fetch('move_link.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: 'id=' + encodeURIComponent(id) + '&categoria_id=' + encodeURIComponent(categoria)
        }).then(res => res.json()).then(data => {
          if (data.success) {
            const cardEl = sel.closest('.card');
            if (cardEl) {
              cardEl.dataset.cat = categoria;
              const active = document.querySelector('.board-btn.active');
              if (active) filter(active.dataset.cat, searchInput ? searchInput.value.toLowerCase() : '');
            }
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
        } else if (navigator.clipboard) {
          try {
            await navigator.clipboard.writeText(url);
            const original = share.innerHTML;
            share.innerHTML = feather.icons['check'].toSvg();
            setTimeout(() => { share.innerHTML = original; }, 2000);
          } catch (_) {}
        }
      });
    }
  };

  cards.forEach(attachCardEvents);

  const escapeHtml = (str) => str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  const createCard = (link) => {
    const domain = new URL(link.url).hostname;
    const imgSrc = link.imagen ? link.imagen : 'https://www.google.com/s2/favicons?domain=' + encodeURIComponent(domain) + '&sz=128';
    const card = document.createElement('div');
    card.className = 'card';
    card.dataset.cat = link.categoria_id;
    card.dataset.id = link.id;
    card.innerHTML = `
      <div class="card-image ${link.imagen ? '' : 'no-image'}">
        <a href="${escapeHtml(link.url)}" target="_blank" rel="noopener noreferrer">
          <img src="${escapeHtml(imgSrc)}" alt="">
        </a>
        <button class="share-btn" data-url="${escapeHtml(link.url)}" aria-label="Compartir"><i data-feather="share-2"></i></button>
        <a href="editar_link.php?id=${link.id}" class="edit-btn" aria-label="Editar"><i data-feather="edit-2"></i></a>
      </div>
      <div class="card-body">
        <div class="card-title">
          <img src="https://www.google.com/s2/favicons?domain=${encodeURIComponent(domain)}" width="20" height="20" alt="">
          <h4>${escapeHtml(link.titulo ? link.titulo : link.url)}</h4>
        </div>
        ${link.descripcion ? `<p>${escapeHtml(link.descripcion)}</p>` : ''}
        <div class="card-actions">
          <select class="move-select" data-id="${link.id}">${categoryOptions}</select>
          <div class="action-btns">
            <button class="delete-btn" data-id="${link.id}" aria-label="Borrar"><i data-feather="trash-2"></i></button>
          </div>
        </div>
      </div>`;
    card.querySelector('.move-select').value = link.categoria_id;
    return card;
  };

  const loadMore = async () => {
    if (loading) return false;
    loading = true;
    const off = offsets[currentCat] || 0;
    const res = await fetch(`load_links.php?offset=${off}&cat=${currentCat}`);
    const data = await res.json();
    if (!data.length) {
      loading = false;
      return false;
    }
    data.forEach(link => {
      const card = createCard(link);
      linkContainer.appendChild(card);
      cards.push(card);
      attachCardEvents(card);
      const cat = String(link.categoria_id);
      offsets[cat] = (offsets[cat] || 0) + 1;
    });
    feather.replace();
    offsets[currentCat] = off + data.length;
    loading = false;
    return true;
  };

  const sentinel = document.getElementById('sentinel');
  if (sentinel && linkContainer) {
    const observer = new IntersectionObserver(async (entries) => {
      if (entries[0].isIntersecting && !loading) {
        const got = await loadMore();
        if (!got) observer.disconnect();
        filter(currentCat, searchInput ? searchInput.value.toLowerCase() : '');
      }
    });
    observer.observe(sentinel);
  }

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

  document.addEventListener('click', (e) => {
    if (e.target.classList.contains('alert-close')) {
      const alert = e.target.parentElement;
      alert.remove();
    }
  });
});
