document.addEventListener('DOMContentLoaded', function () {
  // if (!window.location.pathname.includes('/checkouts')) return;

  const totalEl = document.querySelector('[data-checkout-payment-due-target]');
  if (!totalEl) return;

  const rawText = totalEl.textContent.replace(/[^\d,.]/g, '').replace(',', '.');
  const priceBgn = parseFloat(rawText);
  const rate = 1.95583;
  const eur = (priceBgn / rate).toFixed(2);

  const notice = document.createElement('div');
  notice.innerText = `Total in EUR: €${eur}`;
  notice.style.position = 'fixed';
  notice.style.bottom = '20px';
  notice.style.right = '20px';
  notice.style.backgroundColor = '#fff8dc';
  notice.style.color = '#111';
  notice.style.padding = '12px 16px';
  notice.style.border = '1px solid #ddd';
  notice.style.borderRadius = '8px';
  notice.style.boxShadow = '0 2px 5px rgba(0,0,0,0.1)';
  notice.style.zIndex = '9999';

  document.body.appendChild(notice);
});
