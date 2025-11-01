"use client"

import { Head, useForm } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Plus, Trash2 } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem } from "@/types"
import { route } from "ziggy-js"

interface PaymentMethodsPageProps {
    payment_methods: string[]
    settings: {
        default_payment_method: string
        payment_terms: number
    }
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Einstellungen", href: "/settings" },
    { title: "Zahlungsmethoden" },
]

export default function PaymentMethodsSettings({ payment_methods, settings }: PaymentMethodsPageProps) {
    const { data, setData, post, processing } = useForm({
        payment_methods: payment_methods || [],
        default_payment_method: settings.default_payment_method || "",
        payment_terms: settings.payment_terms || 14,
    })

    const addPaymentMethod = () => {
        const newMethod = prompt("Geben Sie den Namen der Zahlungsmethode ein:")
        if (newMethod && newMethod.trim()) {
            setData("payment_methods", [...data.payment_methods, newMethod.trim()])
        }
    }

    const removePaymentMethod = (index: number) => {
        const updated = data.payment_methods.filter((_, i) => i !== index)
        setData("payment_methods", updated)
    }

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        // TODO: Implement update endpoint
        console.log("Update payment methods:", data)
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Zahlungsmethoden" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-3xl font-bold text-gray-900">Zahlungsmethoden</h1>
                    <p className="text-gray-600">Verwalten Sie verfügbare Zahlungsmethoden</p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Verfügbare Zahlungsmethoden</CardTitle>
                            <CardDescription>Fügen Sie Zahlungsmethoden hinzu, die in Rechnungen verwendet werden können</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                {data.payment_methods.map((method, index) => (
                                    <div key={index} className="flex items-center gap-2">
                                        <Input value={method} readOnly className="flex-1" />
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => removePaymentMethod(index)}
                                            className="text-red-600 hover:text-red-700"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </div>
                                ))}
                            </div>

                            <Button type="button" variant="outline" onClick={addPaymentMethod}>
                                <Plus className="mr-2 h-4 w-4" />
                                Zahlungsmethode hinzufügen
                            </Button>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Standard-Einstellungen</CardTitle>
                            <CardDescription>Legen Sie Standardwerte für Zahlungen fest</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="default_payment_method">Standard-Zahlungsmethode</Label>
                                <Select
                                    value={data.default_payment_method}
                                    onValueChange={(value) => setData("default_payment_method", value)}
                                >
                                    <SelectTrigger id="default_payment_method">
                                        <SelectValue placeholder="Auswählen" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {data.payment_methods.map((method) => (
                                            <SelectItem key={method} value={method}>
                                                {method}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="payment_terms">Zahlungsziel (Tage)</Label>
                                <Input
                                    id="payment_terms"
                                    type="number"
                                    min="1"
                                    max="365"
                                    value={data.payment_terms}
                                    onChange={(e) => setData("payment_terms", parseInt(e.target.value) || 14)}
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing}>
                            {processing ? "Speichert..." : "Speichern"}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    )
}

