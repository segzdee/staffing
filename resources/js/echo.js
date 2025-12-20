/**
 * Echo Configuration
 *
 * This file re-exports Echo from bootstrap.js where it's already initialized.
 * It exists as a separate entry point for pages that need to ensure Echo is loaded
 * (like the messages page) without duplicating the initialization logic.
 *
 * Echo is initialized in bootstrap.js with support for:
 * - Laravel Reverb (WebSocket server)
 * - Pusher (fallback)
 *
 * The global window.Echo object is set up there and available throughout the app.
 */

// Import bootstrap to ensure Echo is initialized
import './bootstrap';

// Export Echo for use in modules that import this file directly
export default window.Echo;
