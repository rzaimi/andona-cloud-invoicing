"use client"

import type React from "react"

import { useEffect, useRef, useState } from "react"
import { Head, router, useForm, usePage } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Textarea } from "@/components/ui/textarea"
import { Switch } from "@/components/ui/switch"
import { Badge } from "@/components/ui/badge"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog"
import { Plus, Edit, Trash2, Eye, Copy, Layout, Star, Download, AlertCircle } from "lucide-react"
import { Alert, AlertDescription } from "@/components/ui/alert"
import AppLayout from "@/layouts/app-layout"
import { route } from "ziggy-js"

interface InvoiceLayoutSettings {
    colors: {
        primary: string
        secondary: string
        accent: string
        text: string
        skonto: string
    }
    fonts: {
        heading: string
        body: string
        size: string
    }
    layout: {
        margin_top: number
        margin_bottom: number
        margin_left: number
        margin_right: number
    }
    branding: {
        logo_position: string
        logo_size: string
    }
    content: {
        show_company_address: boolean
        show_unit_column: boolean
        show_notes: boolean
        show_bank_details: boolean
        show_company_registration: boolean
        show_payment_terms: boolean
        show_item_codes: boolean
        show_row_number: boolean
        show_bauvorhaben: boolean
        show_auftragsnummer: boolean
        show_tax_breakdown: boolean
        custom_footer_text: string
    }
    template_specific?: Record<string, any>
}

interface InvoiceLayout {
    id: string
    name: string
    type: "invoice" | "offer" | "both"
    template: string
    is_default: boolean
    settings: InvoiceLayoutSettings
    created_at: string
    updated_at: string
}

interface Template {
    id: string
    name: string
    description: string
    preview_image: string
    features: string[]
    colors: string[]
    fonts: string[]
}

interface InvoiceLayoutsPageProps {
    company?: { id?: string | null; logo?: string | null; name?: string | null }
    layouts: InvoiceLayout[]
    templates: Template[]
}

// Default settings to ensure all properties exist
const getDefaultSettings = (): InvoiceLayoutSettings => ({
    colors: {
        primary: "#2563eb",
        secondary: "#64748b",
        accent: "#0ea5e9",
        text: "#1e293b",
        skonto: "#16a34a",
    },
    fonts: {
        heading: "Inter",
        body: "Inter",
        size: "small",
    },
    layout: {
        margin_top: 20,
        margin_bottom: 20,
        margin_left: 20,
        margin_right: 20,
    },
    branding: {
        logo_position: "top-left",
        logo_size: "medium",
    },
    content: {
        show_company_address: true,
        show_unit_column: true,
        show_notes: true,
        show_bank_details: true,
        show_company_registration: true,
        show_payment_terms: true,
        show_item_codes: true,
        show_row_number: false,
        show_bauvorhaben: true,
        show_auftragsnummer: true,
        show_tax_breakdown: true,
        custom_footer_text: "",
    },
})

// Apply template-specific defaults
const getTemplateDefaults = (templateId: string, templates: Template[]): Partial<InvoiceLayoutSettings> => {
    const template = templates.find((t) => t.id === templateId)
    if (!template) return {}

    // Base color and font settings
    const defaults: Partial<InvoiceLayoutSettings> = {
        colors: {
            primary: template.colors[0] || "#2563eb",
            secondary: template.colors[1] || "#64748b",
            accent: template.colors[2] || "#0ea5e9",
            text: template.colors[3] || "#1e293b",
        },
        fonts: {
            heading: template.fonts[0] || "Inter",
            body: template.fonts[1] || "Inter",
            size: "small",
        },
    }

    // Template-specific layout configurations
    switch (templateId) {
        case 'modern':
            defaults.layout = {
                margin_top: 20,
                margin_bottom: 20,
                margin_left: 20,
                margin_right: 20,
            }
            defaults.branding = {
                logo_position: "top-left",
                logo_size: "medium",
            }
            defaults.content = {
                show_company_address: true,
                show_unit_column: true,
                show_notes: true,
                show_bank_details: true,
                show_company_registration: true,
                show_payment_terms: true,
                show_item_codes: true,
                show_row_number: false,
                show_bauvorhaben: true,
                show_auftragsnummer: true,
                show_tax_breakdown: true,
                custom_footer_text: "",
            }
            break

        case 'professional':
            defaults.layout = {
                margin_top: 20,
                margin_bottom: 20,
                margin_left: 22,
                margin_right: 18,
            }
            defaults.branding = {
                logo_position: "top-left",
                logo_size: "medium",
            }
            defaults.content = {
                show_company_address: true,
                show_unit_column: true,
                show_notes: true,
                show_bank_details: true,
                show_company_registration: true,
                show_payment_terms: true,
                show_item_codes: true,
                show_row_number: false,
                show_bauvorhaben: true,
                show_auftragsnummer: true,
                show_tax_breakdown: true,
                custom_footer_text: "",
            }
            break

        case 'minimal':
            defaults.layout = {
                margin_top: 10,
                margin_bottom: 10,
                margin_left: 20,
                margin_right: 20,
            }
            defaults.branding = {
                logo_position: "top-left",
                logo_size: "medium",
            }
            defaults.content = {
                show_company_address: true,
                show_unit_column: true,
                show_notes: true,
                show_bank_details: false,
                show_company_registration: false,
                show_payment_terms: true,
                show_item_codes: false,
                show_row_number: false,
                show_bauvorhaben: true,
                show_auftragsnummer: true,
                show_tax_breakdown: false,
                custom_footer_text: "",
            }
            break

        case 'creative':
            defaults.layout = {
                margin_top: 30,
                margin_bottom: 20,
                margin_left: 25,
                margin_right: 15,
            }
            defaults.branding = {
                logo_position: "top-right",
                logo_size: "medium",
            }
            defaults.content = {
                show_company_address: true,
                show_unit_column: true,
                show_notes: true,
                show_bank_details: true,
                show_company_registration: true,
                show_payment_terms: true,
                show_item_codes: true,
                show_row_number: false,
                show_bauvorhaben: true,
                show_auftragsnummer: true,
                show_tax_breakdown: true,
                custom_footer_text: "",
            }
            break

        case 'elegant':
            defaults.layout = {
                margin_top: 30,
                margin_bottom: 30,
                margin_left: 30,
                margin_right: 30,
            }
            defaults.branding = {
                logo_position: "top-center",
                logo_size: "medium",
            }
            defaults.content = {
                show_company_address: true,
                show_unit_column: true,
                show_notes: true,
                show_bank_details: true,
                show_company_registration: true,
                show_payment_terms: true,
                show_item_codes: true,
                show_row_number: false,
                show_bauvorhaben: true,
                show_auftragsnummer: true,
                show_tax_breakdown: true,
                custom_footer_text: "",
            }
            break

        default:
            // Default settings if template not found
            break
    }

    return defaults
}

