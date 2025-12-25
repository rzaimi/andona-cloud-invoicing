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

    loadingPromise = fetch('/api/routes', {
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        }
    })
        .then(async response => {
            // Check if response is HTML (redirect to login) instead of JSON
            const contentType = response.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                // Likely a redirect to login page - return null to use Inertia props instead
                const text = await response.text();
                if (text.includes('<!DOCTYPE html>') || text.includes('<html')) {
                    throw new Error('Authentication required - will use Inertia props');
                }
                throw new Error('Response is not JSON');
            }
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return response.json();
        })
        .then(config => {
            // Validate config structure
            if (!config || typeof config !== 'object') {
                throw new Error('Invalid routes configuration');
            }
            if (!config.routes || typeof config.routes !== 'object') {
                throw new Error('Routes configuration missing routes object');
            }
            ziggyConfig = config;
            return config;
        })
        .catch(error => {
            // Silently fail - routes will be available via Inertia props
            // Only log if it's not an authentication error
            if (!error.message.includes('Authentication required')) {
                console.warn('Failed to load routes from API:', error.message);
            }
            loadingPromise = null;
            return null;
        });

    return loadingPromise;
}

// Initialize Ziggy with routes loaded from API or Inertia props
export async function initializeZiggy(ziggyFromProps?: any) {
    // Try to use routes from Inertia props first (available immediately)
    if (ziggyFromProps && ziggyFromProps.routes) {
        ziggyConfig = ziggyFromProps;
        (window as any).Ziggy = ziggyConfig;
        (window as any).route = (name: RouteName, params?: RouteParams, absolute?: boolean) => 
            ziggyRoute(name, params, absolute, ziggyConfig);
        return;
    }

    // Fallback to API if props not available
    if (!ziggyConfig) {
        ziggyConfig = await loadZiggyRoutes();
    }

    // Only set up if we have valid config
    if (ziggyConfig && ziggyConfig.routes) {
        // Set global Ziggy object that ziggy-js expects
        (window as any).Ziggy = ziggyConfig;
        
        // Make route function available globally
        (window as any).route = (name: RouteName, params?: RouteParams, absolute?: boolean) => 
            ziggyRoute(name, params, absolute, ziggyConfig);
    }
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

