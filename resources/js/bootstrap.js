import _ from 'lodash';
window._ = _;

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Configure Echo for Laravel Reverb
if (import.meta.env.VITE_BROADCAST_DRIVER === 'reverb') {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT,
        wssPort: import.meta.env.VITE_REVERB_PORT,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
} else {
    // Fallback to Pusher if Reverb not configured
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: import.meta.env.VITE_PUSHER_APP_KEY,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
        forceTLS: true
    });
}

// Listen for notifications if user is authenticated
if (window.userId && window.Echo) {
    window.Echo.private(`user.${window.userId}`)
        .listen('NotificationCreated', (e) => {
            if (typeof showToast === 'function') {
                showToast({
                    title: e.notification.title || 'New Notification',
                    message: e.notification.message || '',
                    type: e.notification.type || 'info'
                });
            }
            if (typeof updateNotificationBadge === 'function') {
                updateNotificationBadge();
            }
        })
        .listen('message.new', (e) => {
            if (typeof showToast === 'function') {
                showToast({
                    title: 'New Message',
                    message: `${e.sender_name}: ${e.body.substring(0, 50)}...`,
                    type: 'info'
                });
            }
        })
        .listen('application.status.changed', (e) => {
            if (typeof showToast === 'function') {
                const status = e.status === 'accepted' ? 'success' : 'info';
                showToast({
                    title: 'Application Update',
                    message: `Your application for "${e.shift_title}" was ${e.status}`,
                    type: status
                });
            }
        });
}
