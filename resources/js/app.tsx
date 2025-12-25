import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';
import { initializeZiggy } from './plugins/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => title ? `${title} - ${appName}` : appName,
    resolve: (name) => {
        return resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx'));
    },
    setup({ el, App, props }) {
        const root = createRoot(el);

        // Initialize Ziggy with routes from Inertia props (available immediately)
        // This hides routes from HTML source while ensuring they're available
        if (props.initialPage?.props?.ziggy) {
            initializeZiggy(props.initialPage.props.ziggy).catch(error => {
                console.error('Failed to initialize routes from props:', error);
            });
        } else {
            // Fallback: try to load from API (may fail if not authenticated)
            initializeZiggy().catch(error => {
                console.warn('Routes not available yet, will load on next page navigation');
            });
        }

        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
