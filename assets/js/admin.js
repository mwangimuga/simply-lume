async function refreshDashboard() {
  const box = document.querySelector('[data-dashboard]');
  if (!box) return;
  const token = document.querySelector('meta[name="csrf-token"]').content;
  const res = await fetch(`${window.SL_BASE}api/?action=dashboard`, { headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' } });
  const data = await res.json();
  if (!data.ok) return;
  const s = data.stats;
  box.querySelector('[data-stat=sales]').textContent = s.sales_fmt;
  box.querySelector('[data-stat=today]').textContent = s.today_fmt;
  box.querySelector('[data-stat=pending]').textContent = s.pending;
  box.querySelector('[data-stat=low]').textContent = s.low;
  box.querySelector('[data-stat=units]').textContent = s.units;
  box.querySelector('[data-stat=expenses]').textContent = s.expenses_fmt;

  const tbody = box.querySelector('[data-pending-body]');
  if (tbody) {
    tbody.innerHTML = s.pendingOrders.map((o) => `
      <tr>
        <td>${o.order_number}</td>
        <td>${o.customer_name}</td>
        <td>${o.total_fmt}</td>
        <td><span class="tag pending">${o.order_status}</span></td>
        <td class="no-print"><button class="btn btn-maroon" data-fulfill="${o.id}">Fulfil</button></td>
      </tr>`).join('') || '<tr><td colspan="5">No pending orders.</td></tr>';
  }
  if (window.salesChart && s.trend_labels) {
    window.salesChart.data.labels = s.trend_labels;
    window.salesChart.data.datasets[0].data = s.trend_values;
    window.salesChart.update();
  }
}

document.addEventListener('click', async (e) => {
  const btn = e.target.closest('[data-fulfill]');
  if (!btn) return;
  const body = new FormData();
  body.append('_csrf', document.querySelector('meta[name="csrf-token"]').content);
  body.append('action', 'fulfill');
  body.append('id', btn.dataset.fulfill);
  await fetch(`${window.SL_BASE}api/`, { method: 'POST', body });
  refreshDashboard();
});

setInterval(refreshDashboard, 8000);
