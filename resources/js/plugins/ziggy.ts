import { route as ziggyRoute, type RouteName, type RouteParams } from 'ziggy-js';

let ziggyConfig: any = null;
let loadingPromise: Promise<any> | null = null;

// Load Ziggy routes dynamically from API endpoint to completely hide them from HTML source
async function loadZiggyRoutes(): Promise<any> {
    if (ziggyConfig) {
        return ziggyConfig;
    }

    if (loadingPromise) {
        return loadingPromise;
    }

    loadingPromise = fetch('/api/routes')
        .then(response => response.json())
        .then(config => {
            ziggyConfig = config;
            return config;
        })
        .catch(error => {
            console.error('Failed to load routes:', error);
            loadingPromise = null;
            throw error;
        });

    return loadingPromise;
}

// Initialize Ziggy with routes loaded from API
export async function initializeZiggy() {
    if (!ziggyConfig) {
        ziggyConfig = await loadZiggyRoutes();
    }

    // Set global Ziggy object that ziggy-js expects
    (window as any).Ziggy = ziggyConfig;
    
    // Make route function available globally
    (window as any).route = (name: RouteName, params?: RouteParams, absolute?: boolean) => 
        ziggyRoute(name, params, absolute, ziggyConfig);
}

// Export a function to get route that waits for routes to load
export async function getRoute(name: RouteName, params?: RouteParams, absolute?: boolean): Promise<string> {
    if (!ziggyConfig) {
        await initializeZiggy();
    }
    return ziggyRoute(name, params, absolute, ziggyConfig);
}

// Make route function available synchronously (will use cached config or wait if needed)
export function route(name: RouteName, params?: RouteParams, absolute?: boolean): string {
    if (!ziggyConfig) {
        // If routes aren't loaded yet, throw an error or return a placeholder
        console.warn('Routes not loaded yet, route() called too early');
        return '#';
    }
    return ziggyRoute(name, params, absolute, ziggyConfig);
}

