import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';
import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import 'vuetify/styles';
import '@mdi/font/css/materialdesignicons.css';

// Extend Window interface for Vuetify instance
declare global {
    interface Window {
        __VUETIFY__: any;
    }
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const vuetify = createVuetify({
            components,
            directives,
            theme: {
                defaultTheme: 'light',
                themes: {
                    light: {
                        colors: {
                            primary: '#3B82F6',
                            secondary: '#8B5CF6',
                            accent: '#06B6D4',
                            error: '#EF4444',
                            warning: '#F59E0B',
                            info: '#3B82F6',
                            success: '#10B981',
                        },
                    },
                    dark: {
                        colors: {
                            primary: '#17265C',
                            secondary: '#193BB5',
                            accent: '#06B6D4',
                            error: '#EF4444',
                            warning: '#F59E0B',
                            info: '#3B82F6',
                            success: '#10B981',
                        },
                    },
                },
            },
        })
        
        // Make Vuetify instance available globally for theme switching
        if (typeof window !== 'undefined') {
            window.__VUETIFY__ = vuetify;
        }
        
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(vuetify)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
