"use client"

import { Head, useForm, usePage } from "@inertiajs/react"
import AppLayout from "@/layouts/app-layout"
import { RecurringProfileForm, type RecurringFormData } from "./profile-form"
import type { BreadcrumbItem, Customer } from "@/types"
import { route } from "ziggy-js"

interface CreateProps {
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

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Abo-Rechnungen", href: "/recurring-invoices" },
    { title: "Neue Abo-Rechnung" },
]

export default function RecurringInvoicesCreate() {
    const { customers, layouts, products } = usePage<CreateProps>().props as unknown as CreateProps

    const { data, setData, post, processing, errors } = useForm<RecurringFormData>({
        customer_id: "",
        layout_id: layouts.find((l) => l.is_default)?.id || "",
        name: "",
        description: "",
        vat_regime: "standard",
        tax_rate: 0.19,
        payment_method: "",
        payment_terms: "",
        skonto_percent: "",
        skonto_days: "",
        due_days_after_issue: 14,
        notes: "",
        bauvorhaben: "",
        auftragsnummer: "",
        interval_unit: "month",
        interval_count: 1,
        day_of_month: "",
        start_date: new Date().toISOString().split("T")[0],
        end_date: "",
        max_occurrences: "",
        auto_send: false,
        email_subject_template: "",
        email_body_template: "",
        items: [],
    })

    const onSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        post(route("recurring-invoices.store"))
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Neue Abo-Rechnung" />
            <div className="p-4 md:p-6">
                <h1 className="text-2xl font-semibold mb-6">Neue Abo-Rechnung</h1>
                <RecurringProfileForm
                    data={data}
                    setData={setData as any}
                    errors={errors as Record<string, string>}
                    customers={customers}
                    layouts={layouts}
                    products={products}
                    processing={processing}
                    onSubmit={onSubmit}
                    submitLabel="Abo anlegen"
                    cancelHref="/recurring-invoices"
                />
            </div>
        </AppLayout>
    )
}
