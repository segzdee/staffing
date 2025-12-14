/**
 * Toast Notification System
 * Displays real-time notifications from Laravel Reverb
 */

// Toast container (created on first use)
let toastContainer = null;

/**
 * Show a toast notification
 * @param {Object} notification - Notification object from server
 * @param {string} notification.title - Notification title
 * @param {string} notification.message - Notification message
 * @param {string} notification.type - Notification type (success, error, info, warning)
 * @param {number} duration - Auto-dismiss duration in ms (default: 5000)
 */
function showToast(notification, duration = 5000) {
    // Create container if it doesn't exist
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'toast-container';
        document.body.appendChild(toastContainer);
    }

    // Determine notification type
    const type = notification.type || 'info';
    const title = notification.title || 'Notification';
    const message = notification.message || notification.body || '';

    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');

    // Icon mapping
    const icons = {
        success: '✅',
        error: '❌',
        warning: '⚠️',
        info: 'ℹ️',
        default: '🔔'
    };

    // Build toast HTML
    toast.innerHTML = `
        <div class="toast-content">
            <div class="toast-icon">${icons[type] || icons.default}</div>
            <div class="toast-body">
                <div class="toast-title">${escapeHtml(title)}</div>
                ${message ? `<div class="toast-message">${escapeHtml(message)}</div>` : ''}
            </div>
            <button class="toast-close" aria-label="Close">&times;</button>
        </div>
    `;

    // Add to container
    toastContainer.appendChild(toast);

    // Trigger animation
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);

    // Auto-dismiss
    if (duration > 0) {
        setTimeout(() => {
            dismissToast(toast);
        }, duration);
    }

    // Close button handler
    const closeBtn = toast.querySelector('.toast-close');
    closeBtn.addEventListener('click', () => {
        dismissToast(toast);
    });

    // Click to dismiss
    toast.addEventListener('click', (e) => {
        if (e.target !== closeBtn && !closeBtn.contains(e.target)) {
            dismissToast(toast);
        }
    });

    return toast;
}

/**
 * Dismiss a toast notification
 * @param {HTMLElement} toast - Toast element to dismiss
 */
function dismissToast(toast) {
    toast.classList.remove('show');
    toast.classList.add('hide');
    
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 300);
}

/**
 * Update notification badge count
 * Fetches current unread count and updates badge
 */
function updateNotificationBadge() {
    // Find notification badge element
    const badge = document.querySelector('.notification-badge, .noti_notifications, [data-notification-count]');
    
    if (!badge) {
        return;
    }

    // Fetch current count via AJAX
    fetch('/api/notifications/unread-count', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        const count = data.count || 0;
        
        // Update badge text
        if (badge.textContent !== undefined) {
            badge.textContent = count > 0 ? count : '';
        } else if (badge.innerHTML !== undefined) {
            badge.innerHTML = count > 0 ? `<span class="badge badge-danger">${count}</span>` : '';
        }

        // Show/hide badge
        if (count > 0) {
            badge.style.display = 'inline-block';
            badge.classList.remove('d-none');
        } else {
            badge.style.display = 'none';
            badge.classList.add('d-none');
        }
    })
    .catch(error => {
        console.error('Failed to update notification badge:', error);
    });
}

/**
 * Escape HTML to prevent XSS
 * @param {string} text - Text to escape
 * @returns {string} Escaped text
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Export for use in other scripts
if (typeof window !== 'undefined') {
    window.showToast = showToast;
    window.updateNotificationBadge = updateNotificationBadge;
    window.dismissToast = dismissToast;
}
