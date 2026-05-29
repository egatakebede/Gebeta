/**
 * Global Alert + Notification Panel (unified across roles)
 *
 * This project already uses showToast() from assets/js/script.js.
 * We provide showAlert() as an adapter and a notification-panel implementation.
 */

(() => {
  // In-memory notifications (per page)
  let notifications = [];
  const MAX_NOTIFICATIONS = 50;

  // Polling timer
  let pollingTimer = null;

  function ensureNotificationPanel() {
    // If templates already include these containers, use them; otherwise create.
    let panel = document.getElementById('notificationPanel');
    if (!panel) {
      panel = document.createElement('div');
      panel.id = 'notificationPanel';
      panel.className = 'notification-panel';

      panel.innerHTML = `
        <div class="notification-header">
          <h3>Notifications</h3>
          <button type="button" class="close-btn" onclick="closeNotificationPanel()">✕</button>
        </div>
        <div id="notificationList" class="notification-list"></div>
      `;

      document.body.appendChild(panel);
    }

    let list = document.getElementById('notificationList');
    if (!list) {
      list = document.createElement('div');
      list.id = 'notificationList';
      panel.appendChild(list);
    }

    // Badge container: optional
    let badgeHost = document.querySelector('[data-action="notifications"]');
    if (badgeHost && !document.querySelector('.notification-badge')) {
      const badge = document.createElement('div');
      badge.className = 'notification-badge';
      badgeHost.style.position = 'relative';
      badgeHost.appendChild(badge);
    }
  }

  function getIcons() {
    return {
      success: '✓',
      error: '✕',
      warning: '⚠',
      info: 'ℹ'
    };
  }

  /**
   * Show a top alert.
   * Adapts to existing showToast() if present.
   */
  window.showAlert = function showAlert(message, type = 'info', duration = 4000) {
    // Prefer existing toast system.
    if (typeof window.showToast === 'function') {
      const t = type === 'error' ? 'error' : type;
      window.showToast(message, t);
      return;
    }

    // Fallback: render into #alertContainer if present.
    ensureNotificationPanel();

    const container = document.getElementById('alertContainer') || document.body;
    if (!document.getElementById('alertContainer')) {
      const el = document.createElement('div');
      el.id = 'alertContainer';
      el.className = 'alert-container';
      container.appendChild(el);
    }

    const icons = getIcons();
    const alert = document.createElement('div');
    alert.className = `alert ${type}`;

    alert.innerHTML = `
      <span class="alert-icon">${icons[type] || icons.info}</span>
      <span class="alert-text">${message}</span>
      <button class="alert-close" type="button">✕</button>
    `;

    alert.querySelector('.alert-close').addEventListener('click', () => alert.remove());

    document.getElementById('alertContainer').appendChild(alert);

    if (duration > 0) {
      setTimeout(() => alert.remove(), duration);
    }
  };

  function formatTimeAgo(date) {
    const seconds = Math.floor((Date.now() - date.getTime()) / 1000);
    if (seconds < 60) return 'just now';
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes}m ago`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h ago`;
    const days = Math.floor(hours / 24);
    return `${days}d ago`;
  }

  function updateNotificationPanelUI() {
    ensureNotificationPanel();

    const list = document.getElementById('notificationList');
    if (!notifications.length) {
      list.innerHTML = '<div class="notification-empty">No notifications</div>';
      updateNotificationBadge();
      return;
    }

    list.innerHTML = notifications
      .slice(0, 50)
      .map((n) => {
        return `
          <div class="notification-item ${n.type}">
            <strong>${escapeHtml(n.message)}</strong>
            <div class="notification-time">${formatTimeAgo(n.time)}</div>
          </div>
        `;
      })
      .join('');

    updateNotificationBadge();
  }

  function updateNotificationBadge() {
    const badge = document.querySelector('.notification-badge');
    if (!badge) return;

    const count = notifications.length;
    if (!count) {
      badge.remove();
      return;
    }

    badge.textContent = count > 9 ? '9+' : String(count);
  }

  window.toggleNotificationPanel = function toggleNotificationPanel() {
    ensureNotificationPanel();
    const panel = document.getElementById('notificationPanel');
    panel.classList.toggle('open');
  };

  window.closeNotificationPanel = function closeNotificationPanel() {
    ensureNotificationPanel();
    const panel = document.getElementById('notificationPanel');
    panel.classList.remove('open');
  };

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, (c) => ({
      '&': '&amp;',
      '<': '<',
      '>': '>',
      '"': '"',
      "'": '&#39;'
    }[c]));
  }

  function upsertNotifications(newItems) {
    let changed = false;

    for (const item of newItems) {
      const id = item.id ?? `${item.type ?? 'info'}-${item.time ?? Date.now()}-${item.message ?? ''}`;

      const exists = notifications.some((n) => String(n.id) === String(id));
      if (!exists) {
        changed = true;
        notifications.unshift({
          id,
          message: item.message || '',
          type: item.type || 'info',
          time: item.time ? new Date(item.time) : new Date(),
          data: item.data || {}
        });
      }
    }

    if (notifications.length > MAX_NOTIFICATIONS) {
      notifications = notifications.slice(0, MAX_NOTIFICATIONS);
      changed = true;
    }

    if (changed) updateNotificationPanelUI();
    return changed;
  }

  window.startNotificationPolling = function startNotificationPolling(endpoint, interval = 10000) {
    ensureNotificationPanel();

    if (pollingTimer) clearInterval(pollingTimer);

    const tick = async () => {
      try {
        const res = await fetch(endpoint, { headers: { Accept: 'application/json' } });
        const json = await res.json();
        if (!json || !json.success) return;

        const items = json.notifications || [];
        const changed = upsertNotifications(items);

        // Show alert for important types only.
        if (changed) {
          const important = items.filter((i) => ['error', 'warning'].includes(i.type));
          for (const imp of important.slice(0, 2)) {
            window.showAlert(imp.message || 'Notification', imp.type, 3500);
          }
        }
      } catch (e) {
        // Ignore polling errors
        console.error('Notification polling error:', e);
      }
    };

    tick();
    pollingTimer = setInterval(tick, interval);
  };
})();

