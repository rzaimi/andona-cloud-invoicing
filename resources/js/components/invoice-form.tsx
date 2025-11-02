"use client"

import type React from "react"

import { useState, useEffect } from "react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Textarea } from "@/components/ui/textarea"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Plus, Trash2 } from "lucide-react"

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

interface InvoiceFormProps {
    invoice?: Invoice | null
    type: "invoice" | "offer"
    settings: {
        currency: string
        tax_rate: number
        invoice_prefix: string
        offer_prefix: string
    }
    onSave: (invoice: Partial<Invoice>) => void
    onCancel: () => void
}

export default function InvoiceForm({ invoice, type, settings, onSave, onCancel }: InvoiceFormProps) {
    const [formData, setFormData] = useState({
        number: "",
        customer_id: "",
        customer_name: "",
        issue_date: "",
        due_date: "",
        notes: "",
    })

    const [items, setItems] = useState<InvoiceItem[]>([{ id: 1, description: "", quantity: 1, unit_price: 0, total: 0 }])

    // Mock customers - would come from API
    const customers = [
        { id: 1, name: "ABC Corporation" },
        { id: 2, name: "XYZ Ltd" },
        { id: 3, name: "Tech Solutions Inc" },
    ]

    useEffect(() => {
        if (invoice) {
            setFormData({
                number: invoice.number,
                customer_id: invoice.customer_id.toString(),
                customer_name: invoice.customer_name,
                issue_date: invoice.issue_date,
                due_date: invoice.due_date,
                notes: invoice.notes || "",
            })
            setItems(invoice.items)
        } else {
            // Generate new number
            const prefix = type === "invoice" ? settings.invoice_prefix : settings.offer_prefix
            const number = `${prefix}${new Date().getFullYear()}-${String(Date.now()).slice(-3)}`
            setFormData((prev) => ({ ...prev, number }))
        }
    }, [invoice, type, settings])

    const addItem = () => {
        const newItem: InvoiceItem = {
            id: Date.now(),
            description: "",
            quantity: 1,
            unit_price: 0,
            total: 0,
        }
        setItems([...items, newItem])
    }

    const removeItem = (id: number) => {
        setItems(items.filter((item) => item.id !== id))
    }

    const updateItem = (id: number, field: keyof InvoiceItem, value: string | number) => {
        setItems(
            items.map((item) => {
                if (item.id === id) {
                    const updatedItem = { ...item, [field]: value }
                    if (field === "quantity" || field === "unit_price") {
                        updatedItem.total = updatedItem.quantity * updatedItem.unit_price
                    }
                    return updatedItem
                }
                return item
            }),
        )
    }

    const calculateTotals = () => {
        const subtotal = items.reduce((sum, item) => sum + item.total, 0)
        const tax_amount = subtotal * settings.tax_rate
        const total = subtotal + tax_amount
        return { subtotal, tax_amount, total }
    }

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()

        const { subtotal, tax_amount, total } = calculateTotals()
        const selectedCustomer = customers.find((c) => c.id.toString() === formData.customer_id)

        const invoiceData: Partial<Invoice> = {
            number: formData.number,
            type,
            customer_id: Number.parseInt(formData.customer_id),
            customer_name: selectedCustomer?.name || "",
            issue_date: formData.issue_date,
            due_date: formData.due_date,
            items,
            subtotal,
            tax_amount,
            total,
            notes: formData.notes,
        }

        onSave(invoiceData)
    }

    const { subtotal, tax_amount, total } = calculateTotals()

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                    <Label htmlFor="number">{type === "invoice" ? "Invoice" : "Offer"} Number</Label>
                    <Input
                        id="number"
                        value={formData.number}
                        onChange={(e) => setFormData({ ...formData, number: e.target.value })}
                        required
                    />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="customer">Customer</Label>
                    <Select
                        value={formData.customer_id}
                        onValueChange={(value) => setFormData({ ...formData, customer_id: value })}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Select customer" />
                        </SelectTrigger>
                        <SelectContent>
                            {customers.map((customer) => (
                                <SelectItem key={customer.id} value={customer.id.toString()}>
                                    {customer.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="space-y-2">
                    <Label htmlFor="issue_date">Issue Date</Label>
                    <Input
                        id="issue_date"
                        type="date"
                        value={formData.issue_date}
                        onChange={(e) => setFormData({ ...formData, issue_date: e.target.value })}
                        required
                    />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="due_date">{type === "invoice" ? "Due Date" : "Valid Until"}</Label>
                    <Input
                        id="due_date"
                        type="date"
                        value={formData.due_date}
                        onChange={(e) => setFormData({ ...formData, due_date: e.target.value })}
                        required
                    />
                </div>
            </div>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between">
                    <CardTitle>Items</CardTitle>
                    <Button type="button" onClick={addItem} size="sm">
                        <Plus className="h-4 w-4 mr-2" />
                        Add Item
                    </Button>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Description</TableHead>
                                <TableHead>Quantity</TableHead>
                                <TableHead>Unit Price</TableHead>
                                <TableHead>Total</TableHead>
                                <TableHead>Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {items.map((item) => (
                                <TableRow key={item.id}>
                                    <TableCell>
                                        <Input
                                            value={item.description}
                                            onChange={(e) => updateItem(item.id, "description", e.target.value)}
                                            placeholder="Item description"
                                            required
                                        />
                                    </TableCell>
                                    <TableCell>
                                        <Input
                                            type="number"
                                            min="1"
                                            value={item.quantity}
                                            onChange={(e) => updateItem(item.id, "quantity", Number.parseFloat(e.target.value) || 0)}
                                            required
                                        />
                                    </TableCell>
                                    <TableCell>
                                        <Input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            value={item.unit_price}
                                            onChange={(e) => updateItem(item.id, "unit_price", Number.parseFloat(e.target.value) || 0)}
                                            required
                                        />
                                    </TableCell>
                                    <TableCell>
                                        {settings.currency} {item.total.toFixed(2)}
                                    </TableCell>
                                    <TableCell>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => removeItem(item.id)}
                                            disabled={items.length === 1}
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

                    <div className="mt-4 space-y-2 text-right">
                        <div className="flex justify-between">
                            <span>Subtotal:</span>
                            <span>
                {settings.currency} {subtotal.toFixed(2)}
              </span>
                        </div>
                        <div className="flex justify-between">
                            <span>Tax ({(settings.tax_rate * 100).toFixed(0)}%):</span>
                            <span>
                {settings.currency} {tax_amount.toFixed(2)}
              </span>
                        </div>
                        <div className="flex justify-between font-bold text-lg">
                            <span>Total:</span>
                            <span>
                {settings.currency} {total.toFixed(2)}
              </span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div className="space-y-2">
                <Label htmlFor="notes">Notes</Label>
                <Textarea
                    id="notes"
                    value={formData.notes}
                    onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                    placeholder="Additional notes..."
                />
            </div>

            <div className="flex justify-end space-x-2">
                <Button type="button" variant="outline" onClick={onCancel}>
                    Cancel
                </Button>
                <Button type="submit">
                    {invoice ? "Update" : "Create"} {type === "invoice" ? "Invoice" : "Offer"}
                </Button>
            </div>
        </form>
    )
}
