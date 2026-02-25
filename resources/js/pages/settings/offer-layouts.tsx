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
import { Tabs, TabsContent } from "@/components/ui/tabs"
import { Plus, Edit, Trash2, Eye, Copy, Layout, Star, Download, AlertCircle } from "lucide-react"
import { Alert, AlertDescription } from "@/components/ui/alert"
import AppLayout from "@/layouts/app-layout"
import { route } from "ziggy-js"

interface OfferLayoutSettings {
    colors: {
        primary: string
        secondary: string
        accent: string
        text: string
    }
    fonts: {
        heading: string
        body: string
        size: string
    }
    layout: {
        header_height: number
        footer_height: number
        margin_top: number
        margin_bottom: number
        margin_left: number
        margin_right: number
    }
    branding: {
        show_logo: boolean
        logo_position: string
        company_info_position: string
        show_header_line: boolean
        show_footer_line: boolean
        show_footer: boolean
    }
    content: {
        show_company_address: boolean
        show_company_contact: boolean
        show_customer_number: boolean
        show_tax_number: boolean
        show_unit_column: boolean
        show_notes: boolean
        show_bank_details: boolean
        show_company_registration: boolean
        show_payment_terms: boolean
        show_validity_period: boolean
        show_item_images: boolean
        show_item_codes: boolean
        show_row_number: boolean
        show_bauvorhaben: boolean
        show_tax_breakdown: boolean
        custom_footer_text: string
    }
    template_specific?: Record<string, any>
}