// Merge layout settings with defaults to ensure all properties exist
const mergeWithDefaults = (settings: Partial<InvoiceLayoutSettings> | null): InvoiceLayoutSettings => {
    const defaults = getDefaultSettings()

    if (!settings) return defaults

    return {
        colors: {
            ...defaults.colors,
            ...(settings.colors || {}),
        },
        fonts: {
            ...defaults.fonts,
            ...(settings.fonts || {}),
        },
        layout: {
            ...defaults.layout,
            ...(settings.layout || {}),
        },
        branding: {
            logo_position: settings.branding?.logo_position ?? defaults.branding.logo_position,
            logo_size: settings.branding?.logo_size ?? defaults.branding.logo_size,
        },
        content: {
            ...defaults.content,
            show_company_address: settings.content?.show_company_address ?? defaults.content.show_company_address,
            show_unit_column: settings.content?.show_unit_column ?? defaults.content.show_unit_column,
            show_notes: settings.content?.show_notes ?? defaults.content.show_notes,
            show_bank_details: settings.content?.show_bank_details ?? defaults.content.show_bank_details,
            show_company_registration: settings.content?.show_company_registration ?? defaults.content.show_company_registration,
            show_payment_terms: settings.content?.show_payment_terms ?? defaults.content.show_payment_terms,
            show_item_codes: settings.content?.show_item_codes ?? defaults.content.show_item_codes,
            show_row_number: settings.content?.show_row_number ?? defaults.content.show_row_number,
            show_bauvorhaben: settings.content?.show_bauvorhaben ?? defaults.content.show_bauvorhaben,
            show_auftragsnummer: settings.content?.show_auftragsnummer ?? defaults.content.show_auftragsnummer,
            show_tax_breakdown: settings.content?.show_tax_breakdown ?? defaults.content.show_tax_breakdown,
            custom_footer_text: settings.content?.custom_footer_text ?? defaults.content.custom_footer_text,
        },
        template_specific: settings.template_specific || {},
    }
}

// Module-scope constants for the Layout-bearbeiten dialog.
const SECTIONS: Array<{ id: "general" | "design" | "branding" | "content" | "footer"; label: string }> = [
    { id: "general",  label: "Allgemein" },
    { id: "design",   label: "Design" },
    { id: "branding", label: "Logo & Ränder" },
    { id: "content",  label: "Inhalt" },
    { id: "footer",   label: "Fußzeile" },
]

// Preset swatches shown under each color input — 99% of users pick a brand
// colour once, the native picker is still available for the last 1%.
const COLOR_PRESETS = [
    "#1f2937", "#2563eb", "#0891b2", "#059669",
    "#dc2626", "#7c3aed", "#ea580c", "#475569",
]

// Keys exposed as simple on/off toggles in the "Inhalt" section, in display
// order. Keep in sync with InvoiceLayoutSettings.content.
const CONTENT_TOGGLES: Array<{ key: "show_company_address" | "show_bauvorhaben" | "show_auftragsnummer" | "show_unit_column" | "show_item_codes" | "show_row_number" | "show_tax_breakdown" | "show_notes"; label: string }> = [
    { key: "show_company_address", label: "Firmenadresse im Header" },
    { key: "show_bauvorhaben",     label: "BV (Bauvorhaben)" },
    { key: "show_auftragsnummer",  label: "Auftragsnummer" },
    { key: "show_unit_column",     label: "Einheitsspalte" },
    { key: "show_item_codes",      label: "Artikelnummern" },
    { key: "show_row_number",      label: "Positionsnummern" },
    { key: "show_tax_breakdown",   label: "Steueraufschlüsselung" },
    { key: "show_notes",           label: "Notizen" },
]

