async function slFetch(action, payload = {}, method = 'POST') {
  const opts = {
    method,
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'Accept': 'application/json',
    },
  };
  if (method === 'GET') {
    const q = new URLSearchParams({ action, ...payload });
    const res = await fetch(`${window.SL_BASE}api/?${q}`);
    return res.json();
  }
  const body = new FormData();
  body.append('_csrf', document.querySelector('meta[name="csrf-token"]').content);
  body.append('action', action);
  Object.entries(payload).forEach(([k, v]) => body.append(k, v));
  const res = await fetch(`${window.SL_BASE}api/`, { ...opts, body });
  return res.json();
}

document.addEventListener('click', async (e) => {
  const wish = e.target.closest('[data-wish]');
  if (wish) {
    e.preventDefault();
    e.stopPropagation();
    const data = await slFetch('wishlist', { product_id: wish.dataset.wish });
    wish.textContent = data.on ? '♥' : '♡';
  }
  const add = e.target.closest('[data-add]');
  if (add) {
    e.preventDefault();
    const qty = document.querySelector('[name=qty]')?.value || 1;
    const data = await slFetch('cart_add', { product_id: add.dataset.add, qty });
    if (data.ok) {
      const badge = document.querySelector('[data-cart-count]');
      if (badge) badge.textContent = data.count;
    } else if (data.error) {
      alert(data.error);
    }
  }
});
