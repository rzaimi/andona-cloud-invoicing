"use client"

import { useState } from "react"
import { useForm } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { CheckCircle2, Plus, X, Info } from "lucide-react"
import { route } from "ziggy-js"

interface PaymentMethodsSettingsTabProps {
    paymentMethodSettings: any
}

export default function PaymentMethodsSettingsTab({ paymentMethodSettings }: PaymentMethodsSettingsTabProps) {
    const [newMethod, setNewMethod] = useState("")
    const { data, setData, post, processing, errors, recentlySuccessful } = useForm({
        payment_methods: paymentMethodSettings?.payment_methods || ['Überweisung', 'SEPA-Lastschrift', 'PayPal'],
        default_payment_method: paymentMethodSettings?.default_payment_method || 'Überweisung',
    })

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        post(route("settings.payment-methods.update"))
    }

    const addPaymentMethod = () => {
        if (newMethod.trim() && !data.payment_methods.includes(newMethod.trim())) {
            setData('payment_methods', [...data.payment_methods, newMethod.trim()])
            setNewMethod("")
        }
    }

    const removePaymentMethod = (method: string) => {
        if (data.payment_methods.length > 1) {
            setData('payment_methods', data.payment_methods.filter((m: string) => m !== method))
            // If removed method was the default, set first method as default
            if (data.default_payment_method === method) {
                setData('default_payment_method', data.payment_methods.filter((m: string) => m !== method)[0])
            }
        }
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            {recentlySuccessful && (
                <Alert className="border-green-500 bg-green-50">
                    <CheckCircle2 className="h-4 w-4 text-green-600" />
                    <AlertDescription className="text-green-600">
                        Zahlungsmethoden wurden erfolgreich aktualisiert.
                    </AlertDescription>
                </Alert>
            )}

            <Card>
                <CardHeader>
                    <CardTitle>Zahlungsmethoden</CardTitle>
                    <CardDescription>
                        Verwalten Sie verfügbare Zahlungsmethoden für Rechnungen und Angebote
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                    <Alert>
                        <Info className="h-4 w-4" />
                        <AlertDescription>
                            Diese Zahlungsmethoden stehen bei der Erstellung von Rechnungen und Angeboten zur Verfügung.
                            Die Standard-Zahlungsmethode wird automatisch vorausgewählt.
                        </AlertDescription>
                    </Alert>

                    <div className="space-y-2">
                        <Label htmlFor="default_payment_method">Standard-Zahlungsmethode *</Label>
                        <Select 
                            value={data.default_payment_method} 
                            onValueChange={(value) => setData("default_payment_method", value)}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Standard-Zahlungsmethode wählen" />
                            </SelectTrigger>
                            <SelectContent>
                                {data.payment_methods.map((method: string) => (
                                    <SelectItem key={method} value={method}>
                                        {method}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.default_payment_method && (
                            <p className="text-sm text-red-600">{errors.default_payment_method}</p>
                        )}
                        <p className="text-sm text-muted-foreground">
                            Diese Zahlungsmethode wird standardmäßig bei neuen Rechnungen und Angeboten verwendet.
                        </p>
                    </div>

                    <div className="space-y-2">
                        <Label>Verfügbare Zahlungsmethoden</Label>
                        <div className="space-y-2">
                            {data.payment_methods.map((method: string, index: number) => (
                                <div key={index} className="flex items-center justify-between p-3 border rounded-lg">
                                    <span className="font-medium">{method}</span>
                                    <div className="flex items-center gap-2">
                                        {data.default_payment_method === method && (
                                            <span className="text-xs text-muted-foreground">Standard</span>
                                        )}
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => removePaymentMethod(method)}
                                            disabled={data.payment_methods.length <= 1}
                                            className="h-8 w-8 p-0"
                                        >
                                            <X className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </div>
                        {errors.payment_methods && (
                            <p className="text-sm text-red-600">{errors.payment_methods}</p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="new_method">Neue Zahlungsmethode hinzufügen</Label>
                        <div className="flex gap-2">
                            <Input
                                id="new_method"
                                type="text"
                                placeholder="z.B. Kreditkarte, Barzahlung, etc."
                                value={newMethod}
                                onChange={(e) => setNewMethod(e.target.value)}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter') {
                                        e.preventDefault()
                                        addPaymentMethod()
                                    }
                                }}
                            />
                            <Button
                                type="button"
                                onClick={addPaymentMethod}
                                disabled={!newMethod.trim() || data.payment_methods.includes(newMethod.trim())}
                            >
                                <Plus className="h-4 w-4 mr-2" />
                                Hinzufügen
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div className="flex justify-end">
                <Button type="submit" disabled={processing}>
                    {processing ? "Speichert..." : "Einstellungen speichern"}
                </Button>
            </div>
        </form>
    )
}