interface OfferLayout {
    id: string
    name: string
    template: string
    is_default: boolean
    settings: OfferLayoutSettings
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

interface OfferLayoutsPageProps {
    company?: { id?: string | null; logo?: string | null; name?: string | null }
    layouts: OfferLayout[]
    templates: Template[]
}

const getDefaultSettings = (): OfferLayoutSettings => ({
    colors: {
        primary: "#2563eb",
        secondary: "#64748b",
        accent: "#0ea5e9",
        text: "#1e293b",
    },
    fonts: {
        heading: "Inter",
        body: "Inter",
        size: "medium",
    },
    layout: {
        header_height: 120,
        footer_height: 80,
        margin_top: 20,
        margin_bottom: 20,
        margin_left: 20,
        margin_right: 20,
    },
    branding: {
        show_logo: true,
        logo_position: "top-left",
        company_info_position: "top-right",
        show_header_line: true,
        show_footer_line: true,
        show_footer: true,
    },
    content: {
        show_company_address: true,
        show_company_contact: true,
        show_customer_number: true,
        show_tax_number: true,
        show_unit_column: true,
        show_notes: true,
        show_bank_details: true,
        show_company_registration: true,
        show_payment_terms: true,
        show_validity_period: true,
        show_item_images: false,
        show_item_codes: true,
        show_row_number: false,
        show_bauvorhaben: true,
        show_tax_breakdown: true,
        custom_footer_text: "",
    },
})

const getTemplateDefaults = (templateId: string, templates: Template[]): Partial<OfferLayoutSettings> => {
    const template = templates.find((t) => t.id === templateId)
    if (!template) return {}

    const defaults: Partial<OfferLayoutSettings> = {
        colors: {
            primary: template.colors[0] || "#2563eb",
            secondary: template.colors[1] || "#64748b",
            accent: template.colors[2] || "#0ea5e9",
            text: template.colors[3] || "#1e293b",
        },
        fonts: {
            heading: template.fonts[0] || "Inter",
            body: template.fonts[1] || "Inter",
            size: "medium",
        },
    }

    switch (templateId) {
        case 'modern':
            defaults.layout = { header_height: 120, footer_height: 80, margin_top: 20, margin_bottom: 20, margin_left: 20, margin_right: 20 }
            defaults.branding = { show_logo: true, logo_position: "top-left", company_info_position: "top-left", show_header_line: true, show_footer_line: false, show_footer: true }
            defaults.content = { show_company_address: true, show_company_contact: true, show_customer_number: true, show_tax_number: true, show_unit_column: true, show_notes: true, show_bank_details: true, show_company_registration: true, show_payment_terms: true, show_validity_period: true, show_item_images: false, show_item_codes: true, show_row_number: false, show_bauvorhaben: true, show_tax_breakdown: true, custom_footer_text: "" }
            break
        case 'classic':
            defaults.layout = { header_height: 100, footer_height: 60, margin_top: 15, margin_bottom: 15, margin_left: 15, margin_right: 15 }
            defaults.branding = { show_logo: true, logo_position: "top-center", company_info_position: "top-center", show_header_line: true, show_footer_line: true, show_footer: true }
            defaults.content = { show_company_address: true, show_company_contact: true, show_customer_number: true, show_tax_number: true, show_unit_column: true, show_notes: true, show_bank_details: true, show_company_registration: true, show_payment_terms: true, show_validity_period: true, show_item_images: false, show_item_codes: true, show_row_number: true, show_bauvorhaben: true, show_tax_breakdown: true, custom_footer_text: "" }
            break
        case 'minimal':
            defaults.layout = { header_height: 80, footer_height: 50, margin_top: 10, margin_bottom: 10, margin_left: 20, margin_right: 20 }
            defaults.branding = { show_logo: false, logo_position: "top-left", company_info_position: "top-left", show_header_line: false, show_footer_line: false, show_footer: true }
            defaults.content = { show_company_address: true, show_company_contact: false, show_customer_number: false, show_tax_number: false, show_unit_column: true, show_notes: true, show_bank_details: false, show_company_registration: false, show_payment_terms: true, show_validity_period: true, show_item_images: false, show_item_codes: false, show_row_number: false, show_bauvorhaben: true, show_tax_breakdown: false, custom_footer_text: "" }
            break
        case 'professional':
            defaults.layout = { header_height: 130, footer_height: 90, margin_top: 20, margin_bottom: 20, margin_left: 20, margin_right: 20 }
            defaults.branding = { show_logo: true, logo_position: "top-left", company_info_position: "top-right", show_header_line: true, show_footer_line: true, show_footer: true }
            defaults.content = { show_company_address: true, show_company_contact: true, show_customer_number: true, show_tax_number: true, show_unit_column: true, show_notes: true, show_bank_details: true, show_company_registration: true, show_payment_terms: true, show_validity_period: true, show_item_images: false, show_item_codes: true, show_row_number: false, show_bauvorhaben: true, show_tax_breakdown: true, custom_footer_text: "" }
            break
        case 'elegant':
            defaults.layout = { header_height: 160, footer_height: 110, margin_top: 30, margin_bottom: 30, margin_left: 30, margin_right: 30 }
            defaults.branding = { show_logo: true, logo_position: "top-center", company_info_position: "top-center", show_header_line: true, show_footer_line: true, show_footer: true }
            defaults.content = { show_company_address: true, show_company_contact: true, show_customer_number: true, show_tax_number: true, show_unit_column: true, show_notes: true, show_bank_details: true, show_company_registration: true, show_payment_terms: true, show_validity_period: true, show_item_images: false, show_item_codes: true, show_row_number: false, show_bauvorhaben: true, show_tax_breakdown: true, custom_footer_text: "" }
            break
        default:
            break
    }

    return defaults
}

const mergeWithDefaults = (settings: Partial<OfferLayoutSettings> | null): OfferLayoutSettings => {
    const defaults = getDefaultSettings()
    if (!settings) return defaults

    return {
        colors: { ...defaults.colors, ...(settings.colors || {}) },
        fonts: { ...defaults.fonts, ...(settings.fonts || {}) },
        layout: { ...defaults.layout, ...(settings.layout || {}) },
        branding: {
            ...defaults.branding,
            show_logo: settings.branding?.show_logo ?? defaults.branding.show_logo,
            logo_position: settings.branding?.logo_position ?? defaults.branding.logo_position,
            company_info_position: settings.branding?.company_info_position ?? defaults.branding.company_info_position,
            show_header_line: settings.branding?.show_header_line ?? defaults.branding.show_header_line,
            show_footer_line: settings.branding?.show_footer_line ?? defaults.branding.show_footer_line,
            show_footer: settings.branding?.show_footer ?? defaults.branding.show_footer,
        },
        content: {
            ...defaults.content,
            show_company_address: settings.content?.show_company_address ?? defaults.content.show_company_address,
            show_company_contact: settings.content?.show_company_contact ?? defaults.content.show_company_contact,
            show_customer_number: settings.content?.show_customer_number ?? defaults.content.show_customer_number,
            show_tax_number: settings.content?.show_tax_number ?? defaults.content.show_tax_number,
            show_unit_column: settings.content?.show_unit_column ?? defaults.content.show_unit_column,
            show_notes: settings.content?.show_notes ?? defaults.content.show_notes,
            show_bank_details: settings.content?.show_bank_details ?? defaults.content.show_bank_details,
            show_company_registration: settings.content?.show_company_registration ?? defaults.content.show_company_registration,
            show_payment_terms: settings.content?.show_payment_terms ?? defaults.content.show_payment_terms,
            show_validity_period: settings.content?.show_validity_period ?? defaults.content.show_validity_period,
            show_item_images: settings.content?.show_item_images ?? defaults.content.show_item_images,
            show_item_codes: settings.content?.show_item_codes ?? defaults.content.show_item_codes,
            show_row_number: settings.content?.show_row_number ?? defaults.content.show_row_number,
            show_bauvorhaben: settings.content?.show_bauvorhaben ?? defaults.content.show_bauvorhaben,
            show_tax_breakdown: settings.content?.show_tax_breakdown ?? defaults.content.show_tax_breakdown,
            custom_footer_text: settings.content?.custom_footer_text ?? defaults.content.custom_footer_text,
        },
        template_specific: settings.template_specific || {},
    }
}

export default function OfferLayoutsPage({ layouts, templates, company }: OfferLayoutsPageProps) {
    const page = usePage<{ flash?: { success?: string; error?: string }; csrf_token?: string }>()
    const { flash, csrf_token } = page.props

    const [isLayoutDialogOpen, setIsLayoutDialogOpen] = useState(false)
    const [editingLayout, setEditingLayout] = useState<OfferLayout | null>(null)
    const [previewLayout, setPreviewLayout] = useState<OfferLayout | null>(null)
    const [isPreviewOpen, setIsPreviewOpen] = useState(false)
    const [builderTab, setBuilderTab] = useState<"basic" | "design" | "layout" | "content">("basic")
    const [saveAndPreview, setSaveAndPreview] = useState(false)
    const [livePreviewHtml, setLivePreviewHtml] = useState<string>("")
    const [livePreviewLoading, setLivePreviewLoading] = useState(false)
    const [livePreviewError, setLivePreviewError] = useState<string>("")
    const [livePreviewPdfUrl, setLivePreviewPdfUrl] = useState<string>("")
    const [isSubmitting, setIsSubmitting] = useState(false)
    const [logoPreviewUrl, setLogoPreviewUrl] = useState<string | null>(null)

    const form = useForm({
        name: "",
        template: "minimal",
        settings: getDefaultSettings(),
    })

    const logoForm = useForm({
        _method: "put",
        logo: null as any,
    })

    const [layoutFormData, setLayoutFormData] = useState({
        name: "",
        template: "minimal",
        settings: getDefaultSettings(),
    })

    const leftScrollRef = useRef<HTMLDivElement | null>(null)

    const resetFormData = () => {
        setLayoutFormData({ name: "", template: "minimal", settings: getDefaultSettings() })
    }

    const handleCreateLayout = () => {
        setEditingLayout(null)
        resetFormData()
        setIsLayoutDialogOpen(true)
    }

    const handleEditLayout = (layout: OfferLayout) => {
        setEditingLayout(layout)
        setLayoutFormData({
            name: layout.name || "",
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
            settings: { ...layoutFormData.settings, ...templateDefaults },
        })
    }

    const handleCloseDialog = () => {
        setIsLayoutDialogOpen(false)
        setEditingLayout(null)
        resetFormData()
        setBuilderTab("basic")
        setSaveAndPreview(false)
        setLivePreviewHtml("")
        setLivePreviewError("")
        if (livePreviewPdfUrl) URL.revokeObjectURL(livePreviewPdfUrl)
        setLivePreviewPdfUrl("")
    }

    const handleSaveLayout = (e: React.FormEvent) => {
        e.preventDefault()

        if (!layoutFormData.name || layoutFormData.name.trim() === "") {
            form.setError("name", "Das Name-Feld ist erforderlich.")
            return
        }

        const submitData = {
            name: layoutFormData.name.trim(),
            template: layoutFormData.template,
            settings: layoutFormData.settings,
            save_and_preview: saveAndPreview,
        }

        setIsSubmitting(true)

        const onSuccess = () => { setIsSubmitting(false); handleCloseDialog() }
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
            router.put(route("offer-layouts.update", editingLayout.id), submitData, { onSuccess, onError })
        } else {
            router.post(route("offer-layouts.store"), submitData, { onSuccess, onError })
        }
    }

