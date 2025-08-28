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
    filter(buttons[0].dataset.cat);
    buttons.forEach(btn => {
      btn.addEventListener('click', () => {
        buttons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        filter(btn.dataset.cat);
      });
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
});
