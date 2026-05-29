/**
 * Toggle system adapter for this codebase.
 *
 * Provides:
 *  - toggleStatus() for delivery dashboard (online/offline)
 *  - toggleRestaurantStatus() for restaurant dashboard (open/closed)
 *  - toggleNotifications() for customer dashboards (if present)
 *
 * Buttons should call these functions via onclick.
 */

(() => {
  async function postJson(url, payload) {
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify(payload)
    });

    // API might return json; if it doesn't, throw.
    return await res.json();
  }

  function setLoading(btn, isLoading) {
    if (!btn) return;
    if (isLoading) {
      btn.dataset.loading = '1';
      btn.classList.add('loading');
      btn.disabled = true;
    } else {
      btn.dataset.loading = '';
      btn.classList.remove('loading');
      btn.disabled = false;
    }
  }

  // DELIVERYS: statusToggle button (id=statusToggle) or fallback: button onclick calls toggleStatus
  window.toggleStatus = async function toggleStatus() {
    const btn = document.getElementById('statusToggle');
    const current = btn?.dataset?.status || null;
    if (!btn) {
      window.showAlert?.('Delivery status toggle not found on this page', 'error');
      return;
    }

    const newStatus = current === 'online' ? 'offline' : 'online';
    setLoading(btn, true);

    try {
      const json = await postJson('/api/toggle-delivery-status.php', { status: newStatus });
      if (!json.success) {
        window.showAlert?.(json.message || 'Error updating status', 'error');
        return;
      }

      btn.dataset.status = newStatus;
      // Update visible label if it exists
      const textEl = document.getElementById('statusText');
      if (textEl) textEl.textContent = newStatus === 'online' ? '🟢 Online' : '🔴 Offline';

      window.showAlert?.(`You are now ${newStatus}!`, 'success');

      // Optionally refresh page to ensure DB-driven state
      setTimeout(() => window.location.reload(), 800);
    } catch (e) {
      console.error(e);
      window.showAlert?.('Failed to update status', 'error');
    } finally {
      setLoading(btn, false);
    }
  };

  // RESTAURANT: restaurantToggle (id=restaurantToggle)
  window.toggleRestaurantStatus = async function toggleRestaurantStatus() {
    const btn = document.getElementById('restaurantToggle');
    const current = btn?.dataset?.status || null;
    if (!btn) {
      window.showAlert?.('Restaurant status toggle not found on this page', 'error');
      return;
    }

    const newStatus = current === 'open' ? 'closed' : 'open';
    setLoading(btn, true);

    try {
      const json = await postJson('/api/toggle-restaurant-status.php', { status: newStatus });
      if (!json.success) {
        window.showAlert?.(json.message || 'Error updating restaurant status', 'error');
        return;
      }

      btn.dataset.status = newStatus;
      const textEl = document.getElementById('restaurantStatusText');
      if (textEl) textEl.textContent = newStatus === 'open' ? '🟢 Open' : '🔴 Closed';

      window.showAlert?.(`Restaurant is now ${newStatus}!`, 'success');
      setTimeout(() => window.location.reload(), 800);
    } catch (e) {
      console.error(e);
      window.showAlert?.('Failed to update restaurant status', 'error');
    } finally {
      setLoading(btn, false);
    }
  };

  // CUSTOMER: notifToggle (id=notifToggle). If not present, do nothing.
  window.toggleNotifications = async function toggleNotifications() {
    const btn = document.getElementById('notifToggle');
    if (!btn) {
      window.showAlert?.('Notifications toggle not found on this page', 'error');
      return;
    }

    const current = btn?.dataset?.status || 'on';
    const newEnabled = current === 'on' ? false : true;
    const newStatus = newEnabled ? 'on' : 'off';

    setLoading(btn, true);
    try {
      const json = await postJson('/api/toggle-notifications.php', { notifications_enabled: newEnabled });
      if (!json.success) {
        window.showAlert?.(json.message || 'Error updating notifications', 'error');
        return;
      }

      btn.dataset.status = newStatus;
      const textEl = document.getElementById('notifText');
      if (textEl) textEl.textContent = newEnabled ? '🔔 On' : '🔕 Off';

      window.showAlert?.(`Notifications ${newEnabled ? 'enabled' : 'disabled'}`, 'success');
    } catch (e) {
      console.error(e);
      window.showAlert?.('Failed to update notifications', 'error');
    } finally {
      setLoading(btn, false);
    }
  };

  // Optional: initialize toggle button states from localStorage
  window.initializeToggles = function initializeToggles() {
    const statusToggle = document.getElementById('statusToggle');
    if (statusToggle) {
      const saved = localStorage.getItem('delivery_status') || statusToggle.dataset.status || 'offline';
      statusToggle.dataset.status = saved;
      const textEl = document.getElementById('statusText');
      if (textEl) textEl.textContent = saved === 'online' ? '🟢 Online' : '🔴 Offline';
    }

    const restToggle = document.getElementById('restaurantToggle');
    if (restToggle) {
      const saved = localStorage.getItem('restaurant_status') || restToggle.dataset.status || 'closed';
      restToggle.dataset.status = saved;
      const textEl = document.getElementById('restaurantStatusText');
      if (textEl) textEl.textContent = saved === 'open' ? '🟢 Open' : '🔴 Closed';
    }

    const notifToggle = document.getElementById('notifToggle');
    if (notifToggle) {
      const saved = localStorage.getItem('notifications') || notifToggle.dataset.status || 'on';
      notifToggle.dataset.status = saved;
      const textEl = document.getElementById('notifText');
      if (textEl) textEl.textContent = saved === 'on' ? '🔔 On' : '🔕 Off';
    }
  };

  document.addEventListener('DOMContentLoaded', () => {
    if (typeof window.initializeToggles === 'function') window.initializeToggles();
  });
})();