    const goToStep = (tab: "basic" | "design" | "layout" | "content", anchorId?: string) => {
        setBuilderTab(tab)
        if (!anchorId) return
        window.setTimeout(() => {
            const container = leftScrollRef.current
            if (!container) return
            const el = container.querySelector(`#${anchorId}`) as HTMLElement | null
            if (!el) return
            container.scrollTo({ top: Math.max(0, el.offsetTop - 12), behavior: "smooth" })
        }, 80)
    }

    // Auto-open preview after redirect (?preview=<layoutId>)
    useEffect(() => {
        if (typeof window === "undefined") return
        const url = new URL(window.location.href)
        const previewId = url.searchParams.get("preview")
        if (!previewId) return
        const found = layouts.find((l) => String(l.id) === String(previewId))
        if (found) { setPreviewLayout(found); setIsPreviewOpen(true) }
        url.searchParams.delete("preview")
        window.history.replaceState({}, "", url.toString())
    }, [layouts])

    // Live preview: debounce changes and render a real PDF into iframe
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

                const res = await fetch("/offer-layouts/preview-live-pdf", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
                    },
                    body: JSON.stringify({
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
                        `<html><body style="font-family:system-ui;padding:16px;"><h3>Live preview failed</h3><pre style="white-space:pre-wrap;background:#f5f5f5;padding:12px;border-radius:8px;">${errText.replaceAll("<", "&lt;")}</pre></body></html>`
                    )
                    return
                }

