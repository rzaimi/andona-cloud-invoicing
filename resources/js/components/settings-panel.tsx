"use client"

import type React from "react"

import { useState } from "react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
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
import { Plus, Edit, Trash2 } from "lucide-react"

interface Company {
    id: number
    name: string
    settings: {
        currency: string
        tax_rate: number
        invoice_prefix: string
        offer_prefix: string
    }
}

interface InvoiceLayout {
    id: number
    name: string
    type: "invoice" | "offer" | "both"
    is_default: boolean
    template: string
    created_at: string
}

interface SettingsPanelProps {
    company: Company
}

export default function SettingsPanel({ company }: SettingsPanelProps) {
    const [settings, setSettings] = useState(company.settings)
    const [layouts, setLayouts] = useState<InvoiceLayout[]>([
        {
            id: 1,
            name: "Modern Layout",
            type: "both",
            is_default: true,
            template: "modern",
            created_at: "2024-01-15",
        },
        {
            id: 2,
            name: "Classic Invoice",
            type: "invoice",
            is_default: false,
            template: "classic",
            created_at: "2024-01-20",
        },
        {
            id: 3,
            name: "Minimal Offer",
            type: "offer",
            is_default: false,
            template: "minimal",
            created_at: "2024-02-01",
        },
    ])

    const [isLayoutDialogOpen, setIsLayoutDialogOpen] = useState(false)
    const [editingLayout, setEditingLayout] = useState<InvoiceLayout | null>(null)
    const [layoutFormData, setLayoutFormData] = useState({
        name: "",
        type: "both" as "invoice" | "offer" | "both",
        template: "modern",
    })

    const handleSaveSettings = () => {
        // Save settings to API
        console.log("Saving settings:", settings)
    }

    const handleCreateLayout = () => {
        setEditingLayout(null)
        setLayoutFormData({ name: "", type: "both", template: "modern" })
        setIsLayoutDialogOpen(true)
    }

    const handleEditLayout = (layout: InvoiceLayout) => {
        setEditingLayout(layout)
        setLayoutFormData({
            name: layout.name,
            type: layout.type,
            template: layout.template,
        })
        setIsLayoutDialogOpen(true)
    }

    const handleSaveLayout = (e: React.FormEvent) => {
        e.preventDefault()

        if (editingLayout) {
            // Update existing layout
            setLayouts(layouts.map((layout) => (layout.id === editingLayout.id ? { ...layout, ...layoutFormData } : layout)))
        } else {
            // Create new layout
            const newLayout: InvoiceLayout = {
                id: Date.now(),
                ...layoutFormData,
                is_default: false,
                created_at: new Date().toISOString().split("T")[0],
            }
            setLayouts([...layouts, newLayout])
        }

        setIsLayoutDialogOpen(false)
    }

    const handleDeleteLayout = (id: number) => {
        setLayouts(layouts.filter((layout) => layout.id !== id))
    }

    const handleSetDefaultLayout = (id: number) => {
        setLayouts(
            layouts.map((layout) => ({
                ...layout,
                is_default: layout.id === id,
            })),
        )
    }

    return (
        <div className="space-y-6">
            <div>
                <h2 className="text-3xl font-bold text-gray-900">Settings</h2>
                <p className="text-gray-600">Configure your company settings and invoice layouts</p>
            </div>

            <Tabs defaultValue="general" className="space-y-4">
                <TabsList>
                    <TabsTrigger value="general">General Settings</TabsTrigger>
                    <TabsTrigger value="layouts">Invoice Layouts</TabsTrigger>
                    <TabsTrigger value="notifications">Notifications</TabsTrigger>
                </TabsList>

                <TabsContent value="general" className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Company Information</CardTitle>
                            <CardDescription>Basic company details and invoice settings</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="company-name">Company Name</Label>
                                    <Input id="company-name" value={company.name} disabled />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="currency">Currency</Label>
                                    <Select
                                        value={settings.currency}
                                        onValueChange={(value) => setSettings({ ...settings, currency: value })}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="USD">USD ($)</SelectItem>
                                            <SelectItem value="EUR">EUR (€)</SelectItem>
                                            <SelectItem value="GBP">GBP (£)</SelectItem>
                                            <SelectItem value="JPY">JPY (¥)</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="tax-rate">Tax Rate (%)</Label>
                                    <Input
                                        id="tax-rate"
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        value={settings.tax_rate * 100}
                                        onChange={(e) =>
                                            setSettings({ ...settings, tax_rate: Number.parseFloat(e.target.value) / 100 || 0 })
                                        }
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="invoice-prefix">Invoice Prefix</Label>
                                    <Input
                                        id="invoice-prefix"
                                        value={settings.invoice_prefix}
                                        onChange={(e) => setSettings({ ...settings, invoice_prefix: e.target.value })}
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="offer-prefix">Offer Prefix</Label>
                                    <Input
                                        id="offer-prefix"
                                        value={settings.offer_prefix}
                                        onChange={(e) => setSettings({ ...settings, offer_prefix: e.target.value })}
                                    />
                                </div>
                            </div>

                            <div className="flex justify-end">
                                <Button onClick={handleSaveSettings}>Save Settings</Button>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="layouts" className="space-y-6">
                    <div className="flex justify-between items-center">
                        <div>
                            <h3 className="text-xl font-semibold">Invoice & Offer Layouts</h3>
                            <p className="text-gray-600">Create and manage different layouts for your invoices and offers</p>
                        </div>

                        <Dialog open={isLayoutDialogOpen} onOpenChange={setIsLayoutDialogOpen}>
                            <DialogTrigger asChild>
                                <Button onClick={handleCreateLayout}>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Create Layout
                                </Button>
                            </DialogTrigger>
                            <DialogContent className="sm:max-w-[425px]">
                                <DialogHeader>
                                    <DialogTitle>{editingLayout ? "Edit Layout" : "Create New Layout"}</DialogTitle>
                                    <DialogDescription>
                                        {editingLayout ? "Update layout settings" : "Create a new invoice/offer layout"}
                                    </DialogDescription>
                                </DialogHeader>
                                <form onSubmit={handleSaveLayout}>
                                    <div className="grid gap-4 py-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="layout-name">Layout Name</Label>
                                            <Input
                                                id="layout-name"
                                                value={layoutFormData.name}
                                                onChange={(e) => setLayoutFormData({ ...layoutFormData, name: e.target.value })}
                                                required
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="layout-type">Type</Label>
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
                                                    <SelectItem value="both">Both Invoice & Offer</SelectItem>
                                                    <SelectItem value="invoice">Invoice Only</SelectItem>
                                                    <SelectItem value="offer">Offer Only</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="template">Template Style</Label>
                                            <Select
                                                value={layoutFormData.template}
                                                onValueChange={(value) => setLayoutFormData({ ...layoutFormData, template: value })}
                                            >
                                                <SelectTrigger>
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="modern">Modern</SelectItem>
                                                    <SelectItem value="classic">Classic</SelectItem>
                                                    <SelectItem value="minimal">Minimal</SelectItem>
                                                    <SelectItem value="professional">Professional</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>
                                    <DialogFooter>
                                        <Button type="submit">{editingLayout ? "Update Layout" : "Create Layout"}</Button>
                                    </DialogFooter>
                                </form>
                            </DialogContent>
                        </Dialog>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Available Layouts</CardTitle>
                            <CardDescription>Manage your invoice and offer layouts</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Layout Name</TableHead>
                                        <TableHead>Type</TableHead>
                                        <TableHead>Template</TableHead>
                                        <TableHead>Default</TableHead>
                                        <TableHead>Created</TableHead>
                                        <TableHead>Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {layouts.map((layout) => (
                                        <TableRow key={layout.id}>
                                            <TableCell className="font-medium">{layout.name}</TableCell>
                                            <TableCell>
                                                <Badge variant="outline">{layout.type === "both" ? "Invoice & Offer" : layout.type}</Badge>
                                            </TableCell>
                                            <TableCell className="capitalize">{layout.template}</TableCell>
                                            <TableCell>
                                                {layout.is_default ? (
                                                    <Badge>Default</Badge>
                                                ) : (
                                                    <Button variant="outline" size="sm" onClick={() => handleSetDefaultLayout(layout.id)}>
                                                        Set Default
                                                    </Button>
                                                )}
                                            </TableCell>
                                            <TableCell>{layout.created_at}</TableCell>
                                            <TableCell>
                                                <div className="flex space-x-2">
                                                    <Button variant="outline" size="sm" onClick={() => handleEditLayout(layout)}>
                                                        <Edit className="h-4 w-4" />
                                                    </Button>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => handleDeleteLayout(layout.id)}
                                                        disabled={layout.is_default}
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
                </TabsContent>

                <TabsContent value="notifications" className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Email Notifications</CardTitle>
                            <CardDescription>Configure when to send email notifications</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-center justify-between">
                                <div className="space-y-0.5">
                                    <Label>Invoice Created</Label>
                                    <p className="text-sm text-gray-600">Send notification when a new invoice is created</p>
                                </div>
                                <Switch />
                            </div>

                            <div className="flex items-center justify-between">
                                <div className="space-y-0.5">
                                    <Label>Invoice Sent</Label>
                                    <p className="text-sm text-gray-600">Send notification when an invoice is sent to customer</p>
                                </div>
                                <Switch defaultChecked />
                            </div>

                            <div className="flex items-center justify-between">
                                <div className="space-y-0.5">
                                    <Label>Payment Received</Label>
                                    <p className="text-sm text-gray-600">Send notification when payment is received</p>
                                </div>
                                <Switch defaultChecked />
                            </div>

                            <div className="flex items-center justify-between">
                                <div className="space-y-0.5">
                                    <Label>Invoice Overdue</Label>
                                    <p className="text-sm text-gray-600">Send notification when an invoice becomes overdue</p>
                                </div>
                                <Switch defaultChecked />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Reminder Settings</CardTitle>
                            <CardDescription>Configure automatic payment reminders</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="first-reminder">First Reminder (days before due)</Label>
                                    <Input id="first-reminder" type="number" defaultValue="7" />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="second-reminder">Second Reminder (days after due)</Label>
                                    <Input id="second-reminder" type="number" defaultValue="3" />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="final-reminder">Final Reminder (days after due)</Label>
                                    <Input id="final-reminder" type="number" defaultValue="14" />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="reminder-template">Reminder Email Template</Label>
                                <Textarea
                                    id="reminder-template"
                                    placeholder="Dear [Customer Name], This is a friendly reminder that invoice [Invoice Number] is due on [Due Date]..."
                                    rows={4}
                                />
                            </div>

                            <div className="flex justify-end">
                                <Button>Save Reminder Settings</Button>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    )
}
