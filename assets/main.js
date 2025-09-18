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

  const MAX_DESC = window.innerWidth <= 768 ? 50 : 150;
  document.querySelectorAll('.card-body p').forEach(p => {
    const text = p.textContent.trim();
    if (text.length > MAX_DESC) {
      p.textContent = text.slice(0, MAX_DESC - 3) + '...';
    }
  });

  const openModalBtns = document.querySelectorAll('.open-modal');
  const addModal = document.querySelector('.add-modal');
  const linkInput = addModal ? addModal.querySelector('.form-link [name="link_url"]') : null;
  const clipboardPreview = addModal ? addModal.querySelector('.clipboard-preview') : null;
  const clipboardText = clipboardPreview ? clipboardPreview.querySelector('.clipboard-text') : null;

  const showAddModal = () => {
    if (!addModal) return;
    addModal.classList.add('show');
    if (linkInput) {
      try { linkInput.focus(); } catch (_) {}
    }
  };

  const hideAddModal = () => {
    if (!addModal) return;
    addModal.classList.remove('show');
  };

  const updateClipboardPreview = (state, message) => {
    if (!clipboardPreview || !clipboardText) return;
    clipboardPreview.hidden = false;
    clipboardPreview.removeAttribute('data-state');
    const visualStateMap = {
      'permission-denied': 'error',
      unsupported: 'error',
      prompt: 'empty',
      loading: 'empty'
    };
    const visualState = state ? (visualStateMap[state] || state) : null;
    if (visualState) {
      clipboardPreview.setAttribute('data-state', visualState);
    }
    clipboardText.textContent = message || '';
  };

  if (openModalBtns.length && addModal) {
    openModalBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        showAddModal();
      });
    });
    addModal.addEventListener('click', (e) => {
      if (e.target === addModal || e.target.classList.contains('modal-close')) {
        hideAddModal();
      }
    });
  }

  const sharedParam = params.get('shared');
  if (sharedParam && addModal && linkInput) {
    const candidate = sharedParam.trim();
    const sanitizedCandidate = candidate.replace(/%(?![0-9A-Fa-f]{2})/g, '%25');
    let validUrl = '';
    try {
      const parsed = new URL(sanitizedCandidate);
      if (parsed.protocol === 'http:' || parsed.protocol === 'https:') {
        validUrl = parsed.toString();
      }
    } catch (_) {}

    if (validUrl) {
      linkInput.value = validUrl;
      showAddModal();
      params.delete('shared');
      if (typeof history.replaceState === 'function') {
        const newQuery = params.toString();
        const newUrl = `${window.location.pathname}${newQuery ? `?${newQuery}` : ''}${window.location.hash}`;
        history.replaceState(null, '', newUrl);
      }
    }
  }

  const shouldShowClipboardModal = addModal && !sharedParam;
  if (shouldShowClipboardModal) {
    const clipboardImportBtn = clipboardPreview ? clipboardPreview.querySelector('.clipboard-import-btn') : null;
    const CLIPBOARD_MESSAGES = {
      prompt: 'Pulsa «Importar desde portapapeles» cuando quieras pegar el enlace automáticamente.',
      loading: 'Leyendo el portapapeles...',
      empty: 'Tu portapapeles está vacío. Copia un enlace y vuelve a pulsar «Importar desde portapapeles».',
      permissionDenied: 'No se concedió el permiso para leer el portapapeles. Ajusta los permisos de tu navegador y vuelve a pulsar «Importar desde portapapeles».',
      unsupported: 'Tu navegador no permite leer el portapapeles automáticamente. Copia el enlace y pégalo manualmente.',
      error: 'No se pudo leer el portapapeles. Vuelve a pulsar el botón para reintentarlo.'
    };

    let clipboardState = null;
    const setClipboardState = (state, message, options = {}) => {
      clipboardState = state;
      updateClipboardPreview(state, message);
      const { prefillValue } = options;
      if (state === 'filled' && linkInput && typeof prefillValue === 'string' && prefillValue && !linkInput.value) {
        linkInput.value = prefillValue;
        try { linkInput.focus(); } catch (_) {}
      }
    };

    const setImportButtonAvailability = (available) => {
      if (!clipboardImportBtn) return;
      if (available) {
        clipboardImportBtn.disabled = false;
        clipboardImportBtn.removeAttribute('aria-disabled');
      } else {
        clipboardImportBtn.disabled = true;
        clipboardImportBtn.setAttribute('aria-disabled', 'true');
      }
    };

    showAddModal();

    if (!navigator.clipboard || typeof navigator.clipboard.readText !== 'function') {
      setImportButtonAvailability(false);
      setClipboardState('unsupported', CLIPBOARD_MESSAGES.unsupported);
      return;
    }

    setImportButtonAvailability(true);
    setClipboardState('prompt', CLIPBOARD_MESSAGES.prompt);

    let permissionState = 'prompt';

    if (navigator.permissions && typeof navigator.permissions.query === 'function') {
      navigator.permissions.query({ name: 'clipboard-read' }).then(status => {
        const applyPermissionState = () => {
          permissionState = status.state;
          if (permissionState === 'denied') {
            setClipboardState('permission-denied', CLIPBOARD_MESSAGES.permissionDenied);
          } else if (clipboardState === 'permission-denied') {
            setClipboardState('prompt', CLIPBOARD_MESSAGES.prompt);
          }
        };
        applyPermissionState();
        status.addEventListener('change', applyPermissionState);
      }).catch(() => {});
    }

    if (clipboardImportBtn) {
      clipboardImportBtn.addEventListener('click', async () => {
        if (permissionState === 'denied') {
          setClipboardState('permission-denied', CLIPBOARD_MESSAGES.permissionDenied);
          return;
        }
        setClipboardState('loading', CLIPBOARD_MESSAGES.loading);
        clipboardImportBtn.disabled = true;
        try {
          const text = await navigator.clipboard.readText();
          const rawText = typeof text === 'string' ? text : '';
          const trimmed = rawText.trim();
          if (trimmed) {
            setClipboardState('filled', `Hemos encontrado lo siguiente en tu portapapeles:\n\n${rawText}`, { prefillValue: trimmed });
          } else {
            setClipboardState('empty', CLIPBOARD_MESSAGES.empty);
          }
        } catch (error) {
          if (error && (error.name === 'NotAllowedError' || error.name === 'SecurityError')) {
            permissionState = 'denied';
            setClipboardState('permission-denied', CLIPBOARD_MESSAGES.permissionDenied);
          } else {
            setClipboardState('error', CLIPBOARD_MESSAGES.error);
          }
        } finally {
          clipboardImportBtn.disabled = false;
          clipboardImportBtn.removeAttribute('aria-disabled');
        }
      });
    }
  }

  document.addEventListener('click', (e) => {
    if (e.target.classList.contains('alert-close')) {
      const alert = e.target.parentElement;
      alert.remove();
    }
  });
});
