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
    const menu = card.querySelector('.share-menu');
    if (share && menu) {
      share.addEventListener('click', (e) => {
        e.preventDefault();
        menu.classList.toggle('show');
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
        <button class="share-btn" aria-label="Compartir"><i data-feather="share-2"></i></button>
        <div class="share-menu">
          <a class="share-whatsapp" href="https://api.whatsapp.com/send?text=${encodeURIComponent(link.url)}" target="_blank" rel="noopener noreferrer" aria-label="Compartir en WhatsApp">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347M12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.82 11.82 0 0012.05 0Z"/></svg>
          </a>
        </div>
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
