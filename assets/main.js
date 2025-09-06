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
  const boardSlider = document.querySelector('.board-slider');
  const scrollLeft = document.querySelector('.board-scroll.left');
  const scrollRight = document.querySelector('.board-scroll.right');
  const categoryOptions = document.querySelector('.form-link select') ? document.querySelector('.form-link select').innerHTML : '';
  let currentCat = 'all';
  let loading = false;
  let linkCount = cards.filter(c => !c.classList.contains('ad-card')).length;
  const offsets = { all: linkCount };
  cards.forEach(c => {
    if (!c.classList.contains('ad-card')) {
      const cat = c.dataset.cat;
      offsets[cat] = (offsets[cat] || 0) + 1;
    }
  });
  const filter = (cat, query = '') => {
    cards.forEach(card => {
      const inCat = (cat === 'all' || card.dataset.cat === cat || card.classList.contains('ad-card'));
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
      } else {
        const shareUrl = 'https://www.addtoany.com/share#url=' + encodeURIComponent(url);
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
            if (antiguaCat !== nuevaCat) {
              offsets[antiguaCat] = Math.max((offsets[antiguaCat] || 1) - 1, 0);
              offsets[nuevaCat] = (offsets[nuevaCat] || 0) + 1;
            }
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

  const escapeHtml = (str) => str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  const createCard = (link) => {
    const domain = new URL(link.url).hostname;
    const imgSrc = link.imagen ? link.imagen : 'https://www.google.com/s2/favicons?domain=' + encodeURIComponent(domain) + '&sz=128';
    const isDefault = !link.imagen || link.imagen.includes('google.com/s2/favicons');
    const card = document.createElement('div');
    card.className = 'card';
    card.dataset.cat = link.categoria_id;
    card.dataset.id = link.id;
    const desc = link.descripcion ? link.descripcion : '';
    const shortDesc = desc.length > 75 ? desc.substring(0, 72) + '...' : desc;
    card.innerHTML = `
      <div class="card-image ${isDefault ? 'no-image' : ''}">
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
        ${desc ? `<p>${escapeHtml(shortDesc)}</p>` : ''}
        <div class="card-actions">
          <select class="move-select" data-id="${link.id}">${categoryOptions}</select>
        </div>
      </div>`;
    card.querySelector('.move-select').value = link.categoria_id;
    return card;
  };

  const createAdCard = () => {
    const ad = document.createElement('div');
    ad.className = 'card ad-card';
    ad.dataset.cat = 'ad';
    const body = document.createElement('div');
    body.className = 'card-body';
    const ins = document.createElement('ins');
    ins.setAttribute('data-revive-zoneid', '52');
    ins.setAttribute('data-revive-id', 'cabd7431fd9e40f440e6d6f0c0dc8623');
    body.appendChild(ins);
    const script = document.createElement('script');
    script.async = true;
    script.src = '//4bes.es/adserver/www/delivery/asyncjs.php';
    body.appendChild(script);
    ad.appendChild(body);
    return ad;
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
      linkCount++;
      if (linkCount % 16 === 0) {
        const adCard = createAdCard();
        linkContainer.appendChild(adCard);
        cards.push(adCard);
        attachCardEvents(adCard);
      }
    });
    feather.replace();
    offsets.all = linkCount;
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
