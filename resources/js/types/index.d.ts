export interface Company {
    id: string
    name: string
    email: string
    phone?: string
    address?: string
    postal_code?: string
    city?: string
    country: string
    tax_number?: string
    vat_number?: string
    commercial_register?: string
    managing_director?: string
    bank_name?: string
    bank_iban?: string
    bank_bic?: string
    website?: string
    logo?: string
    status: "active" | "inactive"
    settings?: Record<string, any>
    created_at: string
    updated_at: string
}

export interface User {
    id: string
    name: string
    email: string
    company_id?: string
    role: "admin" | "user"
    status: "active" | "inactive"
    company?: Company
    created_at: string
    updated_at: string
}

export interface Customer {
    id: string
    company_id: string
    number: string
    name: string
    email: string
    phone?: string
    address?: string
    postal_code?: string
    city?: string
    country: string
    tax_number?: string
    vat_number?: string
    contact_person?: string
    customer_type: "business" | "private"
    status: "active" | "inactive"
    company?: Company
    total_invoices?: number
    total_revenue?: number
    outstanding_amount?: number
    created_at: string
    updated_at: string
}

export interface Category {
    id: string
    company_id: string
    name: string
    description?: string
    color?: string
    icon?: string
    parent_id?: string
    sort_order: number
    is_active: boolean
    company?: Company
    parent?: Category
    children?: Category[]
    products?: Product[]
    products_count?: number
    created_at: string
    updated_at: string
}

export interface Warehouse {
    id: string
    company_id: string
    name: string
    description?: string
    address?: string
    postal_code?: string
    city?: string
    country?: string
    contact_person?: string
    phone?: string
    email?: string
    is_default: boolean
    is_active: boolean
    company?: Company
    warehouse_stocks?: WarehouseStock[]
    stock_movements?: StockMovement[]
    total_stock_value?: number
    product_count?: number
    created_at: string
    updated_at: string
}

export interface StockMovement {
    id: string
    company_id: string
    warehouse_id: string
    product_id: string
    user_id: string
    type: "in" | "out" | "adjustment" | "transfer"
    quantity: number
    unit_cost: number
    total_cost: number
    reason: string
    reference_type?: string
    reference_id?: string
    notes?: string
    company?: Company
    warehouse?: Warehouse
    product?: Product
    user?: User
    created_at: string
    updated_at: string
}

export interface WarehouseStock {
    id: string
    company_id: string
    warehouse_id: string
    product_id: string
    quantity: number
    reserved_quantity: number
    average_cost: number
    last_movement_at?: string
    company?: Company
    warehouse?: Warehouse
    product?: Product
    available_quantity?: number
    total_value?: number
    is_low_stock?: boolean
    is_out_of_stock?: boolean
    created_at: string
    updated_at: string
}

export interface Product {
    id: string
    company_id: string
    number: string
    name: string
    description?: string
    unit: string
    price: number
    cost_price?: number
    category?: string
    category_id?: string
    sku?: string
    barcode?: string
    tax_rate: number
    stock_quantity: number
    min_stock_level: number
    track_stock: boolean
    is_service: boolean
    status: "active" | "inactive" | "discontinued"
    custom_fields?: Record<string, any>
    company?: Company
    category_relation?: Category
    warehouse_stocks?: WarehouseStock[]
    stock_movements?: StockMovement[]
    formatted_price?: string
    profit_margin?: number
    created_at: string
    updated_at: string
}

export interface InvoiceItem {
    id: string
    invoice_id: string
    product_id?: string
    description: string
    quantity: number
    unit_price: number
    total: number
    unit: string
    sort_order: number
    product?: Product
    created_at: string
    updated_at: string
}

export interface Invoice {
    id: string
    number: string
    company_id: string
    customer_id: string
    user_id: string
    status: "draft" | "sent" | "paid" | "overdue" | "cancelled"
    issue_date: string
    due_date: string
    subtotal: number
    tax_rate: number
    tax_amount: number
    total: number
    notes?: string
    payment_method?: string
    payment_terms?: string
    layout_id?: string
    company?: Company
    customer?: Customer
    user?: User
    items?: InvoiceItem[]
    layout?: InvoiceLayout
    created_at: string
    updated_at: string
}

export interface OfferItem {
    id: string
    offer_id: string
    product_id?: string
    description: string
    quantity: number
    unit_price: number
    total: number
    unit: string
    sort_order: number
    product?: Product
    created_at: string
    updated_at: string
}

export interface Offer {
    id: string
    number: string
    company_id: string
    customer_id: string
    user_id: string
    status: "draft" | "sent" | "accepted" | "rejected" | "expired"
    issue_date: string
    valid_until: string
    subtotal: number
    tax_rate: number
    tax_amount: number
    total: number
    notes?: string
    terms_conditions?: string
    validity_days: number
    layout_id?: string
    converted_to_invoice_id?: string
    company?: Company
    customer?: Customer
    user?: User
    items?: OfferItem[]
    layout?: OfferLayout
    converted_to_invoice?: Invoice
    created_at: string
    updated_at: string
}

export interface InvoiceLayout {
    id: string
    company_id: string
    name: string
    type: "invoice" | "offer" | "both"
    template: string
    is_default: boolean
    settings?: Record<string, any>
    company?: Company
    created_at: string
    updated_at: string
}

export interface OfferLayout {
    id: string
    company_id: string
    name: string
    template: string
    is_default: boolean
    settings?: Record<string, any>
    company?: Company
    created_at: string
    updated_at: string
}

export interface CompanySetting {
    id: string
    company_id: string
    key: string
    value: any
    type: "string" | "integer" | "decimal" | "boolean" | "json"
    description?: string
    company?: Company
    created_at: string
    updated_at: string
}

export interface PaginatedResponse<T> {
    data: T[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number
    to: number
    links: Array<{
        url: string | null
        label: string
        active: boolean
    }>
}

export interface DashboardStats {
    total_customers: number
    total_invoices: number
    total_offers: number
    total_products: number
    total_revenue: number
    pending_invoices: number
    overdue_invoices: number
    active_offers: number
    expired_offers: number
    low_stock_products: number
    monthly_revenue: number
    monthly_invoices: number
    recent_invoices: Invoice[]
    recent_offers: Offer[]
    recent_customers: Customer[]
}

export interface BreadcrumbItem {
    title: string
    href?: string
}

export interface FormErrors {
    [key: string]: string[]
}

export interface ApiResponse<T = any> {
    data?: T
    message?: string
    errors?: FormErrors
    success: boolean
}
