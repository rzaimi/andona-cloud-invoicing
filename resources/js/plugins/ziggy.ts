import { route as ziggyRoute, type RouteName, type RouteParams } from 'ziggy-js';
import { Ziggy } from '../ziggy';

let ziggyConfig: any = null;

// Initialize Ziggy with routes from generated file (reduces HTML payload size)
export function initializeZiggy() {
    if (ziggyConfig) {
        return;
    }

    // Use routes from generated ziggy.js file
    // This file is generated via: php artisan ziggy:generate
    // Override URL with current window location to avoid CORS issues
    ziggyConfig = {
        ...Ziggy,
        url: window.location.origin, // Use current origin instead of hardcoded URL
        port: window.location.port ? parseInt(window.location.port) : null,
        location: window.location.href,
    };

    // Set global Ziggy object that ziggy-js expects
    (window as any).Ziggy = ziggyConfig;
    
    // Make route function available globally
    (window as any).route = (name: RouteName, params?: RouteParams, absolute?: boolean) => 
        ziggyRoute(name, params, absolute, ziggyConfig);
}

// Export a function to get route (synchronous now since routes are loaded from file)
export function getRoute(name: RouteName, params?: RouteParams, absolute?: boolean): string {
    if (!ziggyConfig) {
        initializeZiggy();
    }
    return ziggyRoute(name, params, absolute, ziggyConfig);
}

// Make route function available synchronously
export function route(name: RouteName, params?: RouteParams, absolute?: boolean): string {
    if (!ziggyConfig) {
        initializeZiggy();
    }
    return ziggyRoute(name, params, absolute, ziggyConfig);
}

