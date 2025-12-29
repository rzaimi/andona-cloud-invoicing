"use client"

import { useState } from "react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Badge } from "@/components/ui/badge"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { Plus, Edit, Trash2, Eye, FileText, EuroIcon } from "lucide-react"
import InvoiceForm from "./invoice-form"
import InvoicePreview from "./invoice-preview"

interface InvoiceItem {
    id: number
    description: string
    quantity: number
    unit_price: number
    total: number
}

interface Invoice {
    id: number
    number: string
    type: "invoice" | "offer"
    customer_id: number
    customer_name: string
    company_id: number
    status: "draft" | "sent" | "paid" | "overdue"
    issue_date: string
    due_date: string
    items: InvoiceItem[]
    subtotal: number
    tax_amount: number
    total: number
    notes?: string
    layout_id: number
}

interface InvoiceManagementProps {
    companyId: number
    settings: {
        currency: string
        tax_rate: number
        invoice_prefix: string
        offer_prefix: string
    }
}

export default function InvoiceManagement({ companyId, settings }: InvoiceManagementProps) {
    const [invoices, setInvoices] = useState<Invoice[]>([
        {
            id: 1,
            number: "INV-2024-001",
            type: "invoice",
            customer_id: 1,
            customer_name: "ABC Corporation",
            company_id: 1,
            status: "sent",
            issue_date: "2024-01-15",
            due_date: "2024-02-15",
            items: [
                { id: 1, description: "Web Development", quantity: 1, unit_price: 5000, total: 5000 },
                { id: 2, description: "SEO Optimization", quantity: 1, unit_price: 1500, total: 1500 },
            ],
            subtotal: 6500,
            tax_amount: 1365,
            total: 7865,
            layout_id: 1,
        },
        {
            id: 2,
            number: "OFF-2024-001",
            type: "offer",
            customer_id: 2,
            customer_name: "XYZ Ltd",
            company_id: 1,
            status: "draft",
            issue_date: "2024-01-20",
            due_date: "2024-02-20",
            items: [{ id: 1, description: "Mobile App Development", quantity: 1, unit_price: 8000, total: 8000 }],
            subtotal: 8000,
            tax_amount: 1680,
            total: 9680,
            layout_id: 1,
        },
    ])

    const [activeTab, setActiveTab] = useState("invoices")
    const [isFormOpen, setIsFormOpen] = useState(false)
    const [isPreviewOpen, setIsPreviewOpen] = useState(false)
    const [editingInvoice, setEditingInvoice] = useState<Invoice | null>(null)
    const [previewInvoice, setPreviewInvoice] = useState<Invoice | null>(null)

    // Filter invoices by company and type
    const filteredInvoices = invoices.filter(
        (invoice) =>
            invoice.company_id === companyId &&
            (activeTab === "invoices" ? invoice.type === "invoice" : invoice.type === "offer"),
    )

    const handleCreateNew = (type: "invoice" | "offer") => {
        setEditingInvoice(null)
        setIsFormOpen(true)
    }

    const handleEdit = (invoice: Invoice) => {
        setEditingInvoice(invoice)
        setIsFormOpen(true)
    }

    const handlePreview = (invoice: Invoice) => {
        setPreviewInvoice(invoice)
        setIsPreviewOpen(true)
    }

    const handleDelete = (id: number) => {
        setInvoices(invoices.filter((invoice) => invoice.id !== id))
    }

    const handleSaveInvoice = (invoiceData: Partial<Invoice>) => {
        if (editingInvoice) {
            // Update existing invoice
            setInvoices(
                invoices.map((invoice) => (invoice.id === editingInvoice.id ? { ...invoice, ...invoiceData } : invoice)),
            )
        } else {
            // Create new invoice
            const newInvoice: Invoice = {
                id: Date.now(),
                company_id: companyId,
                status: "draft",
                layout_id: 1,
                ...invoiceData,
            } as Invoice
            setInvoices([...invoices, newInvoice])
        }
        setIsFormOpen(false)
    }

    const getStatusColor = (status: string) => {
        switch (status) {
            case "paid":
                return "default"
            case "sent":
                return "secondary"
            case "overdue":
                return "destructive"
            default:
                return "outline"
        }
    }

    return (
        <div className="space-y-6">
            <div className="flex justify-between items-center">
                <div>
                    <h2 className="text-3xl font-bold text-gray-900">Invoice & Offer Management</h2>
                    <p className="text-gray-600">Create and manage invoices and offers</p>
                </div>
            </div>

            <Tabs value={activeTab} onValueChange={setActiveTab}>
                <TabsList className="grid w-full grid-cols-2">
                    <TabsTrigger value="invoices" className="flex items-center gap-2">
                        <EuroIcon className="h-4 w-4" />
                        Invoices
                    </TabsTrigger>
                    <TabsTrigger value="offers" className="flex items-center gap-2">
                        <FileText className="h-4 w-4" />
                        Offers
                    </TabsTrigger>
                </TabsList>

                <TabsContent value="invoices" className="space-y-4">
                    <div className="flex justify-end">
                        <Button onClick={() => handleCreateNew("invoice")}>
                            <Plus className="mr-2 h-4 w-4" />
                            Create Invoice
                        </Button>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Invoices</CardTitle>
                            <CardDescription>All invoices for your company</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Invoice #</TableHead>
                                        <TableHead>Customer</TableHead>
                                        <TableHead>Issue Date</TableHead>
                                        <TableHead>Due Date</TableHead>
                                        <TableHead>Total</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {filteredInvoices.map((invoice) => (
                                        <TableRow key={invoice.id}>
                                            <TableCell className="font-medium">{invoice.number}</TableCell>
                                            <TableCell>{invoice.customer_name}</TableCell>
                                            <TableCell>{invoice.issue_date}</TableCell>
                                            <TableCell>{invoice.due_date}</TableCell>
                                            <TableCell>
                                                {settings.currency} {invoice.total.toFixed(2)}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={getStatusColor(invoice.status)}>{invoice.status}</Badge>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex space-x-2">
                                                    <Button variant="outline" size="sm" onClick={() => handlePreview(invoice)}>
                                                        <Eye className="h-4 w-4" />
                                                    </Button>
                                                    <Button variant="outline" size="sm" onClick={() => handleEdit(invoice)}>
                                                        <Edit className="h-4 w-4" />
                                                    </Button>
                                                    <Button variant="outline" size="sm" onClick={() => handleDelete(invoice.id)}>
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

                <TabsContent value="offers" className="space-y-4">
                    <div className="flex justify-end">
                        <Button onClick={() => handleCreateNew("offer")}>
                            <Plus className="mr-2 h-4 w-4" />
                            Create Offer
                        </Button>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Offers</CardTitle>
                            <CardDescription>All offers for your company</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Offer #</TableHead>
                                        <TableHead>Customer</TableHead>
                                        <TableHead>Issue Date</TableHead>
                                        <TableHead>Valid Until</TableHead>
                                        <TableHead>Total</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {filteredInvoices.map((invoice) => (
                                        <TableRow key={invoice.id}>
                                            <TableCell className="font-medium">{invoice.number}</TableCell>
                                            <TableCell>{invoice.customer_name}</TableCell>
                                            <TableCell>{invoice.issue_date}</TableCell>
                                            <TableCell>{invoice.due_date}</TableCell>
                                            <TableCell>
                                                {settings.currency} {invoice.total.toFixed(2)}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={getStatusColor(invoice.status)}>{invoice.status}</Badge>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex space-x-2">
                                                    <Button variant="outline" size="sm" onClick={() => handlePreview(invoice)}>
                                                        <Eye className="h-4 w-4" />
                                                    </Button>
                                                    <Button variant="outline" size="sm" onClick={() => handleEdit(invoice)}>
                                                        <Edit className="h-4 w-4" />
                                                    </Button>
                                                    <Button variant="outline" size="sm" onClick={() => handleDelete(invoice.id)}>
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
            </Tabs>

            {/* Invoice Form Dialog */}
            <Dialog open={isFormOpen} onOpenChange={setIsFormOpen}>
                <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>
                            {editingInvoice
                                ? `Edit ${editingInvoice.type}`
                                : `Create New ${activeTab === "invoices" ? "Invoice" : "Offer"}`}
                        </DialogTitle>
                    </DialogHeader>
                    <InvoiceForm
                        invoice={editingInvoice}
                        type={activeTab === "invoices" ? "invoice" : "offer"}
                        settings={settings}
                        onSave={handleSaveInvoice}
                        onCancel={() => setIsFormOpen(false)}
                    />
                </DialogContent>
            </Dialog>

            {/* Invoice Preview Dialog */}
            <Dialog open={isPreviewOpen} onOpenChange={setIsPreviewOpen}>
                <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>{previewInvoice?.type === "invoice" ? "Invoice" : "Offer"} Preview</DialogTitle>
                    </DialogHeader>
                    {previewInvoice && <InvoicePreview invoice={previewInvoice} settings={settings} />}
                </DialogContent>
            </Dialog>
        </div>
    )
}
