import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';
import { initializeZiggy } from './plugins/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Load routes before initializing the app to ensure they're available
// This completely hides routes from HTML source
initializeZiggy().then(() => {
    createInertiaApp({
        title: (title) => title ? `${title} - ${appName}` : appName,
        resolve: (name) => {
            return resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx'));
        },
        setup({ el, App, props }) {
            const root = createRoot(el);
            root.render(<App {...props} />);
        },
        progress: {
            color: '#4B5563',
        },
    });
}).catch(error => {
    console.error('Failed to initialize routes:', error);
    // Still render the app even if routes fail to load
    createInertiaApp({
        title: (title) => title ? `${title} - ${appName}` : appName,
        resolve: (name) => {
            return resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx'));
        },
        setup({ el, App, props }) {
            const root = createRoot(el);
            root.render(<App {...props} />);
        },
        progress: {
            color: '#4B5563',
        },
    });
});

// This will set light / dark mode on load...
initializeTheme();
