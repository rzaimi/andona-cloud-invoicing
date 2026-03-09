<?php

/**
 * Canonical unit list used across the entire application.
 *
 * This is the single source of truth for available units.
 * - PHP side:  config('units.default')
 * - Frontend:  usePage().props.auth.user.company.settings.default_units
 *              (or the useUnits() hook as a fallback)
 */
return [
    'default' => [
        'Stk.',    // Stück
        'Std.',    // Stunde
        'Tag',
        'Woche',
        'Monat',
        'Jahr',
        'm',
        'm²',
        'm³',
        'kg',
        'g',
        't',       // Tonne
        'l',
        'ml',
        'Paket',
        'Set',
        'Palette',
        'VE',      // Verpackungseinheit
    ],
];
