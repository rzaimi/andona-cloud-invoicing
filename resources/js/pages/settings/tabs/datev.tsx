"use client"

import { useForm } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { Info } from "lucide-react"
import { route } from "ziggy-js"

interface DatevSettingsTabProps {
    datevSettings: any
}

export default function DatevSettingsTab({ datevSettings }: DatevSettingsTabProps) {
    const { data, setData, post, processing, errors } = useForm({
        datev_revenue_account: datevSettings?.datev_revenue_account || '8400',
        datev_receivables_account: datevSettings?.datev_receivables_account || '1200',
        datev_bank_account: datevSettings?.datev_bank_account || '1800',
        datev_expenses_account: datevSettings?.datev_expenses_account || '6000',
        datev_vat_account: datevSettings?.datev_vat_account || '1776',
        datev_customer_account_prefix: datevSettings?.datev_customer_account_prefix || '1000',
    })

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        post(route("settings.datev.update"))
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>DATEV Kontenkonfiguration</CardTitle>
                    <CardDescription>
                        Konfigurieren Sie die DATEV-Kontonummern für den Export. Diese werden in den DATEV-Exporten verwendet.
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                    <Alert>
                        <Info className="h-4 w-4" />
                        <AlertDescription>
                            Die Kontonummern müssen mit Ihrem DATEV-Kontenplan übereinstimmen. 
                            Standardwerte sind typische deutsche Kontonummern, können aber angepasst werden.
                        </AlertDescription>
                    </Alert>

                    <div className="grid gap-6 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="datev_revenue_account">Umsatzerlöse (Konto)</Label>
                            <Input
                                id="datev_revenue_account"
                                value={data.datev_revenue_account}
                                onChange={(e) => setData("datev_revenue_account", e.target.value)}
                                placeholder="8400"
                            />
                            <p className="text-sm text-muted-foreground">
                                Kontonummer für Umsatzerlöse (z.B. 8400)
                            </p>
                            {errors.datev_revenue_account && (
                                <p className="text-sm text-red-600">{errors.datev_revenue_account}</p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="datev_receivables_account">Forderungen (Konto)</Label>
                            <Input
                                id="datev_receivables_account"
                                value={data.datev_receivables_account}
                                onChange={(e) => setData("datev_receivables_account", e.target.value)}
                                placeholder="1200"
                            />
                            <p className="text-sm text-muted-foreground">
                                Kontonummer für Forderungen aus Lieferungen und Leistungen (z.B. 1200)
                            </p>
                            {errors.datev_receivables_account && (
                                <p className="text-sm text-red-600">{errors.datev_receivables_account}</p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="datev_bank_account">Bank (Konto)</Label>
                            <Input
                                id="datev_bank_account"
                                value={data.datev_bank_account}
                                onChange={(e) => setData("datev_bank_account", e.target.value)}
                                placeholder="1800"
                            />
                            <p className="text-sm text-muted-foreground">
                                Kontonummer für Bankkonten (z.B. 1800)
                            </p>
                            {errors.datev_bank_account && (
                                <p className="text-sm text-red-600">{errors.datev_bank_account}</p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="datev_expenses_account">Aufwendungen (Konto)</Label>
                            <Input
                                id="datev_expenses_account"
                                value={data.datev_expenses_account}
                                onChange={(e) => setData("datev_expenses_account", e.target.value)}
                                placeholder="6000"
                            />
                            <p className="text-sm text-muted-foreground">
                                Kontonummer für Aufwendungen (z.B. 6000)
                            </p>
                            {errors.datev_expenses_account && (
                                <p className="text-sm text-red-600">{errors.datev_expenses_account}</p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="datev_vat_account">Umsatzsteuer (Konto)</Label>
                            <Input
                                id="datev_vat_account"
                                value={data.datev_vat_account}
                                onChange={(e) => setData("datev_vat_account", e.target.value)}
                                placeholder="1776"
                            />
                            <p className="text-sm text-muted-foreground">
                                Kontonummer für Umsatzsteuer (z.B. 1776)
                            </p>
                            {errors.datev_vat_account && (
                                <p className="text-sm text-red-600">{errors.datev_vat_account}</p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="datev_customer_account_prefix">Debitoren-Präfix</Label>
                            <Input
                                id="datev_customer_account_prefix"
                                value={data.datev_customer_account_prefix}
                                onChange={(e) => setData("datev_customer_account_prefix", e.target.value)}
                                placeholder="1000"
                            />
                            <p className="text-sm text-muted-foreground">
                                Präfix für automatisch generierte Debitoren-Kontonummern (z.B. 1000)
                            </p>
                            {errors.datev_customer_account_prefix && (
                                <p className="text-sm text-red-600">{errors.datev_customer_account_prefix}</p>
                            )}
                        </div>
                    </div>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Wird gespeichert...' : 'Einstellungen speichern'}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </form>
    )
}



