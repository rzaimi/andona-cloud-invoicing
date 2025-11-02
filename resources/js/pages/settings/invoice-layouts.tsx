"use client"

import type React from "react"

import { useState } from "react"
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { Plus, Edit, Trash2, Eye, Copy, Layout, Star, Download, Palette, Type, Settings, FileText, AlertCircle } from "lucide-react"
import { Alert, AlertDescription } from "@/components/ui/alert"
import AppLayout from "@/layouts/app-layout"
import { route } from "ziggy-js"

interface InvoiceLayoutSettings {
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
        show_item_images: boolean
        show_item_codes: boolean
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
        show_item_images: false,
        show_item_codes: true,
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
            size: "medium",
        },
    }

    // Template-specific layout configurations
    switch (templateId) {
        case 'minimal':
            // Minimal: Clean design with generous whitespaces, larger margins
            defaults.layout = {
                header_height: 140,
                footer_height: 100,
                margin_top: 25,
                margin_bottom: 25,
                margin_left: 25,
                margin_right: 25,
            }
            defaults.branding = {
                show_logo: true,
                logo_position: "top-right",
                company_info_position: "top-left",
                show_header_line: true,
                show_footer_line: true,
                show_footer: true,
            }
            defaults.content = {
                show_company_address: true,
                show_company_contact: true,
                show_customer_number: true,
                show_tax_number: true,
                show_unit_column: true,
                show_notes: true,
                show_bank_details: true,
                show_company_registration: true,
                show_payment_terms: true,
                show_item_images: false,
                show_item_codes: true,
                show_tax_breakdown: true,
                custom_footer_text: "",
            }
            break

        case 'classic':
            // Classic: Traditional layout, compact margins, centered logo
            defaults.layout = {
                header_height: 100,
                footer_height: 60,
                margin_top: 15,
                margin_bottom: 15,
                margin_left: 15,
                margin_right: 15,
            }
            defaults.branding = {
                show_logo: true,
                logo_position: "top-center",
                company_info_position: "top-center",
            }
            defaults.content = {
                show_item_images: false,
                show_item_codes: true,
                show_tax_breakdown: true,
                show_payment_terms: true,
                custom_footer_text: "",
            }
            break

        case 'minimal':
            // Minimal: Focus on content, minimal margins, no logo
            defaults.layout = {
                header_height: 80,
                footer_height: 50,
                margin_top: 10,
                margin_bottom: 10,
                margin_left: 20,
                margin_right: 20,
            }
            defaults.branding = {
                show_logo: false,
                logo_position: "top-left",
                company_info_position: "top-left",
                show_header_line: false,
                show_footer_line: false,
                show_footer: true,
            }
            defaults.content = {
                show_company_address: true,
                show_company_contact: false,
                show_customer_number: false,
                show_tax_number: false,
                show_unit_column: true,
                show_notes: true,
                show_bank_details: false,
                show_company_registration: false,
                show_payment_terms: true,
                show_item_images: false,
                show_item_codes: false,
                show_tax_breakdown: false,
                custom_footer_text: "",
            }
            break

        case 'professional':
            // Professional: Structured, corporate layout with standard margins
            defaults.layout = {
                header_height: 130,
                footer_height: 90,
                margin_top: 20,
                margin_bottom: 20,
                margin_left: 20,
                margin_right: 20,
            }
            defaults.branding = {
                show_logo: true,
                logo_position: "top-left",
                company_info_position: "top-right",
                show_header_line: true,
                show_footer_line: true,
                show_footer: true,
            }
            defaults.content = {
                show_company_address: true,
                show_company_contact: true,
                show_customer_number: true,
                show_tax_number: true,
                show_unit_column: true,
                show_notes: true,
                show_bank_details: true,
                show_company_registration: true,
                show_payment_terms: true,
                show_item_images: false,
                show_item_codes: true,
                show_tax_breakdown: true,
                custom_footer_text: "",
            }
            break

        case 'creative':
            // Creative: Modern with larger header, asymmetric layout
            defaults.layout = {
                header_height: 150,
                footer_height: 70,
                margin_top: 30,
                margin_bottom: 20,
                margin_left: 25,
                margin_right: 15,
            }
            defaults.branding = {
                show_logo: true,
                logo_position: "top-right",
                company_info_position: "top-left",
                show_header_line: true,
                show_footer_line: true,
                show_footer: true,
            }
            defaults.content = {
                show_company_address: true,
                show_company_contact: true,
                show_customer_number: true,
                show_tax_number: true,
                show_unit_column: true,
                show_notes: true,
                show_bank_details: true,
                show_company_registration: true,
                show_payment_terms: true,
                show_item_images: false,
                show_item_codes: true,
                show_tax_breakdown: true,
                custom_footer_text: "",
            }
            break

        case 'elegant':
            // Elegant: Refined with generous spacing, centered alignment
            defaults.layout = {
                header_height: 160,
                footer_height: 110,
                margin_top: 30,
                margin_bottom: 30,
                margin_left: 30,
                margin_right: 30,
            }
            defaults.branding = {
                show_logo: true,
                logo_position: "top-center",
                company_info_position: "top-center",
                show_header_line: true,
                show_footer_line: true,
                show_footer: true,
            }
            defaults.content = {
                show_company_address: true,
                show_company_contact: true,
                show_customer_number: true,
                show_tax_number: true,
                show_unit_column: true,
                show_notes: true,
                show_bank_details: true,
                show_company_registration: true,
                show_payment_terms: true,
                show_item_images: false,
                show_item_codes: true,
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
            show_item_images: settings.content?.show_item_images ?? defaults.content.show_item_images,
            show_item_codes: settings.content?.show_item_codes ?? defaults.content.show_item_codes,
            show_tax_breakdown: settings.content?.show_tax_breakdown ?? defaults.content.show_tax_breakdown,
            custom_footer_text: settings.content?.custom_footer_text ?? defaults.content.custom_footer_text,
        },
        template_specific: settings.template_specific || {},
    }
}