const FOOTER_TOGGLES: Array<{ key: "show_bank_details" | "show_company_registration" | "show_payment_terms"; label: string }> = [
    { key: "show_bank_details",         label: "Bankverbindung" },
    { key: "show_company_registration", label: "Handelsregister" },
    { key: "show_payment_terms",        label: "Zahlungsbedingungen" },
]

export default function InvoiceLayoutsPage({ layouts, templates, company }: InvoiceLayoutsPageProps) {
    const page = usePage<{ flash?: { success?: string; error?: string }; csrf_token?: string }>()
    const { flash, csrf_token } = page.props
    const [isLayoutDialogOpen, setIsLayoutDialogOpen] = useState(false)
    const [editingLayout, setEditingLayout] = useState<InvoiceLayout | null>(null)
    const [previewLayout, setPreviewLayout] = useState<InvoiceLayout | null>(null)
    const [isPreviewOpen, setIsPreviewOpen] = useState(false)
    // Vertical-rail sections of the Layout-bearbeiten dialog.
    const [activeSection, setActiveSection] = useState<"general" | "design" | "branding" | "content" | "footer">("general")
    const [saveAndPreview, setSaveAndPreview] = useState(false)
    const [livePreviewHtml, setLivePreviewHtml] = useState<string>("")
    const [livePreviewLoading, setLivePreviewLoading] = useState(false)
    const [livePreviewError, setLivePreviewError] = useState<string>("")

    const form = useForm({
        name: "",
        type: "both" as "invoice" | "offer" | "both",
        template: "minimal",
        settings: getDefaultSettings(),
    })

    const [layoutFormData, setLayoutFormData] = useState({
        name: "",
        type: "both" as "invoice" | "offer" | "both",
        template: "minimal",
        settings: getDefaultSettings(),
    })

    const [isSubmitting, setIsSubmitting] = useState(false)

    const resetFormData = () => {
        setLayoutFormData({
            name: "",
            type: "both",
            template: "minimal",
            settings: getDefaultSettings(),
        })
    }

    const handleCreateLayout = () => {
        setEditingLayout(null)
        resetFormData()
        setIsLayoutDialogOpen(true)
    }

    const handleEditLayout = (layout: InvoiceLayout) => {
        setEditingLayout(layout)
        setLayoutFormData({
            name: layout.name || "",
            type: layout.type || "both",
            template: layout.template || "minimal",
            settings: mergeWithDefaults(layout.settings),
        })
        setIsLayoutDialogOpen(true)
    }

    const handleTemplateChange = (templateId: string) => {
        const templateDefaults = getTemplateDefaults(templateId, templates)
        setLayoutFormData({
            ...layoutFormData,
            template: templateId,
            settings: {
                ...layoutFormData.settings,
                ...templateDefaults,
            },
        })
    }

    const handleCloseDialog = () => {
        setIsLayoutDialogOpen(false)
        setEditingLayout(null)
        resetFormData()
        setActiveSection("general")
        setSaveAndPreview(false)
        setLivePreviewHtml("")
        setLivePreviewError("")
    }

    const handleSaveLayout = (e: React.FormEvent) => {
        e.preventDefault()
        
        // Check if name is actually filled
        if (!layoutFormData.name || layoutFormData.name.trim() === "") {
            form.setError("name", "Das Name-Feld ist erforderlich.")
            return
        }

        // Prepare submit data
        const submitData = {
            name: layoutFormData.name.trim(),
            type: layoutFormData.type,
            template: layoutFormData.template,
            settings: layoutFormData.settings,
            save_and_preview: saveAndPreview,
        }

        setIsSubmitting(true)

        const onSuccess = () => {
            setIsSubmitting(false)
            handleCloseDialog()
        }
        const onError = (errors: Record<string, string>) => {
            setIsSubmitting(false)
            if (errors) {
                Object.keys(errors).forEach((key) => {
                    const errorMessage = Array.isArray(errors[key]) ? errors[key][0] : errors[key]
                    form.setError(key as any, errorMessage)
                })
            }
        }

        if (editingLayout) {
            router.put(route("invoice-layouts.update", editingLayout.id), submitData, { onSuccess, onError })
        } else {
            router.post(route("invoice-layouts.store"), submitData, { onSuccess, onError })
        }
    }

    // Auto-open preview after redirect (?preview=<layoutId>)
    useEffect(() => {
        if (typeof window === "undefined") return
        const url = new URL(window.location.href)
        const previewId = url.searchParams.get("preview")
        if (!previewId) return

        const found = layouts.find((l) => String(l.id) === String(previewId))
        if (found) {
            setPreviewLayout(found)
            setIsPreviewOpen(true)
        }

        url.searchParams.delete("preview")
        window.history.replaceState({}, "", url.toString())
    }, [layouts])

    // Live preview: debounce changes and render HTML into iframe via srcDoc (no blob URLs = no CSP issues)
    useEffect(() => {
        if (!isLayoutDialogOpen) return

        const csrf =
            csrf_token ||
            (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content

        const controller = new AbortController()
        const t = window.setTimeout(async () => {
            try {
                setLivePreviewLoading(true)
                setLivePreviewError("")

                const res = await fetch("/invoice-layouts/preview-live", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
                    },
                    body: JSON.stringify({
                        type: layoutFormData.type,
                        template: layoutFormData.template,
                        settings: layoutFormData.settings,
                    }),
                    credentials: "same-origin",
                    signal: controller.signal,
                })

                if (!res.ok) {
                    setLivePreviewError(`Preview error (${res.status})`)
                    const errText = await res.text()
                    setLivePreviewHtml(
                        `<html><body style="font-family: system-ui; padding:16px;">
                          <h3 style="margin:0 0 8px;">Live preview failed</h3>
                          <pre style="white-space:pre-wrap; background:#f5f5f5; padding:12px; border-radius:8px;">${errText.replaceAll(
                              "<",
                              "&lt;"
                          )}</pre>
                        </body></html>`
                    )
                    return
                }

                const html = await res.text()
                setLivePreviewHtml(html)
            } catch (e) {
                // ignore aborts; keep last preview
            } finally {
                setLivePreviewLoading(false)
            }
        }, 650)

        return () => {
            controller.abort()
            window.clearTimeout(t)
        }
        // Intentionally depend on the full form state for live updates
    }, [isLayoutDialogOpen, layoutFormData])

    const handleDeleteLayout = (id: string) => {
        if (confirm("Sind Sie sicher, dass Sie dieses Layout löschen möchten?")) {
            router.delete(route("invoice-layouts.destroy", id))
        }
    }

    const handleSetDefaultLayout = (id: string) => {
        router.post(route("invoice-layouts.set-default", id))
    }

    const handleDuplicateLayout = (layout: InvoiceLayout) => {
        router.post(route("invoice-layouts.duplicate", layout.id))
    }

    const handlePreviewLayout = (layout: InvoiceLayout) => {
        const layoutWithDefaults = {
            ...layout,
            settings: mergeWithDefaults(layout.settings),
        }
        setPreviewLayout(layoutWithDefaults)
        setIsPreviewOpen(true)
    }

    const handleClosePreview = () => {
        setIsPreviewOpen(false)
        setPreviewLayout(null)
    }

    const getTypeLabel = (type: string) => {
        switch (type) {
            case "invoice":
                return "Nur Rechnungen"
            case "offer":
                return "Nur Angebote"
            case "both":
                return "Rechnungen & Angebote"
            default:
                return type
        }
    }

    const getTypeBadgeVariant = (type: string): "default" | "secondary" | "outline" => {
        switch (type) {
            case "invoice":
                return "default"
            case "offer":
                return "secondary"
            case "both":
                return "outline"
            default:
                return "outline"
        }
    }

    const updateColorSetting = (colorType: keyof typeof layoutFormData.settings.colors, value: string) => {
        setLayoutFormData({
            ...layoutFormData,
            settings: {
                ...layoutFormData.settings,
                colors: {
                    ...layoutFormData.settings.colors,
                    [colorType]: value,
                },
            },
        })
    }

    const updateFontSetting = (fontType: keyof typeof layoutFormData.settings.fonts, value: string) => {
        setLayoutFormData({
            ...layoutFormData,
            settings: {
                ...layoutFormData.settings,
                fonts: {
                    ...layoutFormData.settings.fonts,
                    [fontType]: value,
                },
            },
        })
    }

    const updateLayoutSetting = (layoutType: keyof typeof layoutFormData.settings.layout, value: number) => {
        setLayoutFormData({
            ...layoutFormData,
            settings: {
                ...layoutFormData.settings,
                layout: {
                    ...layoutFormData.settings.layout,
                    [layoutType]: value,
                },
            },
        })
    }

    const updateBrandingSetting = (
        brandingType: keyof typeof layoutFormData.settings.branding,
        value: boolean | string,
    ) => {
        setLayoutFormData({
            ...layoutFormData,
            settings: {
                ...layoutFormData.settings,
                branding: {
                    ...layoutFormData.settings.branding,
                    [brandingType]: value,
                },
            },
        })
    }

    const updateContentSetting = (contentType: keyof typeof layoutFormData.settings.content, value: boolean | string) => {
        setLayoutFormData({
            ...layoutFormData,
            settings: {
                ...layoutFormData.settings,
                content: {
                    ...layoutFormData.settings.content,
                    [contentType]: value,
                },
            },
        })
    }

    // Safe getter functions for preview
    const getPreviewFontFamily = (layout: InvoiceLayout | null) => {
        if (!layout?.settings?.fonts?.body) return "Inter"
        return layout.settings.fonts.body
    }

    const getPreviewTextColor = (layout: InvoiceLayout | null) => {
        if (!layout?.settings?.colors?.text) return "#1e293b"
        return layout.settings.colors.text
    }

    const getPreviewFontSize = (layout: InvoiceLayout | null) => {
        if (!layout?.settings?.fonts?.size) return "14px"
        const size = layout.settings.fonts.size
        switch (size) {
            case "small":
                return "12px"
            case "large":
                return "16px"
            default:
                return "14px"
        }
    }


    const hasErrors = Object.keys(form.errors).length > 0

    return (
        <AppLayout>
            <Head title="Rechnungslayouts" />

            <div className="space-y-6">
                {/* Success/Error Messages */}
                {flash?.success && (
                    <Alert>
                        <AlertDescription className="text-green-700">{flash.success}</AlertDescription>
                    </Alert>
                )}
                {flash?.error && (
                    <Alert variant="destructive">
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>{flash.error}</AlertDescription>
                    </Alert>
                )}

                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-xl font-bold text-gray-900 dark:text-gray-100">Rechnungslayouts</h1>
                        <p className="text-muted-foreground">Verwalten Sie Ihre Rechnungs- und Angebotslayouts</p>
                    </div>

                    <Dialog open={isLayoutDialogOpen} onOpenChange={setIsLayoutDialogOpen}>
                        <DialogTrigger asChild>
                            <Button onClick={handleCreateLayout}>
                                <Plus className="mr-2 h-4 w-4" />
                                Neues Layout
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="max-w-[1320px] h-[88vh] p-0 overflow-hidden flex flex-col">
                            <DialogHeader className="px-6 pt-5 pb-3 border-b">
                                <DialogTitle>{editingLayout ? "Layout bearbeiten" : "Neues Layout erstellen"}</DialogTitle>
                                <DialogDescription>
                                    {editingLayout
                                        ? "Änderungen werden live in der Vorschau rechts angezeigt."
                                        : "Ein neues Rechnungs- oder Angebotslayout anlegen."}
                                </DialogDescription>
                            </DialogHeader>

                            {hasErrors && (
                                <Alert variant="destructive" className="mx-6 mt-3">
                                    <AlertCircle className="h-4 w-4" />
                                    <AlertDescription>
                                        <div className="font-medium mb-2">Bitte korrigieren Sie die folgenden Fehler:</div>
                                        <ul className="list-disc list-inside space-y-1">
                                            {Object.entries(form.errors).map(([field, message]) => (
                                                <li key={field} className="text-sm">
                                                    <strong>{field}:</strong> {message as string}
                                                </li>
                                            ))}
                                        </ul>
                                    </AlertDescription>
                                </Alert>
                            )}

                            <form onSubmit={handleSaveLayout} className="flex-1 flex flex-col min-h-0">
                                <div className="flex-1 grid grid-cols-[170px_minmax(0,420px)_minmax(0,1fr)] min-h-0 overflow-hidden">
                                    {/* Left rail: section navigation */}
                                    <nav className="border-r bg-muted/30 py-4">
                                        <ul className="space-y-0.5 px-2">
                                            {SECTIONS.map((sec) => (
                                                <li key={sec.id}>
                                                    <button
                                                        type="button"
                                                        onClick={() => setActiveSection(sec.id)}
                                                        className={`w-full text-left px-3 py-2 rounded-md text-sm font-medium transition-colors ${
                                                            activeSection === sec.id
                                                                ? "bg-primary text-primary-foreground"
                                                                : "hover:bg-muted text-muted-foreground"
                                                        }`}
                                                    >
                                                        {sec.label}
                                                    </button>
                                                </li>
                                            ))}
                                        </ul>
                                    </nav>

                                    {/* Center: active section */}
                                    <div className="overflow-y-auto">
                                        <div className="p-6">
                                            {activeSection === "general" && (
                                                <div className="space-y-5">
                                                    <div>
                                                        <h3 className="text-base font-semibold mb-1">Allgemein</h3>
                                                        <p className="text-sm text-muted-foreground">Grundlegende Informationen zum Layout.</p>
                                                    </div>

                                                    <div className="space-y-2">
                                                        <Label htmlFor="layout-name">Name</Label>
                                                        <Input
                                                            id="layout-name"
                                                            value={layoutFormData.name}
                                                            onChange={(e) => setLayoutFormData({ ...layoutFormData, name: e.target.value })}
                                                            placeholder="z.B. Standard-Rechnung"
                                                        />
                                                    </div>

                                                    <div className="space-y-2">
                                                        <Label htmlFor="layout-type">Verwendung</Label>
                                                        <Select
                                                            value={layoutFormData.type}
                                                            onValueChange={(v) => setLayoutFormData({ ...layoutFormData, type: v as "invoice" | "offer" | "both" })}
                                                        >
                                                            <SelectTrigger id="layout-type"><SelectValue /></SelectTrigger>
                                                            <SelectContent>
                                                                <SelectItem value="both">Rechnungen & Angebote</SelectItem>
                                                                <SelectItem value="invoice">Nur Rechnungen</SelectItem>
                                                                <SelectItem value="offer">Nur Angebote</SelectItem>
                                                            </SelectContent>
                                                        </Select>
                                                    </div>

                                                    <div className="space-y-2">
                                                        <Label>Template</Label>
                                                        <div className="grid grid-cols-3 gap-2">
                                                            {templates.map((t) => (
                                                                <button
                                                                    type="button"
                                                                    key={t.id}
                                                                    onClick={() => handleTemplateChange(t.id)}
                                                                    className={`border rounded-lg p-3 text-left transition-all ${
                                                                        layoutFormData.template === t.id
                                                                            ? "border-primary bg-primary/5 ring-2 ring-primary/20"
                                                                            : "border-border hover:border-muted-foreground/40"
                                                                    }`}
                                                                >
                                                                    <div className="text-sm font-medium">{t.name}</div>
                                                                    <div className="text-xs text-muted-foreground mt-0.5 line-clamp-2">{t.description}</div>
                                                                </button>
                                                            ))}
                                                        </div>
                                                    </div>
                                                </div>
                                            )}

                                            {activeSection === "design" && (
                                                <div className="space-y-5">
                                                    <div>
                                                        <h3 className="text-base font-semibold mb-1">Design</h3>
                                                        <p className="text-sm text-muted-foreground">Farben und Schriftarten.</p>
                                                    </div>

                                                    <div className="space-y-4">
                                                        {([
                                                            { key: "primary", label: "Primärfarbe" },
                                                            { key: "secondary", label: "Sekundärfarbe" },
                                                            { key: "accent", label: "Akzent" },
                                                            { key: "text", label: "Text" },
                                                            { key: "skonto", label: "Skonto" },
                                                        ] as const).map(({ key, label }) => (
                                                            <div key={key} className="space-y-2">
                                                                <Label className="text-sm">{label}</Label>
                                                                <div className="flex items-center gap-2">
                                                                    <input
                                                                        type="color"
                                                                        value={layoutFormData.settings.colors[key]}
                                                                        onChange={(e) => updateColorSetting(key, e.target.value)}
                                                                        className="h-9 w-9 cursor-pointer rounded border bg-transparent"
                                                                    />
                                                                    <Input
                                                                        value={layoutFormData.settings.colors[key]}
                                                                        onChange={(e) => updateColorSetting(key, e.target.value)}
                                                                        className="w-28 font-mono text-xs"
                                                                        maxLength={7}
                                                                    />
                                                                    <div className="flex gap-1 ml-auto">
                                                                        {COLOR_PRESETS.map((c) => (
                                                                            <button
                                                                                type="button"
                                                                                key={c}
                                                                                onClick={() => updateColorSetting(key, c)}
                                                                                className="h-6 w-6 rounded border hover:scale-110 transition-transform"
                                                                                style={{ backgroundColor: c }}
                                                                                title={c}
                                                                            />
                                                                        ))}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        ))}
                                                    </div>

                                                    <div className="grid grid-cols-2 gap-3 pt-4 border-t">
                                                        <div className="space-y-2">
                                                            <Label>Schrift</Label>
                                                            <Select
                                                                value={layoutFormData.settings.fonts.body}
                                                                onValueChange={(v) => {
                                                                    updateFontSetting("body", v)
                                                                    updateFontSetting("heading", v)
                                                                }}
                                                            >
                                                                <SelectTrigger><SelectValue /></SelectTrigger>
                                                                <SelectContent>
                                                                    <SelectItem value="Inter">Inter</SelectItem>
                                                                    <SelectItem value="Roboto">Roboto</SelectItem>
                                                                    <SelectItem value="Arial">Arial</SelectItem>
                                                                    <SelectItem value="Helvetica">Helvetica</SelectItem>
                                                                    <SelectItem value="Georgia">Georgia</SelectItem>
                                                                    <SelectItem value="Times New Roman">Times New Roman</SelectItem>
                                                                </SelectContent>
                                                            </Select>
                                                        </div>
                                                        <div className="space-y-2">
                                                            <Label>Größe</Label>
                                                            <Select
                                                                value={layoutFormData.settings.fonts.size}
                                                                onValueChange={(v) => updateFontSetting("size", v)}
                                                            >
                                                                <SelectTrigger><SelectValue /></SelectTrigger>
                                                                <SelectContent>
                                                                    <SelectItem value="small">Klein</SelectItem>
                                                                    <SelectItem value="medium">Mittel</SelectItem>
                                                                    <SelectItem value="large">Groß</SelectItem>
                                                                </SelectContent>
                                                            </Select>
                                                        </div>
                                                    </div>
                                                </div>
                                            )}

                                            {activeSection === "branding" && (
                                                <div className="space-y-5">
                                                    <div>
                                                        <h3 className="text-base font-semibold mb-1">Logo & Ränder</h3>
                                                        <p className="text-sm text-muted-foreground">Logo-Position und Seitenränder.</p>
                                                    </div>

                                                    <div className="grid grid-cols-2 gap-3">
                                                        <div className="space-y-2">
                                                            <Label>Logo-Position</Label>
                                                            <Select
                                                                value={layoutFormData.settings.branding.logo_position}
                                                                onValueChange={(v) => updateBrandingSetting("logo_position", v)}
                                                            >
                                                                <SelectTrigger><SelectValue /></SelectTrigger>
                                                                <SelectContent>
                                                                    <SelectItem value="top-left">Oben links</SelectItem>
                                                                    <SelectItem value="top-center">Oben mittig</SelectItem>
                                                                    <SelectItem value="top-right">Oben rechts</SelectItem>
                                                                </SelectContent>
                                                            </Select>
                                                        </div>
                                                        <div className="space-y-2">
                                                            <Label>Logo-Größe</Label>
                                                            <Select
                                                                value={layoutFormData.settings.branding.logo_size}
                                                                onValueChange={(v) => updateBrandingSetting("logo_size", v)}
                                                            >
                                                                <SelectTrigger><SelectValue /></SelectTrigger>
                                                                <SelectContent>
                                                                    <SelectItem value="small">Klein</SelectItem>
                                                                    <SelectItem value="medium">Mittel</SelectItem>
                                                                    <SelectItem value="large">Groß</SelectItem>
                                                                </SelectContent>
                                                            </Select>
                                                        </div>
                                                    </div>

                                                    <div className="space-y-3 pt-4 border-t">
                                                        <Label className="text-sm">Seitenränder (mm)</Label>
                                                        <div className="grid grid-cols-2 gap-3">
                                                            {([
                                                                { key: "margin_top", label: "Oben" },
                                                                { key: "margin_bottom", label: "Unten" },
                                                                { key: "margin_left", label: "Links" },
                                                                { key: "margin_right", label: "Rechts" },
                                                            ] as const).map(({ key, label }) => (
                                                                <div key={key} className="space-y-1">
                                                                    <Label className="text-xs text-muted-foreground">{label}</Label>
                                                                    <Input
                                                                        type="number"
                                                                        min={0}
                                                                        max={50}
                                                                        value={layoutFormData.settings.layout[key]}
                                                                        onChange={(e) => updateLayoutSetting(key, Number(e.target.value))}
                                                                    />
                                                                </div>
                                                            ))}
                                                        </div>
                                                    </div>
                                                </div>
                                            )}

                                            {activeSection === "content" && (
                                                <div className="space-y-5">
                                                    <div>
                                                        <h3 className="text-base font-semibold mb-1">Inhalt</h3>
                                                        <p className="text-sm text-muted-foreground">Welche Felder und Spalten angezeigt werden.</p>
                                                    </div>

                                                    <div className="rounded-lg border divide-y">
                                                        {CONTENT_TOGGLES.map(({ key, label }) => (
                                                            <div key={key} className="flex items-center justify-between px-4 py-3">
                                                                <Label htmlFor={`tog-${key}`} className="cursor-pointer text-sm font-normal">{label}</Label>
                                                                <Switch
                                                                    id={`tog-${key}`}
                                                                    checked={!!layoutFormData.settings.content[key]}
                                                                    onCheckedChange={(v) => updateContentSetting(key, v)}
                                                                />
                                                            </div>
                                                        ))}
                                                    </div>
                                                </div>
                                            )}

                                            {activeSection === "footer" && (
                                                <div className="space-y-5">
                                                    <div>
                                                        <h3 className="text-base font-semibold mb-1">Fußzeile</h3>
                                                        <p className="text-sm text-muted-foreground">Was unten auf jeder Seite erscheint.</p>
                                                    </div>

                                                    <div className="rounded-lg border divide-y">
                                                        {FOOTER_TOGGLES.map(({ key, label }) => (
                                                            <div key={key} className="flex items-center justify-between px-4 py-3">
                                                                <Label htmlFor={`ftog-${key}`} className="cursor-pointer text-sm font-normal">{label}</Label>
                                                                <Switch
                                                                    id={`ftog-${key}`}
                                                                    checked={!!layoutFormData.settings.content[key]}
                                                                    onCheckedChange={(v) => updateContentSetting(key, v)}
                                                                />
                                                            </div>
                                                        ))}
                                                    </div>

                                                    <div className="space-y-2">
                                                        <Label htmlFor="custom-footer">Freier Fußzeilentext (optional)</Label>
                                                        <Textarea
                                                            id="custom-footer"
                                                            rows={3}
                                                            value={layoutFormData.settings.content.custom_footer_text}
                                                            onChange={(e) => updateContentSetting("custom_footer_text", e.target.value)}
                                                            placeholder="Zusätzlicher Text unten auf jeder Seite…"
                                                        />
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    {/* Right: live preview */}
                                    <div className="border-l bg-muted/20 flex flex-col min-h-0">
                                        <div className="px-4 py-2 border-b bg-background flex items-center justify-between">
                                            <span className="text-xs font-medium text-muted-foreground">Vorschau</span>
                                            {livePreviewLoading && <span className="text-xs text-muted-foreground">Aktualisiere…</span>}
                                        </div>
                                        <div className="flex-1 overflow-hidden">
                                            {livePreviewError ? (
                                                <div className="p-4 text-sm text-destructive">{livePreviewError}</div>
                                            ) : (
                                                <iframe
                                                    srcDoc={livePreviewHtml}
                                                    className="w-full h-full border-0 bg-white"
                                                    title="Layout-Vorschau"
                                                />
                                            )}
                                        </div>
                                    </div>
                                </div>

                                {/* Sticky footer action bar */}
                                <div className="border-t bg-background px-6 py-3 flex items-center justify-between">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => handleTemplateChange(layoutFormData.template)}
                                    >
                                        Auf Template-Standard zurücksetzen
                                    </Button>
                                    <div className="flex gap-2">
                                        <Button type="button" variant="outline" onClick={handleCloseDialog}>
                                            Abbrechen
                                        </Button>
                                        <Button type="submit" disabled={isSubmitting}>
                                            {isSubmitting ? "Speichern…" : "Speichern"}
                                        </Button>
                                    </div>
                                </div>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                {layouts.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <Layout className="h-12 w-12 text-gray-400 mb-4" />
                            <h3 className="text-lg font-semibold text-foreground mb-2">Keine Layouts vorhanden</h3>
                            <p className="text-muted-foreground text-center mb-4">
                                Erstellen Sie Ihr erstes Rechnungslayout, um professionelle Dokumente zu generieren.
                            </p>
                            <Button onClick={handleCreateLayout}>
                                <Plus className="mr-2 h-4 w-4" />
                                Erstes Layout erstellen
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardHeader>
                            <CardTitle>Verfügbare Layouts ({layouts.length})</CardTitle>
                            <CardDescription>Verwalten Sie Ihre Rechnungs- und Angebotslayouts</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Layout Name</TableHead>
                                        <TableHead>Typ</TableHead>
                                        <TableHead>Template</TableHead>
                                        <TableHead>Standard</TableHead>
                                        <TableHead>Erstellt</TableHead>
                                        <TableHead className="text-right">Aktionen</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {layouts.map((layout) => (
                                        <TableRow key={layout.id}>
                                            <TableCell className="font-medium">{layout.name}</TableCell>
                                            <TableCell>
                                                <Badge variant={getTypeBadgeVariant(layout.type)}>{getTypeLabel(layout.type)}</Badge>
                                            </TableCell>
                                            <TableCell className="capitalize">
                                                {templates.find((t) => t.id === layout.template)?.name || layout.template}
                                            </TableCell>
                                            <TableCell>
                                                {layout.is_default ? (
                                                    <Badge className="bg-yellow-100 text-yellow-800 border-yellow-300">
                                                        <Star className="w-3 h-3 mr-1 fill-current" />
                                                        Standard
                                                    </Badge>
                                                ) : (
                                                    <Button variant="outline" size="sm" onClick={() => handleSetDefaultLayout(layout.id)}>
                                                        Als Standard setzen
                                                    </Button>
                                                )}
                                            </TableCell>
                                            <TableCell>{new Date(layout.created_at).toLocaleDateString("de-DE")}</TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end space-x-2">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => handlePreviewLayout(layout)}
                                                        title="Vorschau anzeigen"
                                                    >
                                                        <Eye className="h-4 w-4" />
                                                    </Button>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => handleEditLayout(layout)}
                                                        title="Layout bearbeiten"
                                                    >
                                                        <Edit className="h-4 w-4" />
                                                    </Button>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => handleDuplicateLayout(layout)}
                                                        title="Layout duplizieren"
                                                    >
                                                        <Copy className="h-4 w-4" />
                                                    </Button>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => handleDeleteLayout(layout.id)}
                                                        disabled={layout.is_default}
                                                        className="text-red-600 hover:text-red-700 disabled:opacity-50"
                                                        title={layout.is_default ? "Standard-Layout kann nicht gelöscht werden" : "Layout löschen"}
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}

                {/* Preview Dialog */}
                <Dialog open={isPreviewOpen} onOpenChange={setIsPreviewOpen}>
                    <DialogContent className="max-w-6xl max-h-[90vh] overflow-hidden flex flex-col">
                        <DialogHeader>
                            <DialogTitle>Layout Vorschau: {previewLayout?.name}</DialogTitle>
                            <DialogDescription>Vorschau des Layouts mit Beispieldaten - identisch zur PDF-Ansicht</DialogDescription>
                        </DialogHeader>

                        {previewLayout ? (
                            <div className="flex-1 overflow-hidden border rounded-lg bg-white">
                                <iframe
                                    src={route("invoice-layouts.preview", previewLayout.id)}
                                    className="w-full h-full min-h-[600px] border-0"
                                    title="Invoice Layout Preview"
                                    style={{ minHeight: '600px' }}
                                />
                            </div>
                        ) : (
                            <div className="flex items-center justify-center py-8">
                                <p>Layout wird geladen...</p>
                            </div>
                        )}

                        <DialogFooter>
                            <Button variant="outline" onClick={handleClosePreview}>
                                Schließen
                            </Button>
                            {previewLayout && (
                                <Button onClick={() => window.open(route("invoice-layouts.preview", previewLayout.id), "_blank")}>
                                    <Download className="mr-2 h-4 w-4" />
                                    In neuem Tab öffnen
                                </Button>
                            )}
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    )
}
