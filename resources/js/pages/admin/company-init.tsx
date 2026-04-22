"use client"

import { useState, useRef } from "react"
import { Head, router, usePage } from "@inertiajs/react"
import AppLayout from "@/layouts/app-layout"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { Label } from "@/components/ui/label"
import { Checkbox } from "@/components/ui/checkbox"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Progress } from "@/components/ui/progress"
import {
    Building2,
    PlayCircle,
    RefreshCw,
    CheckCircle2,
    AlertCircle,
    Terminal,
    Info,
    Warehouse,
    Tag,
    Package,
    Receipt,
    FileText,
    Loader2,
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

const STEPS = [
    { key: "warehouse",         label: "Hauptlager wird erstellt",              icon: Warehouse },
    { key: "categories",        label: "Produktkategorien werden erstellt",      icon: Tag },
    { key: "products",          label: "Produkte werden erstellt",               icon: Package },
    { key: "expense_categories",label: "Ausgabenkategorien werden erstellt",     icon: Receipt },
    { key: "invoice_layouts",   label: "Rechnungslayouts werden erstellt",       icon: FileText },
    { key: "offer_layouts",     label: "Angebotslayouts werden erstellt",        icon: FileText },
]

type ResultState =
    | { status: "success"; company: string; type: string; force: boolean; output: string }
    | { status: "error"; message: string }

export default function CompanyInit({ user, companies, companyTypes }: Props) {
    const { props } = usePage<any>()
    const flash  = props.flash  ?? {}
    const errors = (props.errors ?? {}) as Record<string, string>

    const [companyId, setCompanyId]     = useState("")
    const [type, setType]               = useState("")
    const [force, setForce]             = useState(false)
    const [isAnimating, setIsAnimating] = useState(false)
    const [progress, setProgress]       = useState(0)
    const [currentStep, setCurrentStep] = useState(-1)

    // Persists the last result so it stays on screen until next run
    const [result, setResult] = useState<ResultState | null>(null)

    const STEP_MS = 900

    const intervalRef   = useRef<ReturnType<typeof setInterval> | null>(null)
    const serverDoneRef = useRef(false)

    // Always-current refs so interval/onFinish callbacks never read stale closures
    const flashRef  = useRef(flash)
    const errorsRef = useRef(errors)
    flashRef.current  = flash
    errorsRef.current = errors

    const selectedCompany = companies.find((c) => c.id === companyId)

    function captureResult() {
        const f = flashRef.current
        const e = errorsRef.current
        if (f.init_success === true) {
            setResult({
                status:  "success",
                company: f.init_company ?? "",
                type:    f.init_type    ?? "",
                force:   !!f.init_force,
                output:  f.init_output  ?? "",
            })
        } else if (e.init_error) {
            setResult({ status: "error", message: e.init_error })
        }
    }

    function startAnimation() {
        serverDoneRef.current = false
        setResult(null)
        setIsAnimating(true)
        setProgress(5)
        setCurrentStep(0)

        let step = 0
        intervalRef.current = setInterval(() => {
            step++
            if (step < STEPS.length) {
                setCurrentStep(step)
                setProgress(Math.round(5 + (step / STEPS.length) * 85))
            } else {
                setProgress(95)
                if (intervalRef.current) clearInterval(intervalRef.current)
                intervalRef.current = null

                if (serverDoneRef.current) finishAnimation()
            }
        }, STEP_MS)
    }

    function finishAnimation() {
        setProgress(100)
        setCurrentStep(STEPS.length)
        captureResult()
        setIsAnimating(false)
    }

    function handleRun() {
        if (!companyId || !type) return
        startAnimation()
        router.post(
            route("company-init.run"),
            { company_id: companyId, type, force },
            {
                onFinish: () => {
                    serverDoneRef.current = true
                    if (!intervalRef.current) finishAnimation()
                },
                preserveScroll: true,
            },
        )
    }

    return (
        <AppLayout user={user}>
            <Head title="Firma initialisieren" />

            <div className="space-y-6 max-w-3xl">
                {/* Header */}
                <div>
                    <h1 className="text-xl font-bold text-gray-900 dark:text-gray-100">Firma initialisieren</h1>
                    <p className="text-muted-foreground text-sm mt-1">
                        Führt <code className="bg-muted px-1 rounded text-xs">php artisan company:init</code> für eine
                        Firma aus – erstellt branchenspezifische Produkte, Kategorien, Lager und Layouts.
                    </p>
                </div>

                {/* ── Unified progress + result card ── */}
                {(isAnimating || result !== null) && (() => {
                    const isSuccess = !isAnimating && result?.status === "success"
                    const isError   = !isAnimating && result?.status === "error"
                    return (
                        <Card className={
                            isError   ? "border-red-400 dark:border-red-800 bg-red-50 dark:bg-red-950/30" :
                            isSuccess ? "border-green-400 dark:border-green-800 bg-green-50 dark:bg-green-950/30" :
                                        "border-blue-300 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/30"
                        }>
                            <CardContent className="pt-5 pb-5 space-y-4">

                                {/* Header row */}
                                <div className="flex items-center gap-3">
                                    {isAnimating ? (
                                        <Loader2 className="h-5 w-5 text-blue-600 animate-spin shrink-0" />
                                    ) : isSuccess ? (
                                        <CheckCircle2 className="h-5 w-5 text-green-600 shrink-0" />
                                    ) : (
                                        <AlertCircle className="h-5 w-5 text-red-600 shrink-0" />
                                    )}
                                    <div className="flex-1">
                                        {isAnimating ? (
                                            <>
                                                <p className="text-sm font-semibold text-blue-800 dark:text-blue-300">
                                                    Initialisierung läuft…
                                                </p>
                                                <p className="text-xs text-blue-600 dark:text-blue-400 mt-0.5">
                                                    Bitte warten, der Befehl wird auf dem Server ausgeführt.
                                                </p>
                                            </>
                                        ) : isSuccess && result.status === "success" ? (
                                            <>
                                                <p className="text-sm font-semibold text-green-800 dark:text-green-300">
                                                    Erfolgreich initialisiert
                                                </p>
                                                <div className="flex flex-wrap gap-x-3 gap-y-1 text-xs text-green-700 dark:text-green-400 mt-0.5">
                                                    <span><strong>Firma:</strong> {result.company}</span>
                                                    <span>·</span>
                                                    <span><strong>Typ:</strong> {result.type}</span>
                                                    {result.force && (
                                                        <Badge variant="destructive" className="text-xs">--force</Badge>
                                                    )}
                                                </div>
                                            </>
                                        ) : (
                                            <p className="text-sm font-semibold text-red-800 dark:text-red-300">
                                                Fehler beim Ausführen
                                            </p>
                                        )}
                                    </div>
                                    <span className={`text-sm font-mono font-bold ${
                                        isSuccess ? "text-green-700 dark:text-green-300" :
                                        isError   ? "text-red-700 dark:text-red-300" :
                                                    "text-blue-700 dark:text-blue-300"
                                    }`}>
                                        {progress}%
                                    </span>
                                </div>

                                {/* Progress bar */}
                                <Progress value={progress} className="h-2" />

                                {/* Step list — always visible */}
                                <ul className="space-y-1.5 pt-1">
                                    {STEPS.map((step, idx) => {
                                        const done   = idx < currentStep || isSuccess
                                        const active = isAnimating && idx === currentStep
                                        const Icon   = step.icon
                                        return (
                                            <li key={step.key} className="flex items-center gap-2.5 text-sm">
                                                {done ? (
                                                    <CheckCircle2 className="h-4 w-4 text-green-500 shrink-0" />
                                                ) : active ? (
                                                    <RefreshCw className="h-4 w-4 text-blue-500 animate-spin shrink-0" />
                                                ) : (
                                                    <Icon className={`h-4 w-4 shrink-0 ${
                                                        isError ? "text-red-300" : "text-muted-foreground/40"
                                                    }`} />
                                                )}
                                                <span className={
                                                    done   ? "text-green-700 dark:text-green-400" :
                                                    active ? "text-blue-700 dark:text-blue-300 font-medium" :
                                                    isError ? "text-red-400/70" :
                                                             "text-muted-foreground/50"
                                                }>
                                                    {step.label}
                                                </span>
                                            </li>
                                        )
                                    })}
                                </ul>

                                {/* Artisan output — persisted from result state, stays until next run */}
                                {isSuccess && result.status === "success" && result.output && (
                                    <div className="pt-1">
                                        <p className="text-xs text-green-700 dark:text-green-500 font-semibold mb-1.5 flex items-center gap-1">
                                            <Terminal className="h-3 w-3" /> Erstellte / aktualisierte Einträge:
                                        </p>
                                        <pre className="whitespace-pre-wrap rounded bg-black/10 dark:bg-black/40 p-3 text-xs font-mono text-green-800 dark:text-green-200 leading-relaxed max-h-96 overflow-y-auto">
                                            {result.output}
                                        </pre>
                                    </div>
                                )}

                                {/* Error detail */}
                                {isError && result.status === "error" && (
                                    <div className="rounded bg-red-100 dark:bg-red-950/50 border border-red-300 dark:border-red-800 p-3">
                                        <p className="text-xs font-mono text-red-800 dark:text-red-300 break-all">
                                            {result.message}
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    )
                })()}

                {/* ── Form ── */}
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
                            <Select value={companyId} onValueChange={setCompanyId} disabled={isAnimating}>
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
                            <Select value={type} onValueChange={setType} disabled={isAnimating}>
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
                                disabled={isAnimating}
                                className="mt-0.5"
                            />
                            <div className="space-y-1">
                                <Label htmlFor="force" className="font-semibold cursor-pointer">
                                    --force &nbsp;
                                    <Badge variant="outline" className="text-xs">Überschreiben</Badge>
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
                                disabled={!companyId || !type || isAnimating}
                                className="min-w-44"
                            >
                                {isAnimating ? (
                                    <>
                                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                        Wird ausgeführt…
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
