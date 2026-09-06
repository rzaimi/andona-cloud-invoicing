import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import ReactDOMServer from 'react-dom/server';
import { type RouteName, route } from 'ziggy-js';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createServer((page) =>
    createInertiaApp({
        page,
        render: ReactDOMServer.renderToString,
        title: (title) => title ? `${title} - ${appName}` : appName,
        resolve: (name) =>
            resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')) as Promise<never>,
        setup: ({ App, props }) => {
            // Provide a global Ziggy `route()` helper during SSR so page
            // components can resolve named routes on the server.
            (globalThis as any).route = (name: RouteName, params?: unknown, absolute?: boolean) =>
                route(name, params as never, absolute, {
                    ...(page.props as any).ziggy,
                    location: new URL((page.props as any).ziggy.location),
                });

            return <App {...props} />;
        },
    }),
);
