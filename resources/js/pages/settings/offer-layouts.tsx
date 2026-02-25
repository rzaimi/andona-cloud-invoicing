"use client"

import type React from "react"

import { useState } from "react"
import { Head, router, usePage } from "@inertiajs/react"
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
import { Plus, Edit, Trash2, Eye, Copy, Layout, Star, Download, Palette, FileText, Settings, Type, AlertCircle } from "lucide-react"
import { Alert, AlertDescription } from "@/components/ui/alert"
import AppLayout from "@/layouts/app-layout"
import { route } from "ziggy-js"
import { useForm } from "@inertiajs/react"

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
    }
    content: {
        show_item_images: boolean
        show_item_codes: boolean
        show_tax_breakdown: boolean
        show_payment_terms: boolean
        show_validity_period: boolean
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
    layouts: OfferLayout[]
    templates: Template[]
}

// Default settings to ensure all properties exist
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
    },
    content: {
        show_item_images: false,
        show_item_codes: true,
        show_tax_breakdown: true,
        show_payment_terms: true,
        show_validity_period: true,
        custom_footer_text: "",
    },
})

// Apply template-specific defaults
const getTemplateDefaults = (templateId: string, templates: Template[]): Partial<OfferLayoutSettings> => {
    const template = templates.find((t) => t.id === templateId)
    if (!template) return {}

    return {
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
}

// Merge layout settings with defaults to ensure all properties exist
const mergeWithDefaults = (settings: Partial<OfferLayoutSettings> | null): OfferLayoutSettings => {
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
            ...(settings.branding || {}),
        },
        content: {
            ...defaults.content,
            ...(settings.content || {}),
        },
        template_specific: settings.template_specific || {},
    }
}

