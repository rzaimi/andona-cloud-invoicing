"use client"

import type React from "react"
import { Head, Link, useForm } from "@inertiajs/react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Checkbox } from "@/components/ui/checkbox"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { ArrowLeft, Save, Folder, AlertCircle } from 'lucide-react'
import AppLayout from "@/layouts/app-layout"
import type { User, Category } from "@/types"

interface CategoryCreateProps {
    user: User
    parentCategories: Category[]
}

interface CategoryFormData {
    name: string
    description: string
    color: string
    icon: string
    parent_id: string | null
    sort_order: number
    is_active: boolean
}

export default function CategoryCreate({ user, parentCategories }: CategoryCreateProps) {
    const { data, setData, post, processing, errors } = useForm<CategoryFormData>({
        name: "",
        description: "",
        color: "#3b82f6",
        icon: "none",
        parent_id: null,
        sort_order: 0,
        is_active: true,
    })

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()

        // Transform data before sending
        const submitData = {
            ...data,
            parent_id: data.parent_id === "none" ? null : data.parent_id,
            icon: data.icon === "none" ? null : data.icon,
        }

        post("/categories", {
            data: submitData,
        })
    }

    const handleParentChange = (value: string) => {
        setData("parent_id", value === "none" ? null : value)
    }

    const handleIconChange = (value: string) => {
        setData("icon", value === "none" ? null : value)
    }

    // Check if there are any errors
    const hasErrors = Object.keys(errors).length > 0

    const colorOptions = [
        { value: "#3b82f6", label: "Blau", color: "#3b82f6" },
        { value: "#10b981", label: "Grün", color: "#10b981" },
        { value: "#f59e0b", label: "Orange", color: "#f59e0b" },
        { value: "#ef4444", label: "Rot", color: "#ef4444" },
        { value: "#8b5cf6", label: "Lila", color: "#8b5cf6" },
        { value: "#06b6d4", label: "Cyan", color: "#06b6d4" },
        { value: "#84cc16", label: "Lime", color: "#84cc16" },
        { value: "#f97316", label: "Orange", color: "#f97316" },
    ]

    const iconOptions = [
        { value: "none", label: "Kein Icon" },
        { value: "folder", label: "📁 Ordner" },
        { value: "package", label: "📦 Paket" },
        { value: "tag", label: "🏷️ Tag" },
        { value: "star", label: "⭐ Stern" },
        { value: "heart", label: "❤️ Herz" },
        { value: "home", label: "🏠 Haus" },
        { value: "car", label: "🚗 Auto" },
        { value: "computer", label: "💻 Computer" },
    ]

    return (
        <AppLayout user={user}>
            <Head title="Neue Kategorie erstellen" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Button variant="ghost" asChild>
                            <Link href="/categories">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Zurück
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-1xl font-bold tracking-tight">Neue Kategorie erstellen</h1>
                            <p className="text-muted-foreground">Erstellen Sie eine neue Produktkategorie</p>
                        </div>
                    </div>
                </div>

                {/* Error Alert */}
                {hasErrors && (
                    <Alert variant="destructive">
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>
                            <div className="font-medium mb-2">Bitte korrigieren Sie die folgenden Fehler:</div>
                            <ul className="list-disc list-inside space-y-1">
                                {Object.entries(errors).map(([field, message]) => (
                                    <li key={field} className="text-sm">
                                        <strong>{getFieldLabel(field)}:</strong> {message}
                                    </li>
                                ))}
                            </ul>
                        </AlertDescription>
                    </Alert>
                )}

                <form onSubmit={handleSubmit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Kategorieinformationen</CardTitle>
                            <CardDescription>Grundlegende Informationen über die Kategorie</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div className="grid gap-6 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="name">Kategoriename *</Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) => setData("name", e.target.value)}
                                        placeholder="z.B. Beratungsleistungen"
                                        required
                                        className={errors.name ? "border-red-500" : ""}
                                    />
                                    {errors.name && <p className="text-sm text-red-600">{errors.name}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="parent_id">Übergeordnete Kategorie</Label>
                                    <Select
                                        value={data.parent_id || "none"}
                                        onValueChange={handleParentChange}
                                    >
                                        <SelectTrigger className={errors.parent_id ? "border-red-500" : ""}>
                                            <SelectValue placeholder="Kategorie wählen" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none">Hauptkategorie</SelectItem>
                                            {parentCategories.map((category) => (
                                                <SelectItem key={category.id} value={category.id.toString()}>
                                                    {category.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.parent_id && <p className="text-sm text-red-600">{errors.parent_id}</p>}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="description">Beschreibung</Label>
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData("description", e.target.value)}
                                    placeholder="Beschreibung der Kategorie..."
                                    rows={3}
                                    className={errors.description ? "border-red-500" : ""}
                                />
                                {errors.description && <p className="text-sm text-red-600">{errors.description}</p>}
                            </div>

                            <div className="grid gap-6 md:grid-cols-3">
                                <div className="space-y-2">
                                    <Label htmlFor="color">Farbe</Label>
                                    <Select value={data.color} onValueChange={(value) => setData("color", value)}>
                                        <SelectTrigger className={errors.color ? "border-red-500" : ""}>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {colorOptions.map((option) => (
                                                <SelectItem key={option.value} value={option.value}>
                                                    <div className="flex items-center space-x-2">
                                                        <div className="w-4 h-4 rounded-full border" style={{ backgroundColor: option.color }} />
                                                        <span>{option.label}</span>
                                                    </div>
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.color && <p className="text-sm text-red-600">{errors.color}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="icon">Icon</Label>
                                    <Select
                                        value={data.icon || "none"}
                                        onValueChange={handleIconChange}
                                    >
                                        <SelectTrigger className={errors.icon ? "border-red-500" : ""}>
                                            <SelectValue placeholder="Icon wählen" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {iconOptions.map((option) => (
                                                <SelectItem key={option.value} value={option.value}>
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.icon && <p className="text-sm text-red-600">{errors.icon}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="sort_order">Reihenfolge</Label>
                                    <Input
                                        id="sort_order"
                                        type="number"
                                        min="0"
                                        value={data.sort_order}
                                        onChange={(e) => setData("sort_order", Number.parseInt(e.target.value) || 0)}
                                        placeholder="0"
                                        className={errors.sort_order ? "border-red-500" : ""}
                                    />
                                    <p className="text-sm text-muted-foreground">Niedrigere Zahlen werden zuerst angezeigt</p>
                                    {errors.sort_order && <p className="text-sm text-red-600">{errors.sort_order}</p>}
                                </div>
                            </div>

                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="is_active"
                                    checked={data.is_active}
                                    onCheckedChange={(checked) => setData("is_active", !!checked)}
                                />
                                <Label htmlFor="is_active">Kategorie ist aktiv</Label>
                            </div>

                            {/* Preview */}
                            <div className="rounded-lg bg-muted p-4">
                                <h4 className="font-medium mb-2">Vorschau</h4>
                                <div className="flex items-center space-x-2">
                                    <div className="w-4 h-4 rounded-full border" style={{ backgroundColor: data.color }} />
                                    <Folder className="h-4 w-4 text-muted-foreground" />
                                    <span className="font-medium">{data.name || "Kategoriename"}</span>
                                    {data.icon && data.icon !== "none" && (
                                        <span>{iconOptions.find((opt) => opt.value === data.icon)?.label.split(" ")[0]}</span>
                                    )}
                                </div>
                                {data.description && <p className="text-sm text-muted-foreground mt-2">{data.description}</p>}
                                {data.parent_id && data.parent_id !== "none" && (
                                    <p className="text-sm text-muted-foreground mt-1">
                                        Übergeordnete Kategorie: {parentCategories.find(cat => cat.id.toString() === data.parent_id)?.name}
                                    </p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Actions */}
                    <div className="flex items-center justify-end space-x-4">
                        <Button variant="outline" asChild>
                            <Link href="/categories">Abbrechen</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            <Save className="mr-2 h-4 w-4" />
                            {processing ? "Speichern..." : "Kategorie erstellen"}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    )
}

// Helper function to get field labels in German
function getFieldLabel(field: string): string {
    const labels: Record<string, string> = {
        name: "Kategoriename",
        description: "Beschreibung",
        color: "Farbe",
        icon: "Icon",
        parent_id: "Übergeordnete Kategorie",
        sort_order: "Reihenfolge",
        is_active: "Status",
    }
    return labels[field] || field
}
