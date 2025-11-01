"use client"

import { Card, CardContent } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Download, Send } from "lucide-react"

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

interface InvoicePreviewProps {
    invoice: Invoice
    settings: {
        currency: string
        tax_rate: number
        invoice_prefix: string
        offer_prefix: string
    }
}

export default function InvoicePreview({ invoice, settings }: InvoicePreviewProps) {
    const handleDownload = () => {
        // Implementation for PDF download
        console.log("Download PDF")
    }

    const handleSend = () => {
        // Implementation for sending invoice
        console.log("Send invoice")
    }

    return (
        <div className="space-y-4">
            <div className="flex justify-end space-x-2 mb-4">
                <Button variant="outline" onClick={handleDownload}>
                    <Download className="h-4 w-4 mr-2" />
                    Download PDF
                </Button>
                <Button onClick={handleSend}>
                    <Send className="h-4 w-4 mr-2" />
                    Send {invoice.type === "invoice" ? "Invoice" : "Offer"}
                </Button>
            </div>

            <Card className="max-w-4xl mx-auto">
                <CardContent className="p-8">
                    {/* Header */}
                    <div className="flex justify-between items-start mb-8">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900 mb-2">
                                {invoice.type === "invoice" ? "INVOICE" : "OFFER"}
                            </h1>
                            <p className="text-lg font-semibold text-gray-700">{invoice.number}</p>
                        </div>
                        <div className="text-right">
                            <h2 className="text-xl font-bold text-gray-900 mb-2">Your Company Name</h2>
                            <p className="text-gray-600">123 Business Street</p>
                            <p className="text-gray-600">City, State 12345</p>
                            <p className="text-gray-600">contact@company.com</p>
                            <p className="text-gray-600">+1 (555) 123-4567</p>
                        </div>
                    </div>

                    {/* Customer and Date Info */}
                    <div className="grid grid-cols-2 gap-8 mb-8">
                        <div>
                            <h3 className="text-lg font-semibold text-gray-900 mb-2">Bill To:</h3>
                            <p className="font-semibold">{invoice.customer_name}</p>
                            <p className="text-gray-600">Customer Address</p>
                            <p className="text-gray-600">City, State 12345</p>
                        </div>
                        <div className="text-right">
                            <div className="mb-4">
                                <p className="text-gray-600">Issue Date:</p>
                                <p className="font-semibold">{invoice.issue_date}</p>
                            </div>
                            <div>
                                <p className="text-gray-600">{invoice.type === "invoice" ? "Due Date:" : "Valid Until:"}</p>
                                <p className="font-semibold">{invoice.due_date}</p>
                            </div>
                        </div>
                    </div>

                    {/* Items Table */}
                    <div className="mb-8">
                        <table className="w-full border-collapse border border-gray-300">
                            <thead>
                            <tr className="bg-gray-50">
                                <th className="border border-gray-300 px-4 py-2 text-left">Description</th>
                                <th className="border border-gray-300 px-4 py-2 text-center">Qty</th>
                                <th className="border border-gray-300 px-4 py-2 text-right">Unit Price</th>
                                <th className="border border-gray-300 px-4 py-2 text-right">Total</th>
                            </tr>
                            </thead>
                            <tbody>
                            {invoice.items.map((item) => (
                                <tr key={item.id}>
                                    <td className="border border-gray-300 px-4 py-2">{item.description}</td>
                                    <td className="border border-gray-300 px-4 py-2 text-center">{item.quantity}</td>
                                    <td className="border border-gray-300 px-4 py-2 text-right">
                                        {settings.currency} {item.unit_price.toFixed(2)}
                                    </td>
                                    <td className="border border-gray-300 px-4 py-2 text-right">
                                        {settings.currency} {item.total.toFixed(2)}
                                    </td>
                                </tr>
                            ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Totals */}
                    <div className="flex justify-end mb-8">
                        <div className="w-64">
                            <div className="flex justify-between py-2">
                                <span>Subtotal:</span>
                                <span>
                  {settings.currency} {invoice.subtotal.toFixed(2)}
                </span>
                            </div>
                            <div className="flex justify-between py-2">
                                <span>Tax ({(settings.tax_rate * 100).toFixed(0)}%):</span>
                                <span>
                  {settings.currency} {invoice.tax_amount.toFixed(2)}
                </span>
                            </div>
                            <div className="flex justify-between py-2 border-t border-gray-300 font-bold text-lg">
                                <span>Total:</span>
                                <span>
                  {settings.currency} {invoice.total.toFixed(2)}
                </span>
                            </div>
                        </div>
                    </div>

                    {/* Notes */}
                    {invoice.notes && (
                        <div className="mb-8">
                            <h3 className="text-lg font-semibold text-gray-900 mb-2">Notes:</h3>
                            <p className="text-gray-600">{invoice.notes}</p>
                        </div>
                    )}

                    {/* Footer */}
                    <div className="text-center text-gray-500 text-sm border-t border-gray-300 pt-4">
                        <p>Thank you for your business!</p>
                    </div>
                </CardContent>
            </Card>
        </div>
    )
}