export default function OfferLayoutsPage({ layouts, templates }: OfferLayoutsPageProps) {
    const { flash } = usePage<{ flash?: { success?: string; error?: string } }>().props
    const [isSubmitting, setIsSubmitting] = useState(false)
    const [isLayoutDialogOpen, setIsLayoutDialogOpen] = useState(false)
    const [editingLayout, setEditingLayout] = useState<OfferLayout | null>(null)
    const [previewLayout, setPreviewLayout] = useState<OfferLayout | null>(null)
    const [isPreviewOpen, setIsPreviewOpen] = useState(false)

    const form = useForm({
        name: "",
        template: "modern",
        settings: getDefaultSettings(),
    })

    const [layoutFormData, setLayoutFormData] = useState({
        name: "",
        template: "modern",
        settings: getDefaultSettings(),
    })

    const resetFormData = () => {
        setLayoutFormData({
            name: "",
            template: "modern",
            settings: getDefaultSettings(),
        })
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
            template: layout.template || "modern",
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
            template: layoutFormData.template,
            settings: layoutFormData.settings,
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
            router.put(route("offer-layouts.update", editingLayout.id), submitData, { onSuccess, onError })
        } else {
            router.post(route("offer-layouts.store"), submitData, { onSuccess, onError })
        }
    }

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

    // Update functions for form data
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
    const getPreviewFontFamily = (layout: OfferLayout | null) => {
        if (!layout?.settings?.fonts?.body) return "Inter"
        return layout.settings.fonts.body
    }

    const getPreviewTextColor = (layout: OfferLayout | null) => {
        if (!layout?.settings?.colors?.text) return "#1e293b"
        return layout.settings.colors.text
    }

    const getPreviewFontSize = (layout: OfferLayout | null) => {
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

    const getPreviewHeaderHeight = (layout: OfferLayout | null) => {
        if (!layout?.settings?.layout?.header_height) return 120
        return layout.settings.layout.header_height
    }

    const getPreviewFooterHeight = (layout: OfferLayout | null) => {
        if (!layout?.settings?.layout?.footer_height) return 80
        return layout.settings.layout.footer_height
    }

    const hasErrors = Object.keys(form.errors).length > 0

    return (
        <AppLayout>
            <Head title="Angebotslayouts" />

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
                        <DialogContent className="max-w-5xl max-h-[90vh] overflow-y-auto">
                            <DialogHeader>
                                <DialogTitle>
                                    {editingLayout ? "Angebotslayout bearbeiten" : "Neues Angebotslayout erstellen"}
                                </DialogTitle>
                                <DialogDescription>
                                    {editingLayout
                                        ? "Bearbeiten Sie die Layout-Einstellungen für Angebote"
                                        : "Erstellen Sie ein neues Angebotslayout mit individuellen Einstellungen"}
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
                                                            <p className="text-sm text-muted-foreground">Firmenlogo im Angebot anzeigen</p>
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
                                                </div>
                                            </div>
                                        </div>
                                    </TabsContent>

                                    <TabsContent value="content" className="space-y-4">
                                        <div className="space-y-6">
                                            <div>
                                                <h3 className="text-lg font-semibold mb-3">Inhaltsoptionen</h3>
                                                <div className="space-y-4">
                                                    <div className="flex items-center justify-between">
                                                        <div className="space-y-0.5">
                                                            <Label>Artikelbilder anzeigen</Label>
                                                            <p className="text-sm text-muted-foreground">Produktbilder in der Artikeltabelle anzeigen</p>
                                                        </div>
                                                        <Switch
                                                            checked={layoutFormData.settings.content.show_item_images}
                                                            onCheckedChange={(checked) => updateContentSetting("show_item_images", checked)}
                                                        />
                                                    </div>

                                                    <div className="flex items-center justify-between">
                                                        <div className="space-y-0.5">
                                                            <Label>Artikelnummern anzeigen</Label>
                                                            <p className="text-sm text-muted-foreground">Produktnummern in der Artikeltabelle anzeigen</p>
                                                        </div>
                                                        <Switch
                                                            checked={layoutFormData.settings.content.show_item_codes}
                                                            onCheckedChange={(checked) => updateContentSetting("show_item_codes", checked)}
                                                        />
                                                    </div>

                                                    <div className="flex items-center justify-between">
                                                        <div className="space-y-0.5">
                                                            <Label>Steueraufschlüsselung anzeigen</Label>
                                                            <p className="text-sm text-muted-foreground">Detaillierte Steuerberechnung anzeigen</p>
                                                        </div>
                                                        <Switch
                                                            checked={layoutFormData.settings.content.show_tax_breakdown}
                                                            onCheckedChange={(checked) => updateContentSetting("show_tax_breakdown", checked)}
                                                        />
                                                    </div>

                                                    <div className="flex items-center justify-between">
                                                        <div className="space-y-0.5">
                                                            <Label>Zahlungsbedingungen anzeigen</Label>
                                                            <p className="text-sm text-muted-foreground">Zahlungsbedingungen im Angebot anzeigen</p>
                                                        </div>
                                                        <Switch
                                                            checked={layoutFormData.settings.content.show_payment_terms}
                                                            onCheckedChange={(checked) => updateContentSetting("show_payment_terms", checked)}
                                                        />
                                                    </div>

                                                    <div className="flex items-center justify-between">
                                                        <div className="space-y-0.5">
                                                            <Label>Gültigkeitsdauer anzeigen</Label>
                                                            <p className="text-sm text-muted-foreground">Gültigkeitsdauer des Angebots hervorheben</p>
                                                        </div>
                                                        <Switch
                                                            checked={layoutFormData.settings.content.show_validity_period}
                                                            onCheckedChange={(checked) => updateContentSetting("show_validity_period", checked)}
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
                                                    placeholder="z.B. Wir freuen uns auf Ihre Rückmeldung!"
                                                    rows={3}
                                                />
                                                <p className="text-sm text-gray-500">Dieser Text wird am Ende jedes Angebots angezeigt.</p>
                                            </div>
                                        </div>
                                    </TabsContent>
                                </Tabs>

                                <DialogFooter className="mt-6">
                                    <Button type="button" variant="outline" onClick={handleCloseDialog} disabled={isSubmitting}>
                                        Abbrechen
                                    </Button>
                                    <Button type="submit" disabled={isSubmitting}>
                                        {isSubmitting ? "Speichert..." : editingLayout ? "Layout aktualisieren" : "Layout erstellen"}
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
                    <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle>Layout Vorschau: {previewLayout?.name}</DialogTitle>
                            <DialogDescription>Vorschau des Angebotslayouts mit Beispieldaten</DialogDescription>
                        </DialogHeader>

                        {previewLayout ? (
                            <div className="border rounded-lg p-6 bg-white shadow-sm">
                                <div
                                    className="space-y-6"
                                    style={{
                                        fontFamily: getPreviewFontFamily(previewLayout),
                                        color: getPreviewTextColor(previewLayout),
                                        fontSize: getPreviewFontSize(previewLayout),
                                    }}
                                >
                                    {/* Header */}
                                    <div
                                        className="flex justify-between items-start pb-4"
                                        style={{ minHeight: `${getPreviewHeaderHeight(previewLayout)}px` }}
                                    >
                                        <div>
                                            <h1
                                                className="text-3xl font-bold mb-2"
                                                style={{
                                                    color: previewLayout.settings?.colors?.primary || "#2563eb",
                                                    fontFamily: previewLayout.settings?.fonts?.heading || "Inter",
                                                }}
                                            >
                                                ANGEBOT
                                            </h1>
                                            <p className="text-lg font-semibold">AN-2024-0001</p>
                                        </div>
                                        {previewLayout.settings?.branding?.show_logo && (
                                            <div
                                                className={`text-${previewLayout.settings.branding.logo_position?.split("-")[1] || "right"}`}
                                            >
                                                <div className="w-24 h-16 bg-gray-200 rounded mb-2 flex items-center justify-center text-xs">
                                                    Logo
                                                </div>
                                                <h2 className="text-xl font-bold mb-2">Ihre Firma GmbH</h2>
                                                <p className="text-sm">Musterstraße 123</p>
                                                <p className="text-sm">12345 Musterstadt</p>
                                            </div>
                                        )}
                                    </div>

                                    {/* Customer Info */}
                                    <div className="grid grid-cols-2 gap-8 py-4">
                                        <div>
                                            <h3
                                                className="text-lg font-semibold mb-2"
                                                style={{ color: previewLayout.settings?.colors?.secondary || "#64748b" }}
                                            >
                                                Angebot für:
                                            </h3>
                                            <p className="font-semibold">Musterkunde GmbH</p>
                                            <p>Kundenstraße 456</p>
                                            <p>54321 Kundenstadt</p>
                                        </div>
                                        <div className="text-right">
                                            <div className="mb-4">
                                                <p className="text-muted-foreground">Angebotsdatum:</p>
                                                <p className="font-semibold">01.12.2024</p>
                                            </div>
                                            {previewLayout.settings?.content?.show_validity_period && (
                                                <div className="mb-4">
                                                    <p className="text-muted-foreground">Gültig bis:</p>
                                                    <p className="font-semibold text-red-600">31.12.2024</p>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    {/* Items Table */}
                                    <div className="py-4">
                                        <table className="w-full border-collapse border border-gray-300">
                                            <thead>
                                            <tr style={{ backgroundColor: (previewLayout.settings?.colors?.primary || "#2563eb") + "15" }}>
                                                {previewLayout.settings?.content?.show_item_codes && (
                                                    <th className="border border-gray-300 px-4 py-3 text-left font-semibold">Art.-Nr.</th>
                                                )}
                                                <th className="border border-gray-300 px-4 py-3 text-left font-semibold">Beschreibung</th>
                                                <th className="border border-gray-300 px-4 py-3 text-center font-semibold">Menge</th>
                                                <th className="border border-gray-300 px-4 py-3 text-right font-semibold">Einzelpreis</th>
                                                <th className="border border-gray-300 px-4 py-3 text-right font-semibold">Gesamt</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr>
                                                {previewLayout.settings?.content?.show_item_codes && (
                                                    <td className="border border-gray-300 px-4 py-2">ART-001</td>
                                                )}
                                                <td className="border border-gray-300 px-4 py-2">Beispielprodukt</td>
                                                <td className="border border-gray-300 px-4 py-2 text-center">2</td>
                                                <td className="border border-gray-300 px-4 py-2 text-right">€ 50,00</td>
                                                <td className="border border-gray-300 px-4 py-2 text-right">€ 100,00</td>
                                            </tr>
                                            <tr>
                                                {previewLayout.settings?.content?.show_item_codes && (
                                                    <td className="border border-gray-300 px-4 py-2">ART-002</td>
                                                )}
                                                <td className="border border-gray-300 px-4 py-2">Weiteres Produkt</td>
                                                <td className="border border-gray-300 px-4 py-2 text-center">1</td>
                                                <td className="border border-gray-300 px-4 py-2 text-right">€ 25,00</td>
                                                <td className="border border-gray-300 px-4 py-2 text-right">€ 25,00</td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    {/* Totals */}
                                    <div className="flex justify-end py-4">
                                        <div className="w-64">
                                            <div className="flex justify-between py-2">
                                                <span>Zwischensumme:</span>
                                                <span>€ 125,00</span>
                                            </div>
                                            {previewLayout.settings?.content?.show_tax_breakdown && (
                                                <div className="flex justify-between py-2">
                                                    <span>MwSt. (19%):</span>
                                                    <span>€ 23,75</span>
                                                </div>
                                            )}
                                            <div
                                                className="flex justify-between py-2 border-t-2 font-bold text-lg"
                                                style={{
                                                    borderColor: previewLayout.settings?.colors?.primary || "#2563eb",
                                                    color: previewLayout.settings?.colors?.primary || "#2563eb",
                                                }}
                                            >
                                                <span>Gesamtsumme:</span>
                                                <span>€ 148,75</span>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Payment Terms */}
                                    {previewLayout.settings?.content?.show_payment_terms && (
                                        <div className="py-4">
                                            <h3
                                                className="text-lg font-semibold mb-2"
                                                style={{ color: previewLayout.settings?.colors?.secondary || "#64748b" }}
                                            >
                                                Zahlungsbedingungen:
                                            </h3>
                                            <p className="text-sm">Bei Auftragserteilung 50% Anzahlung, Rest bei Lieferung.</p>
                                        </div>
                                    )}

                                    {/* Custom Footer */}
                                    {previewLayout.settings?.content?.custom_footer_text && (
                                        <div
                                            className="text-center text-gray-500 text-sm border-t pt-4 mt-6"
                                            style={{ minHeight: `${getPreviewFooterHeight(previewLayout)}px` }}
                                        >
                                            <p>{previewLayout.settings.content.custom_footer_text}</p>
                                        </div>
                                    )}
                                </div>
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
