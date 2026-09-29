<?php

return [
    /*
    | Each key is a module a super admin can grant to a company.
    | A missing or empty enabled_modules value means every module is on,
    | so companies created before this setting keep the full product
    | until a super admin saves an explicit list.
    */
    'all' => [
        'invoices',
        'dunning',
        'offers',
        'customers',
        'payments',
        'products',
        'expenses',
        'calendar',
        'documents',
        'reports',
        'datev',
    ],

    'labels' => [
        'invoices' => 'Rechnungen',
        'dunning' => 'Mahnwesen',
        'offers' => 'Angebote',
        'customers' => 'Kunden',
        'payments' => 'Zahlungen',
        'products' => 'Produkte',
        'expenses' => 'Ausgaben',
        'calendar' => 'Kalender',
        'documents' => 'Dokumente',
        'reports' => 'Berichte',
        'datev' => 'DATEV',
    ],

    /*
    | Earlier builds stored a single "invoicing" key for every module
    | except expenses. Expand it when reading saved settings.
    */
    'legacy' => [
        'invoicing' => [
            'invoices',
            'dunning',
            'offers',
            'customers',
            'payments',
            'products',
            'calendar',
            'documents',
            'reports',
            'datev',
        ],
    ],

    /*
    | Hard dependencies: enabling the key requires ALL listed modules.
    | Invoices need customers (customer_id is required on every invoice) and
    | payments (recording a payment is the only way an invoice becomes paid).
    | Dunning builds on both. Reports read invoice/customer data exclusively.
    */
    'requires' => [
        'invoices' => ['customers', 'payments'],
        'payments' => ['invoices'],
        'dunning' => ['invoices', 'payments'],
        'offers' => ['customers'],
        'reports' => ['invoices', 'customers'],
    ],

    /*
    | Enabling the key requires AT LEAST ONE of the listed modules.
    | DATEV exports invoices/payments or expenses — without either side of
    | the bookkeeping there is nothing to export.
    */
    'requires_any' => [
        'datev' => ['invoices', 'expenses'],
    ],

    /*
    | Settings tabs that require at least one of the listed modules.
    | Tabs not listed here stay available (company master data, profile, …).
    */
    'settings_tabs' => [
        'company' => ['invoices', 'offers', 'customers', 'products'],
        'reminders' => ['dunning'],
        'erechnung' => ['invoices'],
        'payment-methods' => ['payments', 'invoices'],
        'datev' => ['datev'],
    ],
];
