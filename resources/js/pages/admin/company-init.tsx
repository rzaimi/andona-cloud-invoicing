"use client"

import { useState } from "react"
import { Head, router, usePage } from "@inertiajs/react"
import AppLayout from "@/layouts/app-layout"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Label } from "@/components/ui/label"
import { Checkbox } from "@/components/ui/checkbox"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import {
    Building2,
    PlayCircle,
    RefreshCw,
    CheckCircle2,
    AlertCircle,
    Terminal,
    Info,
} from "lucide-react"
import { route } from "@/plugins/ziggy"

interface Company {
    id: string
    name: string
    city: string | null
    status: string
}

interface CompanyType {
    slug: string
    label: string
}

interface Props {
    user: any
    companies: Company[]
    companyTypes: CompanyType[]
}

export default function CompanyInit({ user, companies, companyTypes }: Props) {
    const { props } = usePage<any>()
    const flash = props.flash ?? {}
    const errors = (props.errors ?? {}) as Record<string, string>

    const [companyId, setCompanyId] = useState("")
    const [type, setType] = useState("")
    const [force, setForce] = useState(false)
    const [isRunning, setIsRunning] = useState(false)

    const selectedCompany = companies.find((c) => c.id === companyId)

    function handleRun() {
        if (!companyId || !type) return

        setIsRunning(true)
        router.post(
            route("company-init.run"),
            { company_id: companyId, type, force },
            {
                onFinish: () => setIsRunning(false),
                preserveScroll: true,
            },
        )
    }

    const hasSuccess = flash.init_success === true
    const hasError = !!errors.init_error

    return (
        <AppLayout user={user}>
            <Head title="Firma initialisieren" />

            <div className="space-y-6 max-w-3xl">
                {/* Header */}
                <div>
                    <h1 className="text-xl font-bold tracking-tight dark:text-gray-100">Firma initialisieren</h1>
                    <p className="text-muted-foreground text-sm mt-1">
                        Führt <code className="bg-muted px-1 rounded text-xs">php artisan company:init</code> für eine
                        Firma aus – erstellt branchenspezifische Produkte, Kategorien, Lager und Layouts.
                    </p>
                </div>

                {/* Success output */}
                {hasSuccess && (
                    <Alert className="border-green-500 bg-green-50 dark:bg-green-950/30">
                        <CheckCircle2 className="h-4 w-4 text-green-600" />
                        <AlertTitle className="text-green-700 dark:text-green-400">
                            Erfolgreich ausgeführt
                        </AlertTitle>
                        <AlertDescription className="space-y-2">
                            <div className="flex flex-wrap gap-2 mt-1 text-sm text-green-700 dark:text-green-300">
                                <span>
                                    <strong>Firma:</strong> {flash.init_company}
                                </span>
                                <span>·</span>
                                <span>
                                    <strong>Typ:</strong> {flash.init_type}
                                </span>
                                {flash.init_force && (
                                    <>
                                        <span>·</span>
                                        <Badge variant="destructive" className="text-xs">
                                            --force
                                        </Badge>
                                    </>
                                )}
                            </div>
                            {flash.init_output && (
                                <pre className="mt-3 whitespace-pre-wrap rounded bg-black/10 dark:bg-black/30 p-3 text-xs font-mono text-green-800 dark:text-green-200 leading-relaxed max-h-80 overflow-y-auto">
                                    {flash.init_output}
                                </pre>
                            )}
                        </AlertDescription>
                    </Alert>
                )}

                {/* Error output */}
                {hasError && (
                    <Alert variant="destructive">
                        <AlertCircle className="h-4 w-4" />
                        <AlertTitle>Fehler</AlertTitle>
                        <AlertDescription>{errors.init_error}</AlertDescription>
                    </Alert>
                )}

                {/* Form */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Terminal className="h-5 w-5" />
                            Befehl konfigurieren
                        </CardTitle>
                        <CardDescription>
                            Wählen Sie die Firma und den Branchentyp aus, um die Initialisierung zu starten.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-5">
                        {/* Company selector */}
                        <div className="space-y-2">
                            <Label htmlFor="company-select">
                                Firma <span className="text-destructive">*</span>
                            </Label>
                            <Select value={companyId} onValueChange={setCompanyId}>
                                <SelectTrigger id="company-select">
                                    <SelectValue placeholder="Firma auswählen..." />
                                </SelectTrigger>
                                <SelectContent>
                                    {companies.map((company) => (
                                        <SelectItem key={company.id} value={company.id}>
                                            <span className="flex items-center gap-2">
                                                <Building2 className="h-4 w-4 text-muted-foreground shrink-0" />
                                                <span>
                                                    {company.name}
                                                    {company.city ? (
                                                        <span className="text-muted-foreground ml-1 text-xs">
                                                            — {company.city}
                                                        </span>
                                                    ) : null}
                                                </span>
                                            </span>
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {selectedCompany && (
                                <p className="text-xs text-muted-foreground font-mono break-all">
                                    UUID: {selectedCompany.id}
                                </p>
                            )}
                        </div>

                        {/* Type selector */}
                        <div className="space-y-2">
                            <Label htmlFor="type-select">
                                Branchentyp <span className="text-destructive">*</span>
                            </Label>
                            <Select value={type} onValueChange={setType}>
                                <SelectTrigger id="type-select">
                                    <SelectValue placeholder="Branchentyp auswählen..." />
                                </SelectTrigger>
                                <SelectContent>
                                    {companyTypes.map((ct) => (
                                        <SelectItem key={ct.slug} value={ct.slug}>
                                            <span className="flex items-center gap-2">
                                                <Badge variant="outline" className="text-xs font-mono">
                                                    {ct.slug}
                                                </Badge>
                                                {ct.label}
                                            </span>
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        {/* Force option */}
                        <div className="flex items-start space-x-3 rounded-md border p-4 bg-amber-50 dark:bg-amber-950/20 border-amber-200 dark:border-amber-800">
                            <Checkbox
                                id="force"
                                checked={force}
                                onCheckedChange={(v) => setForce(v === true)}
                                className="mt-0.5"
                            />
                            <div className="space-y-1">
                                <Label htmlFor="force" className="font-semibold cursor-pointer">
                                    --force &nbsp;
                                    <Badge variant="outline" className="text-xs">
                                        Überschreiben
                                    </Badge>
                                </Label>
                                <p className="text-xs text-muted-foreground">
                                    Vorhandene Daten (Produkte, Kategorien, Lager, Layouts) werden überschrieben.
                                    Ohne diese Option werden bestehende Einträge übersprungen.
                                </p>
                            </div>
                        </div>

                        {/* Command preview */}
                        {(companyId || type) && (
                            <div className="rounded-md bg-muted/60 border p-3">
                                <p className="text-xs text-muted-foreground mb-1 flex items-center gap-1">
                                    <Info className="h-3 w-3" /> Auszuführender Befehl:
                                </p>
                                <code className="text-xs font-mono text-foreground break-all">
                                    php artisan company:init{" "}
                                    {companyId ? companyId : "<company_uuid>"}
                                    {type ? ` --type=${type}` : ""}
                                    {force ? " --force" : ""}
                                </code>
                            </div>
                        )}

                        {/* Run button */}
                        <div className="flex justify-end pt-2">
                            <Button
                                onClick={handleRun}
                                disabled={!companyId || !type || isRunning}
                                className="min-w-40"
                            >
                                {isRunning ? (
                                    <>
                                        <RefreshCw className="mr-2 h-4 w-4 animate-spin" />
                                        Wird ausgeführt...
                                    </>
                                ) : (
                                    <>
                                        <PlayCircle className="mr-2 h-4 w-4" />
                                        Befehl ausführen
                                    </>
                                )}
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Info card */}
                <Card className="border-blue-200 dark:border-blue-900 bg-blue-50 dark:bg-blue-950/20">
                    <CardContent className="pt-4 pb-4">
                        <div className="flex gap-3">
                            <Info className="h-4 w-4 text-blue-600 dark:text-blue-400 mt-0.5 shrink-0" />
                            <div className="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                                <p className="font-semibold">Was wird initialisiert?</p>
                                <ul className="list-disc list-inside space-y-0.5 text-xs">
                                    <li>Hauptlager (Warehouse) für die Firma</li>
                                    <li>Branchenspezifische Produktkategorien</li>
                                    <li>Branchenspezifische Produkte &amp; Dienstleistungen</li>
                                    <li>Ausgabenkategorien</li>
                                    <li>Rechnungs- und Angebotslayouts</li>
                                </ul>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    )
}
