// Show / hide password
document.querySelectorAll('[data-reveal]').forEach((button) => {
  const input = button.parentElement.querySelector('input');
  button.addEventListener('click', () => {
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    button.textContent = show ? 'Hide' : 'Show';
    button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
  });
});

// Carry the "Keep me signed in" choice into social sign-in links
const remember = document.querySelector('input[name="remember"]');
const socialLinks = document.querySelectorAll('.js-social');
if (remember && socialLinks.length) {
  const sync = () => {
    socialLinks.forEach((link) => {
      const url = new URL(link.href, window.location.origin);
      if (remember.checked) {
        url.searchParams.set('remember', '1');
      } else {
        url.searchParams.delete('remember');
      }
      link.href = url.toString();
    });
  };
  remember.addEventListener('change', sync);
  sync();
}

// Prevent double submits
document.querySelectorAll('form').forEach((form) => {
  form.addEventListener('submit', () => {
    const submit = form.querySelector('button[type="submit"]');
    if (submit) {
      submit.disabled = true;
      submit.setAttribute('aria-busy', 'true');
    }
  });
});

// Re-enable buttons if the page is restored from the back/forward cache
window.addEventListener('pageshow', (event) => {
  if (event.persisted) {
    document.querySelectorAll('button[type="submit"]').forEach((b) => {
      b.disabled = false;
      b.removeAttribute('aria-busy');
    });
  }
});