                const blob = await res.blob()
                const url = URL.createObjectURL(blob)
                setLivePreviewPdfUrl((prev) => { if (prev) URL.revokeObjectURL(prev); return url })
            } catch {
                // ignore aborts
            } finally {
                setLivePreviewLoading(false)
            }
        }, 650)

        return () => { controller.abort(); window.clearTimeout(t) }
    }, [isLayoutDialogOpen, layoutFormData])

    const handleDeleteLayout = (id: string) => {
        if (confirm("Sind Sie sicher, dass Sie dieses Angebotslayout löschen möchten?")) {
            router.delete(route("offer-layouts.destroy", id))
        }
    }

    const handleSetDefaultLayout = (id: string) => {
        router.post(route("offer-layouts.set-default", id))
    }

    const handleDuplicateLayout = (layout: OfferLayout) => {
        router.post(route("offer-layouts.duplicate", layout.id))
    }

    const handlePreviewLayout = (layout: OfferLayout) => {
        setPreviewLayout({ ...layout, settings: mergeWithDefaults(layout.settings) })
        setIsPreviewOpen(true)
    }

    const updateColorSetting = (colorType: keyof typeof layoutFormData.settings.colors, value: string) => {
        setLayoutFormData({ ...layoutFormData, settings: { ...layoutFormData.settings, colors: { ...layoutFormData.settings.colors, [colorType]: value } } })
    }

    const updateFontSetting = (fontType: keyof typeof layoutFormData.settings.fonts, value: string) => {
        setLayoutFormData({ ...layoutFormData, settings: { ...layoutFormData.settings, fonts: { ...layoutFormData.settings.fonts, [fontType]: value } } })
    }

    const updateLayoutSetting = (layoutType: keyof typeof layoutFormData.settings.layout, value: number) => {
        setLayoutFormData({ ...layoutFormData, settings: { ...layoutFormData.settings, layout: { ...layoutFormData.settings.layout, [layoutType]: value } } })
    }

    const updateBrandingSetting = (brandingType: keyof typeof layoutFormData.settings.branding, value: boolean | string) => {
        setLayoutFormData({ ...layoutFormData, settings: { ...layoutFormData.settings, branding: { ...layoutFormData.settings.branding, [brandingType]: value } } })
    }

    const updateContentSetting = (contentType: keyof typeof layoutFormData.settings.content, value: boolean | string) => {
        setLayoutFormData({ ...layoutFormData, settings: { ...layoutFormData.settings, content: { ...layoutFormData.settings.content, [contentType]: value } } })
    }

    const hasErrors = Object.keys(form.errors).length > 0

    return (
        <AppLayout>
            <Head title="Angebotslayouts" />

            <div className="space-y-6">
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
                        <h1 className="text-2xl font-bold text-foreground">Angebotslayouts</h1>
                        <p className="text-muted-foreground">Verwalten Sie Ihre Angebotslayouts und Templates</p>
                    </div>

                    <Dialog open={isLayoutDialogOpen} onOpenChange={setIsLayoutDialogOpen}>
                        <DialogTrigger asChild>
                            <Button onClick={handleCreateLayout}>
                                <Plus className="mr-2 h-4 w-4" />
                                Neues Layout
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="max-w-7xl h-[90vh] overflow-hidden">
                            <DialogHeader>
                                <DialogTitle>{editingLayout ? "Layout bearbeiten" : "Neues Layout erstellen"}</DialogTitle>
                                <DialogDescription>
                                    {editingLayout ? "Bearbeiten Sie die Layout-Einstellungen" : "Erstellen Sie ein neues Angebotslayout"}
                                </DialogDescription>
                            </DialogHeader>

                            {hasErrors && (
                                <Alert variant="destructive" className="mb-4">
                                    <AlertCircle className="h-4 w-4" />
                                    <AlertDescription>
                                        <div className="font-medium mb-2">Bitte korrigieren Sie die folgenden Fehler:</div>
                                        <ul className="list-disc list-inside space-y-1">
                                            {Object.entries(form.errors).map(([field, message]) => (
                                                <li key={field} className="text-sm"><strong>{field}:</strong> {message as string}</li>
                                            ))}
                                        </ul>
                                    </AlertDescription>
                                </Alert>
                            )}

                            <form onSubmit={handleSaveLayout} className="h-[calc(90vh-140px)]">
                                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 h-full">
                                    {/* Left side: step-by-step configurator */}
                                    <div className="flex flex-col h-full overflow-hidden">
                                        <div className="rounded-lg border bg-muted/40 p-3">
                                            <div className="text-sm font-medium mb-2">Konfigurator</div>
                                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                <Button type="button" variant="outline" className="justify-start" onClick={() => goToStep("basic")}>
                                                    1. Layout wählen
                                                </Button>
                                                <Button type="button" variant="outline" className="justify-start" onClick={() => goToStep("design", "step-colors")}>
                                                    2. Farben
                                                </Button>
                                                <Button type="button" variant="outline" className="justify-start" onClick={() => goToStep("design", "step-fonts")}>
                                                    3. Schriften
                                                </Button>
                                                <Button type="button" variant="outline" className="justify-start" onClick={() => goToStep("layout", "step-logo")}>
                                                    4. Logo & Branding
                                                </Button>
                                                <Button type="button" variant="outline" className="justify-start" onClick={() => goToStep("layout", "step-header-footer")}>
                                                    5. Kopf-/Fußzeile
                                                </Button>
                                                <Button type="button" variant="outline" className="justify-start" onClick={() => goToStep("layout", "step-margins")}>
                                                    6. Seitenränder
                                                </Button>
                                                <Button type="button" variant="outline" className="justify-start sm:col-span-2" onClick={() => goToStep("content", "step-additional")}>
                                                    7. Weitere Optionen
                                                </Button>
                                            </div>
                                        </div>

                                        <div ref={leftScrollRef} className="flex-1 overflow-y-auto pr-2 mt-4">
                                            <Tabs value={builderTab} onValueChange={(v) => setBuilderTab(v as any)} className="space-y-4">

                                                <TabsContent value="basic" className="space-y-6">
                                                    <div className="grid gap-6">
                                                        <div className="grid gap-2">
                                                            <Label htmlFor="layout-name">Layout Name</Label>
                                                            <Input
                                                                id="layout-name"
                                                                value={layoutFormData.name}
                                                                onChange={(e) => setLayoutFormData({ ...layoutFormData, name: e.target.value })}
                                                                placeholder="z.B. Modernes Angebotslayout"
                                                                required
                                                            />
                                                        </div>

                                                        <div className="grid gap-4">
                                                            <Label>Template auswählen</Label>
                                                            <div className="grid grid-cols-2 gap-4">
                                                                {templates.map((template) => (
                                                                    <div
                                                                        key={template.id}
                                                                        className={`border rounded-lg p-4 cursor-pointer transition-all ${
                                                                            layoutFormData.template === template.id
                                                                                ? "border-blue-500 bg-blue-50"
                                                                                : "border-gray-200 hover:border-gray-300"
                                                                        }`}
                                                                        onClick={() => handleTemplateChange(template.id)}
                                                                    >
                                                                        <div className="flex items-start justify-between mb-2">
                                                                            <h3 className="font-semibold">{template.name}</h3>
                                                                            {layoutFormData.template === template.id && <Badge variant="default">Ausgewählt</Badge>}
                                                                        </div>
                                                                        <p className="text-sm text-muted-foreground mb-3">{template.description}</p>
                                                                        <div className="flex flex-wrap gap-1 mb-3">
                                                                            {template.features.map((feature) => (
                                                                                <Badge key={feature} variant="outline" className="text-xs">{feature}</Badge>
                                                                            ))}
                                                                        </div>
                                                                        <div className="flex gap-1">
                                                                            {template.colors.map((color, index) => (
                                                                                <div key={index} className="w-4 h-4 rounded-full border border-gray-300" style={{ backgroundColor: color }} />
                                                                            ))}
                                                                        </div>
                                                                    </div>
                                                                ))}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </TabsContent>

                                                <TabsContent value="design" className="space-y-4">
                                                    <div className="grid gap-6">
                                                        <div id="step-colors">
                                                            <h3 className="text-lg font-semibold mb-3">Farben</h3>
                                                            <div className="grid grid-cols-2 gap-4">
                                                                {(["primary", "secondary", "accent", "text"] as const).map((colorKey) => (
                                                                    <div key={colorKey} className="grid gap-2">
                                                                        <Label htmlFor={`${colorKey}-color`}>
                                                                            {colorKey === "primary" ? "Primärfarbe" : colorKey === "secondary" ? "Sekundärfarbe" : colorKey === "accent" ? "Akzentfarbe" : "Textfarbe"}
                                                                        </Label>
                                                                        <div className="flex gap-2">
                                                                            <Input id={`${colorKey}-color`} type="color" value={layoutFormData.settings.colors[colorKey]} onChange={(e) => updateColorSetting(colorKey, e.target.value)} className="w-16 h-10" />
                                                                            <Input value={layoutFormData.settings.colors[colorKey]} onChange={(e) => updateColorSetting(colorKey, e.target.value)} placeholder="#2563eb" />
                                                                        </div>
                                                                    </div>
                                                                ))}
                                                            </div>
                                                        </div>

                                                        <div id="step-fonts">
                                                            <h3 className="text-lg font-semibold mb-3">Schriftarten</h3>
                                                            <div className="grid grid-cols-3 gap-4">
                                                                <div className="grid gap-2">
                                                                    <Label>Überschrift</Label>
                                                                    <Select value={layoutFormData.settings.fonts.heading} onValueChange={(v) => updateFontSetting("heading", v)}>
                                                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                                                        <SelectContent>
                                                                            {["Inter","Roboto","Open Sans","Lato","Montserrat","Poppins","Playfair Display"].map((f) => <SelectItem key={f} value={f}>{f}</SelectItem>)}
                                                                        </SelectContent>
                                                                    </Select>
                                                                </div>
                                                                <div className="grid gap-2">
                                                                    <Label>Fließtext</Label>
                                                                    <Select value={layoutFormData.settings.fonts.body} onValueChange={(v) => updateFontSetting("body", v)}>
                                                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                                                        <SelectContent>
                                                                            {["Inter","Roboto","Open Sans","Lato","Source Sans Pro","Nunito"].map((f) => <SelectItem key={f} value={f}>{f}</SelectItem>)}
                                                                        </SelectContent>
                                                                    </Select>
                                                                </div>
                                                                <div className="grid gap-2">
                                                                    <Label>Schriftgröße</Label>
                                                                    <Select value={layoutFormData.settings.fonts.size} onValueChange={(v) => updateFontSetting("size", v)}>
                                                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                                                        <SelectContent>
                                                                            <SelectItem value="small">Klein (14px)</SelectItem>
                                                                            <SelectItem value="medium">Mittel (16px)</SelectItem>
                                                                            <SelectItem value="large">Groß (18px)</SelectItem>
                                                                        </SelectContent>
                                                                    </Select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </TabsContent>

                                                <TabsContent value="layout" className="space-y-4">
                                                    <div className="grid gap-6">
                                                        <div id="step-margins">
                                                            <h3 className="text-lg font-semibold mb-3">Seitenränder (mm)</h3>
                                                            <div className="grid grid-cols-2 gap-4">
                                                                {([["margin_top","Oben"],["margin_bottom","Unten"],["margin_left","Links"],["margin_right","Rechts"]] as const).map(([key, label]) => (
                                                                    <div key={key} className="grid gap-2">
                                                                        <Label>{label}</Label>
                                                                        <Input type="number" value={layoutFormData.settings.layout[key]} onChange={(e) => updateLayoutSetting(key, parseInt(e.target.value) || 0)} min="0" max="100" />
                                                                    </div>
                                                                ))}
                                                            </div>
                                                        </div>

                                                        <div id="step-header-footer">
                                                            <h3 className="text-lg font-semibold mb-3">Bereiche (px)</h3>
                                                            <div className="grid grid-cols-2 gap-4">
                                                                <div className="grid gap-2">
                                                                    <Label>Kopfzeile Höhe</Label>
                                                                    <Input type="number" value={layoutFormData.settings.layout.header_height} onChange={(e) => updateLayoutSetting("header_height", parseInt(e.target.value) || 0)} min="50" max="300" />
                                                                </div>
                                                                <div className="grid gap-2">
                                                                    <Label>Fußzeile Höhe</Label>
                                                                    <Input type="number" value={layoutFormData.settings.layout.footer_height} onChange={(e) => updateLayoutSetting("footer_height", parseInt(e.target.value) || 0)} min="30" max="200" />
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div id="step-logo">
                                                            <h3 className="text-lg font-semibold mb-3">Branding</h3>
                                                            <div className="rounded-lg border bg-muted/40 p-4 mb-4">
                                                                <div className="flex items-start justify-between gap-4">
                                                                    <div className="space-y-1">
                                                                        <div className="text-sm font-medium">Logo (Firma)</div>
                                                                        <p className="text-sm text-muted-foreground">Dieses Layout nutzt das Firmenlogo.</p>
                                                                    </div>
                                                                    {(logoPreviewUrl || company?.logo) ? (
                                                                        <img
                                                                            src={logoPreviewUrl || (company?.logo ? `/storage/${company.logo}` : "")}
                                                                            alt="Company logo preview"
                                                                            className="h-14 w-auto rounded border bg-white"
                                                                        />
                                                                    ) : null}
                                                                </div>
                                                                <div className="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                                                    <Input
                                                                        type="file"
                                                                        accept="image/png,image/jpeg,image/jpg,image/gif"
                                                                        onChange={(e) => {
                                                                            const file = e.target.files?.[0]
                                                                            if (!file) return
                                                                            if (logoPreviewUrl) URL.revokeObjectURL(logoPreviewUrl)
                                                                            setLogoPreviewUrl(URL.createObjectURL(file))
                                                                            logoForm.setData("logo" as any, file)
                                                                        }}
                                                                    />
                                                                    <Button
                                                                        type="button"
                                                                        variant="outline"
                                                                        disabled={!company?.id || logoForm.processing || !logoForm.data.logo}
                                                                        onClick={() => {
                                                                            if (!company?.id) return
                                                                            logoForm.post(route("companies.update", company.id), {
                                                                                forceFormData: true,
                                                                                preserveScroll: true,
                                                                                onSuccess: () => logoForm.reset("logo"),
                                                                            })
                                                                        }}
                                                                    >
                                                                        {logoForm.processing ? "Upload..." : "Logo hochladen"}
                                                                    </Button>
                                                                </div>
                                                                {logoForm.errors.logo && <p className="mt-2 text-sm text-red-600">{logoForm.errors.logo}</p>}
                                                            </div>

                                                            <div className="space-y-4">
                                                                <div className="flex items-center justify-between">
                                                                    <div className="space-y-0.5">
                                                                        <Label>Logo anzeigen</Label>
                                                                        <p className="text-sm text-muted-foreground">Firmenlogo im Angebot anzeigen</p>
                                                                    </div>
                                                                    <Switch checked={layoutFormData.settings.branding.show_logo} onCheckedChange={(v) => updateBrandingSetting("show_logo", v)} />
                                                                </div>

                                                                <div className="grid grid-cols-2 gap-4">
                                                                    <div className="grid gap-2">
                                                                        <Label>Logo Position</Label>
                                                                        <Select value={layoutFormData.settings.branding.logo_position} onValueChange={(v) => updateBrandingSetting("logo_position", v)}>
                                                                            <SelectTrigger><SelectValue /></SelectTrigger>
                                                                            <SelectContent>
                                                                                <SelectItem value="top-left">Oben Links</SelectItem>
                                                                                <SelectItem value="top-center">Oben Mitte</SelectItem>
                                                                                <SelectItem value="top-right">Oben Rechts</SelectItem>
                                                                            </SelectContent>
                                                                        </Select>
                                                                    </div>
                                                                    <div className="grid gap-2">
                                                                        <Label>Firmeninfo Position</Label>
                                                                        <Select value={layoutFormData.settings.branding.company_info_position} onValueChange={(v) => updateBrandingSetting("company_info_position", v)}>
                                                                            <SelectTrigger><SelectValue /></SelectTrigger>
                                                                            <SelectContent>
                                                                                <SelectItem value="top-left">Oben Links</SelectItem>
                                                                                <SelectItem value="top-center">Oben Mitte</SelectItem>
                                                                                <SelectItem value="top-right">Oben Rechts</SelectItem>
                                                                            </SelectContent>
                                                                        </Select>
                                                                    </div>
                                                                </div>

                                                                <div className="flex items-center justify-between">
                                                                    <div className="space-y-0.5">
                                                                        <Label>Kopfzeilen-Linie anzeigen</Label>
                                                                        <p className="text-sm text-muted-foreground">Linie unter dem Header anzeigen</p>
                                                                    </div>
                                                                    <Switch checked={layoutFormData.settings.branding.show_header_line} onCheckedChange={(v) => updateBrandingSetting("show_header_line", v)} />
                                                                </div>

                                                                <div className="flex items-center justify-between">
                                                                    <div className="space-y-0.5">
                                                                        <Label>Fußzeile anzeigen</Label>
                                                                        <p className="text-sm text-muted-foreground">Fußzeile mit Firmeninformationen anzeigen</p>
                                                                    </div>
                                                                    <Switch checked={layoutFormData.settings.branding.show_footer} onCheckedChange={(v) => updateBrandingSetting("show_footer", v)} />
                                                                </div>

                                                                <div className="flex items-center justify-between">
                                                                    <div className="space-y-0.5">
                                                                        <Label>Fußzeilen-Linie anzeigen</Label>
                                                                        <p className="text-sm text-muted-foreground">Linie über der Fußzeile anzeigen</p>
                                                                    </div>
                                                                    <Switch checked={layoutFormData.settings.branding.show_footer_line} onCheckedChange={(v) => updateBrandingSetting("show_footer_line", v)} />
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </TabsContent>

                                                <TabsContent value="content" className="space-y-4">
                                                    <div className="space-y-6" id="step-additional">
                                                        <div>
                                                            <h3 className="text-lg font-semibold mb-3">Firmeninformationen</h3>
                                                            <div className="space-y-4">
                                                                <div className="flex items-center justify-between">
                                                                    <div className="space-y-0.5">
                                                                        <Label>Firmenadresse anzeigen</Label>
                                                                        <p className="text-sm text-muted-foreground">Adresse der Firma im Header anzeigen</p>
                                                                    </div>
                                                                    <Switch checked={layoutFormData.settings.content.show_company_address} onCheckedChange={(v) => updateContentSetting("show_company_address", v)} />
                                                                </div>
                                                                <div className="flex items-center justify-between">
                                                                    <div className="space-y-0.5">
                                                                        <Label>Firmenkontakt anzeigen</Label>
                                                                        <p className="text-sm text-muted-foreground">Telefon, E-Mail und Website anzeigen</p>
                                                                    </div>
                                                                    <Switch checked={layoutFormData.settings.content.show_company_contact} onCheckedChange={(v) => updateContentSetting("show_company_contact", v)} />
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div>
                                                            <h3 className="text-lg font-semibold mb-3">Angebotsinformationen</h3>
                                                            <div className="space-y-4">
                                                                <div className="flex items-center justify-between">
                                                                    <div className="space-y-0.5">
                                                                        <Label>Kundennummer anzeigen</Label>
                                                                        <p className="text-sm text-muted-foreground">Kundennummer im Dokument anzeigen</p>
                                                                    </div>
                                                                    <Switch checked={layoutFormData.settings.content.show_customer_number} onCheckedChange={(v) => updateContentSetting("show_customer_number", v)} />
                                                                </div>
                                                                <div className="flex items-center justify-between">
                                                                    <div className="space-y-0.5">
                                                                        <Label>Steuernummer anzeigen</Label>
                                                                        <p className="text-sm text-muted-foreground">Steuernummer im Dokument anzeigen</p>
                                                                    </div>
                                                                    <Switch checked={layoutFormData.settings.content.show_tax_number} onCheckedChange={(v) => updateContentSetting("show_tax_number", v)} />
                                                                </div>
                                                                <div className="flex items-center justify-between">
                                                                    <div className="space-y-0.5">
                                                                        <Label>BV (Bauvorhaben) anzeigen</Label>
                                                                        <p className="text-sm text-muted-foreground">Bauvorhaben-Referenz im Dokumentkopf anzeigen</p>
                                                                    </div>
                                                                    <Switch checked={layoutFormData.settings.content.show_bauvorhaben} onCheckedChange={(v) => updateContentSetting("show_bauvorhaben", v)} />
                                                                </div>
                                                                <div className="flex items-center justify-between">
                                                                    <div className="space-y-0.5">
                                                                        <Label>Einheitsspalte anzeigen</Label>
                                                                        <p className="text-sm text-muted-foreground">Einheit (Stk., kg, etc.) in Artikeltabelle anzeigen</p>
                                                                    </div>
                                                                    <Switch checked={layoutFormData.settings.content.show_unit_column} onCheckedChange={(v) => updateContentSetting("show_unit_column", v)} />
                                                                </div>
                                                                <div className="flex items-center justify-between">
                                                                    <div className="space-y-0.5">
                                                                        <Label>Notizen anzeigen</Label>
                                                                        <p className="text-sm text-muted-foreground">Notizenfeld im Dokument anzeigen</p>
                                                                    </div>
                                                                    <Switch checked={layoutFormData.settings.content.show_notes} onCheckedChange={(v) => updateContentSetting("show_notes", v)} />
                                                                </div>
                                                                <div className="flex items-center justify-between">
                                                                    <div className="space-y-0.5">
                                                                        <Label>Gültigkeitsdauer anzeigen</Label>
                                                                        <p className="text-sm text-muted-foreground">Gültigkeitsdauer des Angebots hervorheben</p>
                                                                    </div>
                                                                    <Switch checked={layoutFormData.settings.content.show_validity_period} onCheckedChange={(v) => updateContentSetting("show_validity_period", v)} />
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div>
                                                            <h3 className="text-lg font-semibold mb-3">Fußzeilen-Informationen</h3>
                                                            <div className="space-y-4">
                                                                <div className="flex items-center justify-between">
                                                                    <div className="space-y-0.5">
                                                                        <Label>Bankverbindung anzeigen</Label>
                                                                        <p className="text-sm text-muted-foreground">IBAN, BIC und Bankname anzeigen</p>
                                                                    </div>
                                                                    <Switch checked={layoutFormData.settings.content.show_bank_details} onCheckedChange={(v) => updateContentSetting("show_bank_details", v)} />
                                                                </div>
                                                                <div className="flex items-center justify-between">
                                                                    <div className="space-y-0.5">
                                                                        <Label>Handelsregister anzeigen</Label>
                                                                        <p className="text-sm text-muted-foreground">Handelsregister und Steuernummer anzeigen</p>
                                                                    </div>
                                                                    <Switch checked={layoutFormData.settings.content.show_company_registration} onCheckedChange={(v) => updateContentSetting("show_company_registration", v)} />
                                                                </div>
                                                                <div className="flex items-center justify-between">
                                                                    <div className="space-y-0.5">
                                                                        <Label>Zahlungsbedingungen anzeigen</Label>
                                                                        <p className="text-sm text-muted-foreground">Zahlungsbedingungen im Angebot anzeigen</p>
                                                                    </div>
                                                                    <Switch checked={layoutFormData.settings.content.show_payment_terms} onCheckedChange={(v) => updateContentSetting("show_payment_terms", v)} />
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div>
                                                            <h3 className="text-lg font-semibold mb-3">Artikeloptionen</h3>
                                                            <div className="space-y-4">
                                                                <div className="flex items-center justify-between">
                                                                    <div className="space-y-0.5">
                                                                        <Label>Artikelbilder anzeigen</Label>
                                                                        <p className="text-sm text-muted-foreground">Produktbilder in der Artikeltabelle anzeigen</p>
                                                                    </div>
                                                                    <Switch checked={layoutFormData.settings.content.show_item_images} onCheckedChange={(v) => updateContentSetting("show_item_images", v)} />
                                                                </div>
                                                                <div className="flex items-center justify-between">
                                                                    <div className="space-y-0.5">
                                                                        <Label>Artikelnummern anzeigen</Label>
                                                                        <p className="text-sm text-muted-foreground">Produktnummern in der Artikeltabelle anzeigen</p>
                                                                    </div>
                                                                    <Switch checked={layoutFormData.settings.content.show_item_codes} onCheckedChange={(v) => updateContentSetting("show_item_codes", v)} />
                                                                </div>
                                                                <div className="flex items-center justify-between">
                                                                    <div className="space-y-0.5">
                                                                        <Label>Positionsnummern anzeigen</Label>
                                                                        <p className="text-sm text-muted-foreground">Fortlaufende Nr.-Spalte (Pos.) in der Artikeltabelle anzeigen</p>
                                                                    </div>
                                                                    <Switch checked={layoutFormData.settings.content.show_row_number} onCheckedChange={(v) => updateContentSetting("show_row_number", v)} />
                                                                </div>
                                                                <div className="flex items-center justify-between">
                                                                    <div className="space-y-0.5">
                                                                        <Label>Steueraufschlüsselung anzeigen</Label>
                                                                        <p className="text-sm text-muted-foreground">Detaillierte Steuerberechnung anzeigen</p>
                                                                    </div>
                                                                    <Switch checked={layoutFormData.settings.content.show_tax_breakdown} onCheckedChange={(v) => updateContentSetting("show_tax_breakdown", v)} />
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div className="grid gap-2">
                                                            <Label htmlFor="custom-footer">Benutzerdefinierter Fußzeilentext</Label>
                                                            <Textarea
                                                                id="custom-footer"
                                                                value={layoutFormData.settings.content.custom_footer_text}
                                                                onChange={(e) => updateContentSetting("custom_footer_text", e.target.value)}
                                                                placeholder="z.B. Wir freuen uns auf Ihre Rückmeldung!"
                                                                rows={3}
                                                            />
                                                            <p className="text-sm text-muted-foreground">Dieser Text wird am Ende jedes Angebots angezeigt.</p>
                                                        </div>
                                                    </div>
                                                </TabsContent>
                                            </Tabs>
                                        </div>

                                        <div className="mt-4 flex items-center justify-between border-t pt-4">
                                            <Button type="button" variant="outline" onClick={handleCloseDialog} disabled={isSubmitting}>
                                                Abbrechen
                                            </Button>
                                            <Button type="submit" disabled={isSubmitting} onClick={() => setSaveAndPreview(true)}>
                                                {isSubmitting ? "Speichert..." : "Speichern & Vorschau"}
                                            </Button>
                                        </div>
                                    </div>

                                    {/* Right side: Live preview */}
                                    <div className="relative h-full overflow-hidden rounded-lg border bg-white">
                                        {livePreviewLoading && (
                                            <div className="absolute inset-0 z-10 flex items-center justify-center bg-white/60 text-sm text-muted-foreground">
                                                Vorschau wird aktualisiert…
                                            </div>
                                        )}
                                        {livePreviewPdfUrl ? (
                                            <iframe src={livePreviewPdfUrl} className="w-full h-full border-0" title="Angebotslayout Live Vorschau (PDF)" />
                                        ) : (
                                            <iframe srcDoc={livePreviewHtml || "<html><body></body></html>"} className="w-full h-full border-0" title="Angebotslayout Live Vorschau (lädt)" />
                                        )}
                                    </div>
                                </div>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                {layouts.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <Layout className="h-12 w-12 text-muted-foreground mb-4" />
                            <h3 className="text-lg font-semibold text-foreground mb-2">Keine Angebotslayouts vorhanden</h3>
                            <p className="text-muted-foreground text-center mb-4">
                                Erstellen Sie Ihr erstes Angebotslayout, um professionelle Angebote zu generieren.
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
                            <CardTitle>Verfügbare Angebotslayouts ({layouts.length})</CardTitle>
                            <CardDescription>Verwalten Sie Ihre Angebotslayouts und Templates</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Layout Name</TableHead>
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
                                                    <Button variant="outline" size="sm" onClick={() => handlePreviewLayout(layout)} title="Vorschau anzeigen">
                                                        <Eye className="h-4 w-4" />
                                                    </Button>
                                                    <Button variant="outline" size="sm" onClick={() => handleEditLayout(layout)} title="Layout bearbeiten">
                                                        <Edit className="h-4 w-4" />
                                                    </Button>
                                                    <Button variant="outline" size="sm" onClick={() => handleDuplicateLayout(layout)} title="Layout duplizieren">
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
                            <DialogDescription>Vorschau des Angebotslayouts mit Beispieldaten – identisch zur PDF-Ansicht</DialogDescription>
                        </DialogHeader>

                        {previewLayout ? (
                            <div className="flex-1 overflow-hidden border rounded-lg bg-white">
                                <iframe
                                    src={route("offer-layouts.preview", previewLayout.id)}
                                    className="w-full h-full min-h-[600px] border-0"
                                    title="Angebotslayout Vorschau"
                                    style={{ minHeight: "600px" }}
                                />
                            </div>
                        ) : (
                            <div className="flex items-center justify-center py-8">
                                <p>Layout wird geladen...</p>
                            </div>
                        )}

                        <DialogFooter>
                            <Button variant="outline" onClick={() => { setIsPreviewOpen(false); setPreviewLayout(null) }}>
                                Schließen
                            </Button>
                            {previewLayout && (
                                <Button onClick={() => window.open(route("offer-layouts.preview", previewLayout.id), "_blank")}>
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
