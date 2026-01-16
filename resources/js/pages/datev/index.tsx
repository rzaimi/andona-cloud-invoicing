"use client"

import { Head, useForm, usePage } from "@inertiajs/react"
import AppLayout from "@/layouts/app-layout"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { 
    Download, 
    FileText, 
    Users, 
    CreditCard, 
    ReceiptEuro, 
    Receipt,
    Calendar,
    Info
} from "lucide-react"
import { route } from "@/plugins/ziggy"
import { useState } from "react"

interface Props {
    company_id: string
}

export default function DatevIndex({ company_id }: Props) {
    const [loading, setLoading] = useState<string | null>(null)
    const page = usePage()
    
    // Get CSRF token from Inertia page props
    const csrfToken = (page.props as any).csrf_token || 
                      document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                      ''

    const transactionsForm = useForm({
        date_from: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
        date_to: new Date().toISOString().split('T')[0],
        format: 'csv',
    })

    const paymentsForm = useForm({
        date_from: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
        date_to: new Date().toISOString().split('T')[0],
        format: 'csv',
    })

    const expensesForm = useForm({
        date_from: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
        date_to: new Date().toISOString().split('T')[0],
        format: 'csv',
    })

    const vatForm = useForm({
        date_from: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
        date_to: new Date().toISOString().split('T')[0],
        format: 'csv',
    })

    const customersForm = useForm({
        format: 'csv',
    })

    const handleExport = (form: any, routeName: string, type: string) => {
        setLoading(type)
        
        // Create a form and submit it to trigger file download
        const formData = new FormData()
        Object.keys(form.data).forEach(key => {
            formData.append(key, form.data[key])
        })
        
        // Add CSRF token to form data (Laravel also accepts it in the form body)
        formData.append('_token', csrfToken)
        
        fetch(route(routeName), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
            credentials: 'same-origin', // Include cookies for session
        })
        .then(async response => {
            // Check if response is ok
            if (!response.ok) {
                // Try to get error message from response
                let errorMessage = `HTTP ${response.status}: ${response.statusText}`
                try {
                    const text = await response.text()
                    try {
                        const errorData = JSON.parse(text)
                        if (errorData.message) {
                            errorMessage = errorData.message
                        } else if (errorData.errors) {
                            errorMessage = Object.values(errorData.errors).flat().join(', ')
                        }
                    } catch (e) {
                        // Not JSON, use text as error message
                        if (text) {
                            errorMessage = text.substring(0, 200) // Limit length
                        }
                    }
                } catch (e) {
                    // Could not read response
                    console.error('Could not read error response:', e)
                }
                throw new Error(errorMessage)
            }
            
            // Check content type to ensure it's a CSV file
            const contentType = response.headers.get('Content-Type')
            if (!contentType || !contentType.includes('csv') && !contentType.includes('text')) {
                console.warn('Unexpected content type:', contentType)
            }
            
            // Get filename from Content-Disposition header
            const contentDisposition = response.headers.get('Content-Disposition')
            const filename = contentDisposition 
                ? contentDisposition.split('filename=')[1]?.replace(/"/g, '') 
                : `datev_export_${Date.now()}.csv`
            
            return response.blob().then(blob => {
                if (blob.size === 0) {
                    throw new Error('Die exportierte Datei ist leer.')
                }
                return { blob, filename }
            })
        })
        .then(({ blob, filename }) => {
            const url = window.URL.createObjectURL(blob)
            const a = document.createElement('a')
            a.href = url
            a.download = filename
            document.body.appendChild(a)
            a.click()
            window.URL.revokeObjectURL(url)
            document.body.removeChild(a)
        })
        .catch(error => {
            console.error('Export error:', error)
            console.error('Error details:', {
                message: error.message,
                stack: error.stack,
            })
            
            // Display user-friendly error message
            const errorMessage = error.message || 'Unbekannter Fehler beim Exportieren'
            alert(`Fehler beim Exportieren: ${errorMessage}\n\nBitte überprüfen Sie die Browser-Konsole (F12) für weitere Details.`)
        })
        .finally(() => {
            setLoading(null)
        })
    }

    return (
        <AppLayout breadcrumbs={[{ title: "Dashboard", href: "/dashboard" }, { title: "DATEV Export" }]}>
            <Head title="DATEV Export" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-1xl font-bold tracking-tight">DATEV Export</h1>
                    <p className="text-muted-foreground mt-2">
                        Exportieren Sie Ihre Daten im DATEV-Format für die Buchhaltung
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <div className="flex items-center gap-2">
                            <Info className="h-5 w-5" />
                            <CardTitle>Informationen</CardTitle>
                        </div>
                        <CardDescription>
                            DATEV-Exporte werden im CSV-Format mit ISO-8859-1 Kodierung erstellt. 
                            Die Dateien können direkt in DATEV importiert werden.
                        </CardDescription>
                    </CardHeader>
                </Card>

                <div className="grid gap-6 md:grid-cols-2">
                    {/* Transactions Export */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <FileText className="h-5 w-5" />
                                <CardTitle>Umsätze (Rechnungen)</CardTitle>
                            </div>
                            <CardDescription>
                                Exportieren Sie Rechnungen als DATEV-Umsätze
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="transactions_date_from">Von Datum</Label>
                                <Input
                                    id="transactions_date_from"
                                    type="date"
                                    value={transactionsForm.data.date_from}
                                    onChange={(e) => transactionsForm.setData('date_from', e.target.value)}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="transactions_date_to">Bis Datum</Label>
                                <Input
                                    id="transactions_date_to"
                                    type="date"
                                    value={transactionsForm.data.date_to}
                                    onChange={(e) => transactionsForm.setData('date_to', e.target.value)}
                                />
                            </div>
                            <Button
                                onClick={() => handleExport(transactionsForm, 'datev.export.transactions', 'transactions')}
                                disabled={loading === 'transactions'}
                                className="w-full"
                            >
                                {loading === 'transactions' ? (
                                    <>
                                        <Download className="mr-2 h-4 w-4 animate-pulse" />
                                        Wird exportiert...
                                    </>
                                ) : (
                                    <>
                                        <Download className="mr-2 h-4 w-4" />
                                        Umsätze exportieren
                                    </>
                                )}
                            </Button>
                        </CardContent>
                    </Card>

                    {/* Customers Export */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <Users className="h-5 w-5" />
                                <CardTitle>Debitoren (Kunden)</CardTitle>
                            </div>
                            <CardDescription>
                                Exportieren Sie Kunden als DATEV-Debitoren
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="text-sm text-muted-foreground">
                                Alle aktiven Kunden werden exportiert
                            </div>
                            <Button
                                onClick={() => handleExport(customersForm, 'datev.export.customers', 'customers')}
                                disabled={loading === 'customers'}
                                className="w-full"
                            >
                                {loading === 'customers' ? (
                                    <>
                                        <Download className="mr-2 h-4 w-4 animate-pulse" />
                                        Wird exportiert...
                                    </>
                                ) : (
                                    <>
                                        <Download className="mr-2 h-4 w-4" />
                                        Debitoren exportieren
                                    </>
                                )}
                            </Button>
                        </CardContent>
                    </Card>

                    {/* Payments Export */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <CreditCard className="h-5 w-5" />
                                <CardTitle>Zahlungen</CardTitle>
                            </div>
                            <CardDescription>
                                Exportieren Sie Zahlungen im DATEV-Format
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="payments_date_from">Von Datum</Label>
                                <Input
                                    id="payments_date_from"
                                    type="date"
                                    value={paymentsForm.data.date_from}
                                    onChange={(e) => paymentsForm.setData('date_from', e.target.value)}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="payments_date_to">Bis Datum</Label>
                                <Input
                                    id="payments_date_to"
                                    type="date"
                                    value={paymentsForm.data.date_to}
                                    onChange={(e) => paymentsForm.setData('date_to', e.target.value)}
                                />
                            </div>
                            <Button
                                onClick={() => handleExport(paymentsForm, 'datev.export.payments', 'payments')}
                                disabled={loading === 'payments'}
                                className="w-full"
                            >
                                {loading === 'payments' ? (
                                    <>
                                        <Download className="mr-2 h-4 w-4 animate-pulse" />
                                        Wird exportiert...
                                    </>
                                ) : (
                                    <>
                                        <Download className="mr-2 h-4 w-4" />
                                        Zahlungen exportieren
                                    </>
                                )}
                            </Button>
                        </CardContent>
                    </Card>

                    {/* Expenses Export */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <ReceiptEuro className="h-5 w-5" />
                                <CardTitle>Ausgaben</CardTitle>
                            </div>
                            <CardDescription>
                                Exportieren Sie Ausgaben im DATEV-Format
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="expenses_date_from">Von Datum</Label>
                                <Input
                                    id="expenses_date_from"
                                    type="date"
                                    value={expensesForm.data.date_from}
                                    onChange={(e) => expensesForm.setData('date_from', e.target.value)}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="expenses_date_to">Bis Datum</Label>
                                <Input
                                    id="expenses_date_to"
                                    type="date"
                                    value={expensesForm.data.date_to}
                                    onChange={(e) => expensesForm.setData('date_to', e.target.value)}
                                />
                            </div>
                            <Button
                                onClick={() => handleExport(expensesForm, 'datev.export.expenses', 'expenses')}
                                disabled={loading === 'expenses'}
                                className="w-full"
                            >
                                {loading === 'expenses' ? (
                                    <>
                                        <Download className="mr-2 h-4 w-4 animate-pulse" />
                                        Wird exportiert...
                                    </>
                                ) : (
                                    <>
                                        <Download className="mr-2 h-4 w-4" />
                                        Ausgaben exportieren
                                    </>
                                )}
                            </Button>
                        </CardContent>
                    </Card>

                    {/* VAT Export */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <Receipt className="h-5 w-5" />
                                <CardTitle>Umsatzsteuer</CardTitle>
                            </div>
                            <CardDescription>
                                Exportieren Sie Umsatzsteuer-Daten im DATEV-Format
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="vat_date_from">Von Datum</Label>
                                <Input
                                    id="vat_date_from"
                                    type="date"
                                    value={vatForm.data.date_from}
                                    onChange={(e) => vatForm.setData('date_from', e.target.value)}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="vat_date_to">Bis Datum</Label>
                                <Input
                                    id="vat_date_to"
                                    type="date"
                                    value={vatForm.data.date_to}
                                    onChange={(e) => vatForm.setData('date_to', e.target.value)}
                                />
                            </div>
                            <Button
                                onClick={() => handleExport(vatForm, 'datev.export.vat', 'vat')}
                                disabled={loading === 'vat'}
                                className="w-full"
                            >
                                {loading === 'vat' ? (
                                    <>
                                        <Download className="mr-2 h-4 w-4 animate-pulse" />
                                        Wird exportiert...
                                    </>
                                ) : (
                                    <>
                                        <Download className="mr-2 h-4 w-4" />
                                        Umsatzsteuer exportieren
                                    </>
                                )}
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    )
}

