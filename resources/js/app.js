/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import './bootstrap';

/**
 * Vue is not actively used in this project currently.
 * The project uses Alpine.js and Tailwind CSS for interactivity.
 *
 * If you need Vue components in the future, uncomment below:
 */

// import { createApp } from 'vue';
// import ExampleComponent from './components/ExampleComponent.vue';

// const app = createApp({});
// app.component('example-component', ExampleComponent);
// app.mount('#app');

/**
 * For Vue 2 legacy support (if needed):
 */
// import Vue from 'vue';
// window.Vue = Vue;
//
// Vue.component('example-component', require('./components/ExampleComponent.vue').default);
//
// const app = new Vue({
//     el: '#app',
// });

console.log('OvertimeStaff assets loaded via Vite');

/**
 * Live Shift Market Component
 */
import './components/live-shift-market.js';