export default function InvoiceLayoutsPage({ layouts, templates }: InvoiceLayoutsPageProps) {
    const { flash } = usePage<{ flash?: { success?: string; error?: string } }>().props
    const [isLayoutDialogOpen, setIsLayoutDialogOpen] = useState(false)
    const [editingLayout, setEditingLayout] = useState<InvoiceLayout | null>(null)
    const [previewLayout, setPreviewLayout] = useState<InvoiceLayout | null>(null)
    const [isPreviewOpen, setIsPreviewOpen] = useState(false)

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
        }

        // Submit directly using router with the correct data
        if (editingLayout) {
            router.put(route("invoice-layouts.update", editingLayout.id), submitData, {
                onSuccess: () => {
                    handleCloseDialog()
                },
                onError: (errors) => {
                    // Handle validation errors - set them in the form object for display
                    if (errors) {
                        Object.keys(errors).forEach((key) => {
                            const errorMessage = Array.isArray(errors[key]) ? errors[key][0] : errors[key]
                            form.setError(key as any, errorMessage)
                        })
                    }
                },
            })
        } else {
            router.post(route("invoice-layouts.store"), submitData, {
                onSuccess: () => {
                    handleCloseDialog()
                },
                onError: (errors) => {
                    // Handle validation errors - set them in the form object for display
                    if (errors) {
                        Object.keys(errors).forEach((key) => {
                            const errorMessage = Array.isArray(errors[key]) ? errors[key][0] : errors[key]
                            form.setError(key as any, errorMessage)
                        })
                    }
                },
            })
        }
    }

    const handleDeleteLayout = (id: string) => {
        if (confirm("Sind Sie sicher, dass Sie dieses Layout löschen möchten?")) {
            router.delete(route("invoice-layouts.destroy", id), {
                onSuccess: () => {
                    console.log("Layout deleted successfully")
                },
                onError: (error) => {
                    console.error("Error deleting layout:", error)
                },
            })
        }
    }

    const handleSetDefaultLayout = (id: string) => {
        router.post(
            route("invoice-layouts.set-default", id),
            {},
            {
                onSuccess: () => {
                    console.log("Default layout set successfully")
                },
                onError: (error) => {
                    console.error("Error setting default layout:", error)
                },
            },
        )
    }

    const handleDuplicateLayout = (layout: InvoiceLayout) => {
        router.post(
            route("invoice-layouts.duplicate", layout.id),
            {},
            {
                onSuccess: () => {
                    console.log("Layout duplicated successfully")
                },
                onError: (error) => {
                    console.error("Error duplicating layout:", error)
                },
            },
        )
    }

    const handlePreviewLayout = (layout: InvoiceLayout) => {
        console.log("Opening preview for layout:", layout.name)
        // Ensure the layout has complete settings before previewing
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

    const handleDownloadPDF = () => {
        if (previewLayout) {
            // This would trigger PDF download
            console.log("Downloading PDF for layout:", previewLayout.id)
            // router.get(`/settings/invoice-layouts/${previewLayout.id}/pdf`)
        }
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
        if (!layout?.settings?.fonts?.size) return "16px"
        const size = layout.settings.fonts.size
        switch (size) {
            case "small":
                return "14px"
            case "large":
                return "18px"
            default:
                return "16px"
        }
    }

    const getPreviewHeaderHeight = (layout: InvoiceLayout | null) => {
        if (!layout?.settings?.layout?.header_height) return 120
        return layout.settings.layout.header_height
    }

    const getPreviewFooterHeight = (layout: InvoiceLayout | null) => {
        if (!layout?.settings?.layout?.footer_height) return 80
        return layout.settings.layout.footer_height
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
                        <h1 className="text-3xl font-bold text-gray-900">Rechnungslayouts</h1>
                        <p className="text-gray-600">Verwalten Sie Ihre Rechnungs- und Angebotslayouts</p>
                    </div>

                    <Dialog open={isLayoutDialogOpen} onOpenChange={setIsLayoutDialogOpen}>
                        <DialogTrigger asChild>
                            <Button onClick={handleCreateLayout}>
                                <Plus className="mr-2 h-4 w-4" />
                                Neues Layout
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="max-w-5xl max-h-[90vh] overflow-y-auto">
                            <DialogHeader>
                                <DialogTitle>{editingLayout ? "Layout bearbeiten" : "Neues Layout erstellen"}</DialogTitle>
                                <DialogDescription>
                                    {editingLayout
                                        ? "Bearbeiten Sie die Layout-Einstellungen"
                                        : "Erstellen Sie ein neues Rechnungs-/Angebotslayout"}
                                </DialogDescription>
                            </DialogHeader>

                            {hasErrors && (
                                <Alert variant="destructive" className="mb-4">
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

                            <form onSubmit={handleSaveLayout}>
                                <Tabs defaultValue="basic" className="space-y-4">
                                    <TabsList className="grid w-full grid-cols-4">
                                        <TabsTrigger value="basic" className="flex items-center gap-2">
                                            <FileText className="h-4 w-4" />
                                            Grundlagen
                                        </TabsTrigger>
                                        <TabsTrigger value="design" className="flex items-center gap-2">
                                            <Palette className="h-4 w-4" />
                                            Design
                                        </TabsTrigger>
                                        <TabsTrigger value="layout" className="flex items-center gap-2">
                                            <Settings className="h-4 w-4" />
                                            Layout
                                        </TabsTrigger>
                                        <TabsTrigger value="content" className="flex items-center gap-2">
                                            <Type className="h-4 w-4" />
                                            Inhalt
                                        </TabsTrigger>
                                    </TabsList>

                                    <TabsContent value="basic" className="space-y-6">
                                        <div className="grid gap-6">
                                            <div className="grid gap-2">
                                                <Label htmlFor="layout-name">Layout Name</Label>
                                                <Input
                                                    id="layout-name"
                                                    value={layoutFormData.name}
                                                    onChange={(e) => setLayoutFormData({ ...layoutFormData, name: e.target.value })}
                                                    placeholder="z.B. Modernes Layout"
                                                    required
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="layout-type">Verwendung</Label>
                                                <Select
                                                    value={layoutFormData.type}
                                                    onValueChange={(value: "invoice" | "offer" | "both") =>
                                                        setLayoutFormData({ ...layoutFormData, type: value })
                                                    }
                                                >
                                                    <SelectTrigger>
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="both">Rechnungen & Angebote</SelectItem>
                                                        <SelectItem value="invoice">Nur Rechnungen</SelectItem>
                                                        <SelectItem value="offer">Nur Angebote</SelectItem>
                                                    </SelectContent>
                                                </Select>
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
                                                            <p className="text-sm text-gray-600 mb-3">{template.description}</p>
                                                            <div className="flex flex-wrap gap-1 mb-3">
                                                                {template.features.map((feature) => (
                                                                    <Badge key={feature} variant="outline" className="text-xs">
                                                                        {feature}
                                                                    </Badge>
                                                                ))}
                                                            </div>
                                                            <div className="flex gap-1">
                                                                {template.colors.map((color, index) => (
                                                                    <div
                                                                        key={index}
                                                                        className="w-4 h-4 rounded-full border border-gray-300"
                                                                        style={{ backgroundColor: color }}
                                                                    />
                                                                ))}
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        </div>
                                    </TabsContent>

                                    {/* Rest of the tabs content remains the same as before but with enhanced styling */}
                                    <TabsContent value="design" className="space-y-4">
                                        <div className="grid gap-6">
                                            <div>
                                                <h3 className="text-lg font-semibold mb-3">Farben</h3>
                                                <div className="grid grid-cols-2 gap-4">
                                                    <div className="grid gap-2">
                                                        <Label htmlFor="primary-color">Primärfarbe</Label>
                                                        <div className="flex gap-2">
                                                            <Input
                                                                id="primary-color"
                                                                type="color"
                                                                value={layoutFormData.settings.colors.primary}
                                                                onChange={(e) => updateColorSetting("primary", e.target.value)}
                                                                className="w-16 h-10"
                                                            />
                                                            <Input
                                                                value={layoutFormData.settings.colors.primary}
                                                                onChange={(e) => updateColorSetting("primary", e.target.value)}
                                                                placeholder="#2563eb"
                                                            />
                                                        </div>
                                                    </div>

                                                    <div className="grid gap-2">
                                                        <Label htmlFor="secondary-color">Sekundärfarbe</Label>
                                                        <div className="flex gap-2">
                                                            <Input
                                                                id="secondary-color"
                                                                type="color"
                                                                value={layoutFormData.settings.colors.secondary}
                                                                onChange={(e) => updateColorSetting("secondary", e.target.value)}
                                                                className="w-16 h-10"
                                                            />
                                                            <Input
                                                                value={layoutFormData.settings.colors.secondary}
                                                                onChange={(e) => updateColorSetting("secondary", e.target.value)}
                                                                placeholder="#64748b"
                                                            />
                                                        </div>
                                                    </div>

                                                    <div className="grid gap-2">
                                                        <Label htmlFor="accent-color">Akzentfarbe</Label>
                                                        <div className="flex gap-2">
                                                            <Input
                                                                id="accent-color"
                                                                type="color"
                                                                value={layoutFormData.settings.colors.accent}
                                                                onChange={(e) => updateColorSetting("accent", e.target.value)}
                                                                className="w-16 h-10"
                                                            />
                                                            <Input
                                                                value={layoutFormData.settings.colors.accent}
                                                                onChange={(e) => updateColorSetting("accent", e.target.value)}
                                                                placeholder="#0ea5e9"
                                                            />
                                                        </div>
                                                    </div>

                                                    <div className="grid gap-2">
                                                        <Label htmlFor="text-color">Textfarbe</Label>
                                                        <div className="flex gap-2">
                                                            <Input
                                                                id="text-color"
                                                                type="color"
                                                                value={layoutFormData.settings.colors.text}
                                                                onChange={(e) => updateColorSetting("text", e.target.value)}
                                                                className="w-16 h-10"
                                                            />
                                                            <Input
                                                                value={layoutFormData.settings.colors.text}
                                                                onChange={(e) => updateColorSetting("text", e.target.value)}
                                                                placeholder="#1e293b"
                                                            />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div>
                                                <h3 className="text-lg font-semibold mb-3">Schriftarten</h3>
                                                <div className="grid grid-cols-3 gap-4">
                                                    <div className="grid gap-2">
                                                        <Label htmlFor="heading-font">Überschrift</Label>
                                                        <Select
                                                            value={layoutFormData.settings.fonts.heading}
                                                            onValueChange={(value) => updateFontSetting("heading", value)}
                                                        >
                                                            <SelectTrigger>
                                                                <SelectValue />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                <SelectItem value="Inter">Inter</SelectItem>
                                                                <SelectItem value="Roboto">Roboto</SelectItem>
                                                                <SelectItem value="Open Sans">Open Sans</SelectItem>
                                                                <SelectItem value="Lato">Lato</SelectItem>
                                                                <SelectItem value="Montserrat">Montserrat</SelectItem>
                                                                <SelectItem value="Poppins">Poppins</SelectItem>
                                                                <SelectItem value="Playfair Display">Playfair Display</SelectItem>
                                                            </SelectContent>
                                                        </Select>
                                                    </div>

                                                    <div className="grid gap-2">
                                                        <Label htmlFor="body-font">Fließtext</Label>
                                                        <Select
                                                            value={layoutFormData.settings.fonts.body}
                                                            onValueChange={(value) => updateFontSetting("body", value)}
                                                        >
                                                            <SelectTrigger>
                                                                <SelectValue />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                <SelectItem value="Inter">Inter</SelectItem>
                                                                <SelectItem value="Roboto">Roboto</SelectItem>
                                                                <SelectItem value="Open Sans">Open Sans</SelectItem>
                                                                <SelectItem value="Lato">Lato</SelectItem>
                                                                <SelectItem value="Source Sans Pro">Source Sans Pro</SelectItem>
                                                                <SelectItem value="Nunito">Nunito</SelectItem>
                                                            </SelectContent>
                                                        </Select>
                                                    </div>

                                                    <div className="grid gap-2">
                                                        <Label htmlFor="font-size">Schriftgröße</Label>
                                                        <Select
                                                            value={layoutFormData.settings.fonts.size}
                                                            onValueChange={(value) => updateFontSetting("size", value)}
                                                        >
                                                            <SelectTrigger>
                                                                <SelectValue />
                                                            </SelectTrigger>
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

                                    {/* Layout and Content tabs remain the same as before */}
                                    <TabsContent value="layout" className="space-y-4">
                                        <div className="grid gap-6">
                                            <div>
                                                <h3 className="text-lg font-semibold mb-3">Seitenränder (mm)</h3>
                                                <div className="grid grid-cols-2 gap-4">
                                                    <div className="grid gap-2">
                                                        <Label htmlFor="margin-top">Oben</Label>
                                                        <Input
                                                            id="margin-top"
                                                            type="number"
                                                            value={layoutFormData.settings.layout.margin_top}
                                                            onChange={(e) => updateLayoutSetting("margin_top", Number.parseInt(e.target.value) || 0)}
                                                            min="0"
                                                            max="100"
                                                        />
                                                    </div>

                                                    <div className="grid gap-2">
                                                        <Label htmlFor="margin-bottom">Unten</Label>
                                                        <Input
                                                            id="margin-bottom"
                                                            type="number"
                                                            value={layoutFormData.settings.layout.margin_bottom}
                                                            onChange={(e) =>
                                                                updateLayoutSetting("margin_bottom", Number.parseInt(e.target.value) || 0)
                                                            }
                                                            min="0"
                                                            max="100"
                                                        />
                                                    </div>

                                                    <div className="grid gap-2">
                                                        <Label htmlFor="margin-left">Links</Label>
                                                        <Input
                                                            id="margin-left"
                                                            type="number"
                                                            value={layoutFormData.settings.layout.margin_left}
                                                            onChange={(e) => updateLayoutSetting("margin_left", Number.parseInt(e.target.value) || 0)}
                                                            min="0"
                                                            max="100"
                                                        />
                                                    </div>

                                                    <div className="grid gap-2">
                                                        <Label htmlFor="margin-right">Rechts</Label>
                                                        <Input
                                                            id="margin-right"
                                                            type="number"
                                                            value={layoutFormData.settings.layout.margin_right}
                                                            onChange={(e) =>
                                                                updateLayoutSetting("margin_right", Number.parseInt(e.target.value) || 0)
                                                            }
                                                            min="0"
                                                            max="100"
                                                        />
                                                    </div>
                                                </div>
                                            </div>

                                            <div>
                                                <h3 className="text-lg font-semibold mb-3">Bereiche (px)</h3>
                                                <div className="grid grid-cols-2 gap-4">
                                                    <div className="grid gap-2">
                                                        <Label htmlFor="header-height">Kopfzeile Höhe</Label>
                                                        <Input
                                                            id="header-height"
                                                            type="number"
                                                            value={layoutFormData.settings.layout.header_height}
                                                            onChange={(e) =>
                                                                updateLayoutSetting("header_height", Number.parseInt(e.target.value) || 0)
                                                            }
                                                            min="50"
                                                            max="300"
                                                        />
                                                    </div>

                                                    <div className="grid gap-2">
                                                        <Label htmlFor="footer-height">Fußzeile Höhe</Label>
                                                        <Input
                                                            id="footer-height"
                                                            type="number"
                                                            value={layoutFormData.settings.layout.footer_height}
                                                            onChange={(e) =>
                                                                updateLayoutSetting("footer_height", Number.parseInt(e.target.value) || 0)
                                                            }
                                                            min="30"
                                                            max="200"
                                                        />
                                                    </div>
                                                </div>
                                            </div>

                                            <div>
                                                <h3 className="text-lg font-semibold mb-3">Branding</h3>
                                                <div className="space-y-4">
                                                    <div className="flex items-center justify-between">
                                                        <div className="space-y-0.5">
                                                            <Label>Logo anzeigen</Label>
                                                            <p className="text-sm text-gray-600">Firmenlogo im Dokument anzeigen</p>
                                                        </div>
                                                        <Switch
                                                            checked={layoutFormData.settings.branding.show_logo}
                                                            onCheckedChange={(checked) => updateBrandingSetting("show_logo", checked)}
                                                        />
                                                    </div>

                                                    <div className="grid grid-cols-2 gap-4">
                                                        <div className="grid gap-2">
                                                            <Label htmlFor="logo-position">Logo Position</Label>
                                                            <Select
                                                                value={layoutFormData.settings.branding.logo_position}
                                                                onValueChange={(value) => updateBrandingSetting("logo_position", value)}
                                                            >
                                                                <SelectTrigger>
                                                                    <SelectValue />
                                                                </SelectTrigger>
                                                                <SelectContent>
                                                                    <SelectItem value="top-left">Oben Links</SelectItem>
                                                                    <SelectItem value="top-center">Oben Mitte</SelectItem>
                                                                    <SelectItem value="top-right">Oben Rechts</SelectItem>
                                                                </SelectContent>
                                                            </Select>
                                                        </div>

                                                        <div className="grid gap-2">
                                                            <Label htmlFor="company-info-position">Firmeninfo Position</Label>
                                                            <Select
                                                                value={layoutFormData.settings.branding.company_info_position}
                                                                onValueChange={(value) => updateBrandingSetting("company_info_position", value)}
                                                            >
                                                                <SelectTrigger>
                                                                    <SelectValue />
                                                                </SelectTrigger>
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
                                                            <p className="text-sm text-gray-600">Linie unter dem Header anzeigen</p>
                                                        </div>
                                                        <Switch
                                                            checked={layoutFormData.settings.branding.show_header_line}
                                                            onCheckedChange={(checked) => updateBrandingSetting("show_header_line", checked)}
                                                        />
                                                    </div>

                                                    <div className="flex items-center justify-between">
                                                        <div className="space-y-0.5">
                                                            <Label>Fußzeile anzeigen</Label>
                                                            <p className="text-sm text-gray-600">Fußzeile mit Firmeninformationen anzeigen</p>
                                                        </div>
                                                        <Switch
                                                            checked={layoutFormData.settings.branding.show_footer}
                                                            onCheckedChange={(checked) => updateBrandingSetting("show_footer", checked)}
                                                        />
                                                    </div>

                                                    <div className="flex items-center justify-between">
                                                        <div className="space-y-0.5">
                                                            <Label>Fußzeilen-Linie anzeigen</Label>
                                                            <p className="text-sm text-gray-600">Linie über der Fußzeile anzeigen</p>
                                                        </div>
                                                        <Switch
                                                            checked={layoutFormData.settings.branding.show_footer_line}
                                                            onCheckedChange={(checked) => updateBrandingSetting("show_footer_line", checked)}
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </TabsContent>

                                    <TabsContent value="content" className="space-y-4">
                                        <div className="space-y-6">
                                            <div>
                                                <h3 className="text-lg font-semibold mb-3">Firmeninformationen</h3>
                                                <div className="space-y-4">
                                                    <div className="flex items-center justify-between">
                                                        <div className="space-y-0.5">
                                                            <Label>Firmenadresse anzeigen</Label>
                                                            <p className="text-sm text-gray-600">Adresse der Firma im Header anzeigen</p>
                                                        </div>
                                                        <Switch
                                                            checked={layoutFormData.settings.content.show_company_address}
                                                            onCheckedChange={(checked) => updateContentSetting("show_company_address", checked)}
                                                        />
                                                    </div>

                                                    <div className="flex items-center justify-between">
                                                        <div className="space-y-0.5">
                                                            <Label>Firmenkontakt anzeigen</Label>
                                                            <p className="text-sm text-gray-600">Telefon, E-Mail und Website anzeigen</p>
                                                        </div>
                                                        <Switch
                                                            checked={layoutFormData.settings.content.show_company_contact}
                                                            onCheckedChange={(checked) => updateContentSetting("show_company_contact", checked)}
                                                        />
                                                    </div>
                                                </div>
                                            </div>

                                            <div>
                                                <h3 className="text-lg font-semibold mb-3">Rechnungsinformationen</h3>
                                                <div className="space-y-4">
                                                    <div className="flex items-center justify-between">
                                                        <div className="space-y-0.5">
                                                            <Label>Kundennummer anzeigen</Label>
                                                            <p className="text-sm text-gray-600">Kundennummer im Dokument anzeigen</p>
                                                        </div>
                                                        <Switch
                                                            checked={layoutFormData.settings.content.show_customer_number}
                                                            onCheckedChange={(checked) => updateContentSetting("show_customer_number", checked)}
                                                        />
                                                    </div>

                                                    <div className="flex items-center justify-between">
                                                        <div className="space-y-0.5">
                                                            <Label>Steuernummer anzeigen</Label>
                                                            <p className="text-sm text-gray-600">Steuernummer im Dokument anzeigen</p>
                                                        </div>
                                                        <Switch
                                                            checked={layoutFormData.settings.content.show_tax_number}
                                                            onCheckedChange={(checked) => updateContentSetting("show_tax_number", checked)}
                                                        />
                                                    </div>

                                                    <div className="flex items-center justify-between">
                                                        <div className="space-y-0.5">
                                                            <Label>Einheitsspalte anzeigen</Label>
                                                            <p className="text-sm text-gray-600">Einheit (Stk., kg, etc.) in Artikeltabelle anzeigen</p>
                                                        </div>
                                                        <Switch
                                                            checked={layoutFormData.settings.content.show_unit_column}
                                                            onCheckedChange={(checked) => updateContentSetting("show_unit_column", checked)}
                                                        />
                                                    </div>

                                                    <div className="flex items-center justify-between">
                                                        <div className="space-y-0.5">
                                                            <Label>Notizen anzeigen</Label>
                                                            <p className="text-sm text-gray-600">Notizenfeld im Dokument anzeigen</p>
                                                        </div>
                                                        <Switch
                                                            checked={layoutFormData.settings.content.show_notes}
                                                            onCheckedChange={(checked) => updateContentSetting("show_notes", checked)}
                                                        />
                                                    </div>
                                                </div>
                                            </div>

                                            <div>
                                                <h3 className="text-lg font-semibold mb-3">Fußzeilen-Informationen</h3>
                                                <div className="space-y-4">
                                                    <div className="flex items-center justify-between">
                                                        <div className="space-y-0.5">
                                                            <Label>Bankverbindung anzeigen</Label>
                                                            <p className="text-sm text-gray-600">IBAN, BIC und Bankname anzeigen</p>
                                                        </div>
                                                        <Switch
                                                            checked={layoutFormData.settings.content.show_bank_details}
                                                            onCheckedChange={(checked) => updateContentSetting("show_bank_details", checked)}
                                                        />
                                                    </div>

                                                    <div className="flex items-center justify-between">
                                                        <div className="space-y-0.5">
                                                            <Label>Handelsregister anzeigen</Label>
                                                            <p className="text-sm text-gray-600">Handelsregister und Steuernummer anzeigen</p>
                                                        </div>
                                                        <Switch
                                                            checked={layoutFormData.settings.content.show_company_registration}
                                                            onCheckedChange={(checked) => updateContentSetting("show_company_registration", checked)}
                                                        />
                                                    </div>

                                                    <div className="flex items-center justify-between">
                                                        <div className="space-y-0.5">
                                                            <Label>Zahlungsbedingungen anzeigen</Label>
                                                            <p className="text-sm text-gray-600">Zahlungsbedingungen im Dokument anzeigen</p>
                                                        </div>
                                                        <Switch
                                                            checked={layoutFormData.settings.content.show_payment_terms}
                                                            onCheckedChange={(checked) => updateContentSetting("show_payment_terms", checked)}
                                                        />
                                                    </div>
                                                </div>
                                            </div>

                                            <div>
                                                <h3 className="text-lg font-semibold mb-3">Artikeloptionen</h3>
                                                <div className="space-y-4">
                                                    <div className="flex items-center justify-between">
                                                        <div className="space-y-0.5">
                                                            <Label>Artikelbilder anzeigen</Label>
                                                            <p className="text-sm text-gray-600">Produktbilder in der Artikeltabelle anzeigen</p>
                                                        </div>
                                                        <Switch
                                                            checked={layoutFormData.settings.content.show_item_images}
                                                            onCheckedChange={(checked) => updateContentSetting("show_item_images", checked)}
                                                        />
                                                    </div>

                                                    <div className="flex items-center justify-between">
                                                        <div className="space-y-0.5">
                                                            <Label>Artikelnummern anzeigen</Label>
                                                            <p className="text-sm text-gray-600">Produktnummern in der Artikeltabelle anzeigen</p>
                                                        </div>
                                                        <Switch
                                                            checked={layoutFormData.settings.content.show_item_codes}
                                                            onCheckedChange={(checked) => updateContentSetting("show_item_codes", checked)}
                                                        />
                                                    </div>

                                                    <div className="flex items-center justify-between">
                                                        <div className="space-y-0.5">
                                                            <Label>Steueraufschlüsselung anzeigen</Label>
                                                            <p className="text-sm text-gray-600">Detaillierte Steuerberechnung anzeigen</p>
                                                        </div>
                                                        <Switch
                                                            checked={layoutFormData.settings.content.show_tax_breakdown}
                                                            onCheckedChange={(checked) => updateContentSetting("show_tax_breakdown", checked)}
                                                        />
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="custom-footer">Benutzerdefinierter Fußzeilentext</Label>
                                                <Textarea
                                                    id="custom-footer"
                                                    value={layoutFormData.settings.content.custom_footer_text}
                                                    onChange={(e) => updateContentSetting("custom_footer_text", e.target.value)}
                                                    placeholder="z.B. Vielen Dank für Ihr Vertrauen!"
                                                    rows={3}
                                                />
                                                <p className="text-sm text-gray-500">Dieser Text wird am Ende jedes Dokuments angezeigt.</p>
                                            </div>
                                        </div>
                                    </TabsContent>
                                </Tabs>

                                <DialogFooter className="mt-6">
                                    <Button type="button" variant="outline" onClick={handleCloseDialog} disabled={form.processing}>
                                        Abbrechen
                                    </Button>
                                    <Button type="submit" disabled={form.processing}>
                                        {form.processing ? "Speichert..." : editingLayout ? "Layout aktualisieren" : "Layout erstellen"}
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                {layouts.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <Layout className="h-12 w-12 text-gray-400 mb-4" />
                            <h3 className="text-lg font-semibold text-gray-900 mb-2">Keine Layouts vorhanden</h3>
                            <p className="text-gray-600 text-center mb-4">
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
