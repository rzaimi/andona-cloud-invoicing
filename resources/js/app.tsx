import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';
import { initializeZiggy } from './plugins/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

function readInitialPage(): Record<string, unknown> | undefined {
    const script = document.querySelector('script[data-page="app"][type="application/json"]');
    if (script?.textContent) {
        return JSON.parse(script.textContent) as Record<string, unknown>;
    }

    // Fallback for stale compiled Blade views that still use Inertia v2 markup.
    const el = document.getElementById('app');
    if (el?.dataset.page) {
        return JSON.parse(el.dataset.page) as Record<string, unknown>;
    }

    return undefined;
}

createInertiaApp({
    page: readInitialPage(),
    title: (title) => title ? `${title} - ${appName}` : appName,
    resolve: (name) =>
        resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')) as Promise<never>,
    setup({ el, App, props }) {
        const root = createRoot(el);

        // Initialize Ziggy with routes from generated file
        // Routes are loaded from resources/js/ziggy.js (generated via: php artisan ziggy:generate)
        // This completely removes routes from HTML payload
        initializeZiggy();

        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
