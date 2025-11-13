document.addEventListener('DOMContentLoaded', () => {
  if (window.feather) {
    feather.replace();
  }
  const params = new URLSearchParams(window.location.search);
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
    const normalizedQuery = query.trim();
    cards.forEach(card => {
      const inCat = (cat === 'all' || card.dataset.cat === cat);
      const isAd = card.classList.contains('ad-card');
      let matches = true;
      if (normalizedQuery !== '') {
        const loweredQuery = normalizedQuery.toLowerCase();
        const titleEl = card.querySelector('.card-title');
        const descEl = card.querySelector('.card-body p');
        const titleText = titleEl ? titleEl.textContent.toLowerCase() : '';
        const descText = descEl ? descEl.textContent.toLowerCase() : '';
        matches = !isAd && (titleText.includes(loweredQuery) || descText.includes(loweredQuery));
      }
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

  const boardLazyContainer = document.querySelector('.board-links[data-total]');
  if (boardLazyContainer) {
    const total = parseInt(boardLazyContainer.dataset.total || '0', 10);
    let loaded = parseInt(boardLazyContainer.dataset.loaded || '0', 10);
    const mode = boardLazyContainer.dataset.mode || 'private';
    const isDesktop = window.matchMedia('(min-width: 769px)').matches;
    if (isDesktop && total > loaded) {
      const sentinel = document.createElement('div');
      sentinel.className = 'load-more-sentinel';
      boardLazyContainer.appendChild(sentinel);
      const limit = mode === 'public' ? 50 : 100;
      const catId = boardLazyContainer.dataset.cat;
      const token = boardLazyContainer.dataset.token;
      let loadingMore = false;
      let sentinelObserver = null;

      const stopObserving = () => {
        if (observer) {
          observer.unobserve(sentinel);
        }
        if (sentinelObserver) {
          sentinelObserver.disconnect();
        }
        sentinel.remove();
      };

      const buildCard = (link) => {
        const imgSrc = link.imagen && link.imagen.length ? link.imagen : (link.favicon || '');
        const isLocalFavicon = typeof imgSrc === 'string' && imgSrc.startsWith('/local_favicons/');
        const card = document.createElement('div');
        card.className = 'card';

        const imageWrapper = document.createElement('div');
        const classes = ['card-image'];
        if (!link.imagen) {
          classes.push('no-image');
        }
        if (mode === 'public' && isLocalFavicon) {
          classes.push('local-favicon');
        }
        imageWrapper.className = classes.join(' ');

        const anchor = document.createElement('a');
        anchor.href = link.url;
        anchor.target = '_blank';
        anchor.rel = 'noopener noreferrer';

        const img = document.createElement('img');
        img.loading = 'lazy';
        img.alt = '';
        img.src = imgSrc || '';
        anchor.appendChild(img);
        imageWrapper.appendChild(anchor);

        if (mode === 'public') {
          const shareBtn = document.createElement('button');
          shareBtn.className = 'share-btn';
          shareBtn.dataset.url = link.url;
          shareBtn.setAttribute('aria-label', 'Compartir');
          shareBtn.innerHTML = '<i data-feather="share-2"></i>';
          imageWrapper.appendChild(shareBtn);
        }

        card.appendChild(imageWrapper);

        if (mode === 'public') {
          const body = document.createElement('div');
          body.className = 'card-body';

          const titleWrapper = document.createElement('div');
          titleWrapper.className = 'card-title';

          const title = document.createElement('h4');
          if (link.favicon) {
            const favImg = document.createElement('img');
            favImg.src = link.favicon;
            favImg.width = 18;
            favImg.height = 18;
            favImg.alt = '';
            favImg.loading = 'lazy';
            title.appendChild(favImg);
          }
          const titleText = link.titulo && link.titulo.length ? link.titulo : link.url;
          title.appendChild(document.createTextNode(titleText));
          titleWrapper.appendChild(title);
          body.appendChild(titleWrapper);

          if (link.descripcion && link.descripcion.length) {
            const desc = document.createElement('p');
            desc.textContent = link.descripcion;
            body.appendChild(desc);
          }

          card.appendChild(body);
        }

        return card;
      };

      const loadMore = () => {
        if (loadingMore || loaded >= total) return;
        loadingMore = true;
        let url = '';
        if (mode === 'public') {
          if (!token) {
            loadingMore = false;
            return;
          }
          url = `load_public_links.php?token=${encodeURIComponent(token)}&offset=${loaded}&limit=${limit}`;
        } else {
          if (!catId) {
            loadingMore = false;
            return;
          }
          url = `load_links.php?cat=${encodeURIComponent(catId)}&offset=${loaded}&limit=${limit}`;
        }
        fetch(url)
          .then(resp => (resp.ok ? resp.json() : []))
          .then(data => {
            if (!Array.isArray(data) || data.length === 0) {
              loaded = total;
              boardLazyContainer.dataset.loaded = String(loaded);
              stopObserving();
              return;
            }
            data.forEach(link => {
              const card = buildCard(link);
              boardLazyContainer.insertBefore(card, sentinel);
              cards.push(card);
              attachCardEvents(card);
              if (observer) {
                observer.observe(card);
              }
            });
            loaded += data.length;
            boardLazyContainer.dataset.loaded = String(loaded);
            if (window.feather) {
              feather.replace();
            }
            if (loaded >= total) {
              stopObserving();
            }
          })
          .catch(() => {
            // Silently ignore errors to avoid breaking the scroll experience.
          })
          .finally(() => {
            loadingMore = false;
          });
      };

      sentinelObserver = new IntersectionObserver(entries => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            loadMore();
          }
        });
      }, { rootMargin: '200px' });

      sentinelObserver.observe(sentinel);
    }
  }

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

  // Ajuste de longitud de descripciones para el listado de fichas.

  const MAX_DESC = window.innerWidth <= 768 ? 50 : 150;
  document.querySelectorAll('.card-body p').forEach(p => {
    const text = p.textContent.trim();
    if (text.length > MAX_DESC) {
      p.textContent = text.slice(0, MAX_DESC - 3) + '...';
    }
  });

  const favolinkForm = document.querySelector('.favolink-form');
  if (favolinkForm) {
    const linkInput = favolinkForm.querySelector('[name="link_url"]');
    if (linkInput) {
      const sharedParam = params.get('shared');
      if (sharedParam) {
        try { linkInput.focus(); } catch (_) {}
      }
      if (params.has('shared')) {
        params.delete('shared');
        if (typeof history.replaceState === 'function') {
          const newQuery = params.toString();
          const newUrl = `${window.location.pathname}${newQuery ? `?${newQuery}` : ''}${window.location.hash}`;
          history.replaceState(null, '', newUrl);
        }
      }
    }
  }

  document.addEventListener('click', (e) => {
    if (e.target.classList.contains('alert-close')) {
      const alert = e.target.parentElement;
      alert.remove();
    }
  });
});
