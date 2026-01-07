"use client"

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"

interface PaymentMethodsSettingsTabProps {
    paymentMethodSettings: any
}

export default function PaymentMethodsSettingsTab({ paymentMethodSettings }: PaymentMethodsSettingsTabProps) {
    return (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Zahlungsmethoden</CardTitle>
                    <CardDescription>
                        Verwalten Sie verfügbare Zahlungsmethoden
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <p className="text-sm text-muted-foreground">
                        Diese Funktion wird in Kürze verfügbar sein.
                    </p>
                </CardContent>
            </Card>
        </div>
    )
}



