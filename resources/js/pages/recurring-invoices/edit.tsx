"use client"

import { Head, useForm, usePage } from "@inertiajs/react"
import AppLayout from "@/layouts/app-layout"
import { RecurringProfileForm, type RecurringFormData } from "./profile-form"
import type { BreadcrumbItem, Customer, RecurringInvoiceProfile } from "@/types"
import { route } from "ziggy-js"

interface EditProps {
    profile: RecurringInvoiceProfile
    customers: Customer[]
    layouts: Array<{ id: string; name: string; is_default?: boolean }>
    products: Array<{
        id: string
        name: string
        description?: string
        price: number
        unit: string
        tax_rate: number
    }>
}

export default function RecurringInvoicesEdit() {
    const { profile, customers, layouts, products } = usePage<EditProps>().props as unknown as EditProps

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Dashboard", href: "/dashboard" },
        { title: "Abo-Rechnungen", href: "/recurring-invoices" },
        { title: profile.name, href: `/recurring-invoices/${profile.id}` },
        { title: "Bearbeiten" },
    ]

    const { data, setData, put, processing, errors } = useForm<RecurringFormData>({
        customer_id: profile.customer_id,
        layout_id: profile.layout_id ?? "",
        name: profile.name,
        description: profile.description ?? "",
        vat_regime: profile.vat_regime ?? "standard",
        tax_rate: Number(profile.tax_rate ?? 0.19),
        payment_method: profile.payment_method ?? "",
        payment_terms: profile.payment_terms ?? "",
        skonto_percent: profile.skonto_percent ?? "",
        skonto_days: profile.skonto_days ?? "",
        due_days_after_issue: profile.due_days_after_issue ?? 14,
        notes: profile.notes ?? "",
        bauvorhaben: profile.bauvorhaben ?? "",
        auftragsnummer: profile.auftragsnummer ?? "",
        interval_unit: profile.interval_unit,
        interval_count: profile.interval_count,
        day_of_month: profile.day_of_month ?? "",
        start_date: profile.start_date,
        end_date: profile.end_date ?? "",
        max_occurrences: profile.max_occurrences ?? "",
        auto_send: profile.auto_send,
        email_subject_template: profile.email_subject_template ?? "",
        email_body_template: profile.email_body_template ?? "",
        items: (profile.items ?? []).map((it, idx) => ({
            id: it.id,
            product_id: it.product_id,
            description: it.description,
            quantity: Number(it.quantity),
            unit_price: Number(it.unit_price),
            unit: it.unit,
            tax_rate: it.tax_rate == null ? null : Number(it.tax_rate),
            discount_type: it.discount_type ?? null,
            discount_value: it.discount_value == null ? null : Number(it.discount_value),
            sort_order: it.sort_order ?? idx,
        })),
    })

    const onSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        put(route("recurring-invoices.update", profile.id))
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Abo "${profile.name}" bearbeiten`} />
            <div className="flex flex-1 flex-col gap-6">
                <h1 className="text-xl font-bold text-gray-900 dark:text-gray-100">Abo-Rechnung bearbeiten</h1>
                <RecurringProfileForm
                    data={data}
                    setData={setData as any}
                    errors={errors as Record<string, string>}
                    customers={customers}
                    layouts={layouts}
                    products={products}
                    processing={processing}
                    onSubmit={onSubmit}
                    submitLabel="Änderungen speichern"
                    cancelHref={`/recurring-invoices/${profile.id}`}
                />
            </div>
        </AppLayout>
    )
}
