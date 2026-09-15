// StarLoco-Web: the little interactivity the site needs, without a framework (keeps the CSP strict).
// Everything is progressive: links and forms work without JavaScript.
(() => {
  const closeMenus = (except) => {
    document.querySelectorAll('details[data-menu][open]').forEach((menu) => {
      if (menu !== except) menu.removeAttribute('open');
    });
  };

  document.addEventListener('click', (event) => {
    const target = event.target instanceof Element ? event.target : null;
    if (!target) return;

    // <a href="/login" data-dialog-open="login-dialog">: opens the dialog instead of navigating.
    const opener = target.closest('[data-dialog-open]');
    const dialog = opener && document.getElementById(opener.getAttribute('data-dialog-open'));
    if (dialog && typeof dialog.showModal === 'function') {
      event.preventDefault();
      closeMenus();
      dialog.showModal();
      dialog.querySelector('input:not([type=hidden])')?.focus();
      return;
    }

    if (target.closest('[data-dialog-close]')) target.closest('dialog')?.close();
    // A click on the backdrop lands on the <dialog> itself (its content has its own wrapper).
    if (target instanceof HTMLDialogElement) target.close();

    if (target.closest('[data-dismiss]')) target.closest('[data-dismissible]')?.remove();

    const refresh = target.closest('[data-captcha-refresh]');
    if (refresh) {
      const image = document.getElementById(refresh.getAttribute('data-captcha-refresh'));
      if (image) image.src = image.src.split('?')[0] + '?' + Date.now();
    }

    closeMenus(target.closest('details[data-menu]'));
  });

  // <form data-confirm="Sure?">: asks before submitting (e.g. deletions).
  document.addEventListener('submit', (event) => {
    const message = event.target instanceof HTMLFormElement && event.target.getAttribute('data-confirm');
    if (message && !window.confirm(message)) event.preventDefault();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeMenus();
  });
})();
