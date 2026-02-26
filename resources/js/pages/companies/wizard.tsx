import AppLayout from "@/layouts/app-layout"
import { BreadcrumbItem } from "@/types"
import { usePage, router } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Progress } from "@/components/ui/progress"
import { Alert, AlertDescription } from "@/components/ui/alert"
import {
    Building2, Mail, FileText, Bell, Landmark, User,
    CheckCircle, ArrowLeft, ArrowRight, X, AlertCircle, Briefcase,
} from "lucide-react"
import React, { useState, useRef, useEffect } from "react"

import Step1CompanyInfo from "./wizard/Step1CompanyInfo"
import Step2IndustryType from "./wizard/Step2IndustryType"
import Step3EmailSettings from "./wizard/Step2EmailSettings"
import Step4InvoiceSettings from "./wizard/Step3InvoiceSettings"
import Step5MahnungSettings from "./wizard/Step4MahnungSettings"
import Step6BankingInfo from "./wizard/Step5BankingInfo"
import Step7FirstUser from "./wizard/Step6FirstUser"
import Step8Review from "./wizard/Step7Review"

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Firmen", href: "/companies" },
    { title: "Neue Firma erstellen" },
]

const TOTAL_STEPS = 8

const steps = [
    { number: 1, title: "Firmeninformationen",    icon: Building2,   description: "Grundlegende Unternehmensdaten" },
    { number: 2, title: "Branchenpaket",           icon: Briefcase,   description: "Passende Produkte & Layouts automatisch anlegen" },
    { number: 3, title: "E-Mail Konfiguration",    icon: Mail,        description: "SMTP-Einstellungen für E-Mail-Versand" },
    { number: 4, title: "Rechnungseinstellungen",  icon: FileText,    description: "Präfixe, Steuersätze und Formate" },
    { number: 5, title: "Mahnungseinstellungen",   icon: Bell,        description: "Intervalle und Gebühren" },
    { number: 6, title: "Bankinformationen",        icon: Landmark,    description: "IBAN und BIC für Zahlungen" },
    { number: 7, title: "Erster Benutzer",          icon: User,        description: "Admin-Benutzer erstellen (optional)" },
    { number: 8, title: "Überprüfen & Erstellen",  icon: CheckCircle, description: "Zusammenfassung aller Einstellungen" },
]

// ─── Default form state ────────────────────────────────────────────────────────

const defaultData = {
    company_info: {
        name: "", email: "", phone: "", address: "",
        postal_code: "", city: "", country: "Deutschland",
        tax_number: "", vat_number: "", website: "",
        logo: null as string | null,
    },
    industry_type: {
        slug: null as string | null,
        initialize_data: true,
    },
    email_settings: {
        configure_smtp: false,
        smtp_host: "", smtp_port: 587, smtp_username: "", smtp_password: "",
        smtp_encryption: "tls", smtp_from_address: "", smtp_from_name: "",
    },
    invoice_settings: {
        invoice_prefix: "RE-", offer_prefix: "AN-", customer_prefix: "KD-",
        currency: "EUR", tax_rate: 0.19, reduced_tax_rate: 0.07,
        payment_terms: 14, offer_validity_days: 30,
        date_format: "d.m.Y", decimal_separator: ",", thousands_separator: ".",
    },
    mahnung_settings: {
        reminder_friendly_days: 7,
        reminder_mahnung1_days: 14, reminder_mahnung1_fee: 5,
        reminder_mahnung2_days: 21, reminder_mahnung2_fee: 10,
        reminder_mahnung3_days: 30, reminder_mahnung3_fee: 15,
        reminder_inkasso_days: 45,
        reminder_interest_rate: 9,
        reminder_auto_send: true,
    },
    banking_info: { bank_name: "", iban: "", bic: "", account_holder: "" },
    first_user: {
        create_user: false, name: "", email: "", password: "", send_welcome_email: true,
    },
}

type WizardFormData = typeof defaultData

// ─── Client-side validation per step ─────────────────────────────────────────

function validateStep(step: number, data: WizardFormData): Record<string, string> {
    const e: Record<string, string> = {}
    const ci = data.company_info
    const es = data.email_settings
    const fu = data.first_user

    if (step === 1) {
        if (!ci.name?.trim())
            e["company_info.name"] = "Das Feld Firmenname ist erforderlich."
        if (!ci.email?.trim())
            e["company_info.email"] = "Das Feld E-Mail ist erforderlich."
        else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(ci.email))
            e["company_info.email"] = "Bitte geben Sie eine gültige E-Mail-Adresse ein."
        if (ci.website?.trim() && !/^https?:\/\//.test(ci.website))
            e["company_info.website"] = "Die Webseite muss mit https:// oder http:// beginnen."
    }

    if (step === 3 && es.configure_smtp) {
        if (!es.smtp_host?.trim())
            e["email_settings.smtp_host"] = "Das Feld SMTP Host ist erforderlich."
        if (!es.smtp_username?.trim())
            e["email_settings.smtp_username"] = "Das Feld SMTP Benutzername ist erforderlich."
        if (!es.smtp_password?.trim())
            e["email_settings.smtp_password"] = "Das Feld SMTP Passwort ist erforderlich."
        if (!es.smtp_from_address?.trim())
            e["email_settings.smtp_from_address"] = "Das Feld Absender E-Mail ist erforderlich."
        else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(es.smtp_from_address))
            e["email_settings.smtp_from_address"] = "Bitte geben Sie eine gültige E-Mail-Adresse ein."
        if (!es.smtp_from_name?.trim())
            e["email_settings.smtp_from_name"] = "Das Feld Absender Name ist erforderlich."
    }

    if (step === 7 && fu.create_user) {
        if (!fu.name?.trim())
            e["first_user.name"] = "Das Feld Name ist erforderlich."
        if (!fu.email?.trim())
            e["first_user.email"] = "Das Feld E-Mail ist erforderlich."
        else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(fu.email))
            e["first_user.email"] = "Bitte geben Sie eine gültige E-Mail-Adresse ein."
        if (!fu.password?.trim())
            e["first_user.password"] = "Das Feld Passwort ist erforderlich."
        else if (fu.password.length < 8)
            e["first_user.password"] = "Das Passwort muss mindestens 8 Zeichen lang sein."
    }

    return e
}

// ─── Component ────────────────────────────────────────────────────────────────

export default function CompanyWizard() {
    // Server-side errors (returned after the final submit fails, e.g. email taken)
    const { errors: serverErrors = {} } = usePage<{ errors?: Record<string, string>; [key: string]: any }>().props

    const [currentStep, setCurrentStep] = useState(1)
    const [processing, setProcessing] = useState(false)
    const [localErrors, setLocalErrors] = useState<Record<string, string>>({})
    const [formData, setFormData] = useState<WizardFormData>(defaultData)

    // Logo lives in a ref (never serialized to state)
    const logoFileRef = useRef<File | null>(null)
    const [logoPreview, setLogoPreview] = useState<string | null>(null)

    // Merge server errors (base) with local validation errors (override)
    const errors: Record<string, string> = { ...(serverErrors as Record<string, string>), ...localErrors }
    const errorEntries = Object.entries(errors)
    const hasErrors = errorEntries.length > 0

    // Clean up blob URL on unmount
    useEffect(() => () => { if (logoPreview) URL.revokeObjectURL(logoPreview) }, [])

    // ── Helpers ───────────────────────────────────────────────────────────────

    const updateSection = (key: string, value: any) =>
        setFormData((prev) => ({ ...prev, [key]: value }))

    const handleLogoFile = (file: File | null) => {
        if (logoPreview) URL.revokeObjectURL(logoPreview)
        logoFileRef.current = file
        setLogoPreview(file ? URL.createObjectURL(file) : null)
    }

    // Logo display URL: local preview OR stored path from a previous session
    const storedLogoPath = formData.company_info?.logo as string | undefined
    const storedLogoUrl = storedLogoPath
        ? (storedLogoPath.startsWith("http") ? storedLogoPath : `/storage/${storedLogoPath}`)
        : null
    const effectiveLogoPreview = logoPreview || storedLogoUrl

    // ── Navigation ────────────────────────────────────────────────────────────

    const handleNext = () => {
        const stepErrors = validateStep(currentStep, formData)
        if (Object.keys(stepErrors).length > 0) {
            setLocalErrors(stepErrors)
            window.scrollTo({ top: 0, behavior: "smooth" })
            return
        }
        setLocalErrors({})
        setCurrentStep((s) => Math.min(s + 1, TOTAL_STEPS))
        window.scrollTo({ top: 0, behavior: "smooth" })
    }

    const handleBack = () => {
        setLocalErrors({})
        setCurrentStep((s) => Math.max(s - 1, 1))
        window.scrollTo({ top: 0, behavior: "smooth" })
    }

    const handleComplete = () => {
        setProcessing(true)
        const payload: Record<string, any> = { ...formData }
        if (logoFileRef.current) {
            payload.company_info = { ...payload.company_info, logo: logoFileRef.current }
        }
        router.post(route("companies.wizard.complete"), payload, {
            forceFormData: true,
            onFinish: () => setProcessing(false),
            onError: (errs) => {
                // onError fires for 422 responses; also show via serverErrors for redirects
                setLocalErrors(errs)
                window.scrollTo({ top: 0, behavior: "smooth" })
            },
        })
    }

    const handleCancel = () => {
        if (confirm("Möchten Sie den Wizard wirklich abbrechen? Alle Eingaben gehen verloren.")) {
            router.post(route("companies.wizard.cancel"), {})
        }
    }

    // ── Render ────────────────────────────────────────────────────────────────

    const progress = ((currentStep - 1) / (TOTAL_STEPS - 1)) * 100

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <div className="max-w-5xl mx-auto space-y-6">
                <div>
                    <h1 className="text-2xl font-bold">Neue Firma erstellen</h1>
                    <p className="text-muted-foreground">
                        Folgen Sie den Schritten, um eine neue Firma einzurichten
                    </p>
                </div>

                {/* Validation errors */}
                {hasErrors && (
                    <Alert variant="destructive">
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>
                            <ul className="list-disc list-inside space-y-1">
                                {errorEntries.map(([key, msg]) => (
                                    <li key={key}>{String(msg)}</li>
                                ))}
                            </ul>
                        </AlertDescription>
                    </Alert>
                )}

                {/* Progress */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="space-y-3">
                            <div className="flex justify-between text-sm">
                                <span className="font-medium">Schritt {currentStep} von {TOTAL_STEPS}</span>
                                <span className="text-muted-foreground">
                                    {Math.round(progress)}% abgeschlossen
                                </span>
                            </div>
                            <Progress value={progress} className="h-2" />
                        </div>
                    </CardContent>
                </Card>

                {/* Step indicators */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="grid grid-cols-8 gap-2">
                            {steps.map((step) => {
                                const Icon = step.icon
                                const isActive = currentStep === step.number
                                const isCompleted = currentStep > step.number
                                return (
                                    <div
                                        key={step.number}
                                        className={`flex flex-col items-center p-3 rounded-lg border-2 transition-all ${
                                            isActive
                                                ? "border-primary bg-primary/5"
                                                : isCompleted
                                                ? "border-green-500 bg-green-50"
                                                : "border-gray-200 bg-gray-50"
                                        }`}
                                    >
                                        <div
                                            className={`w-10 h-10 rounded-full flex items-center justify-center mb-2 ${
                                                isActive
                                                    ? "bg-primary text-white"
                                                    : isCompleted
                                                    ? "bg-green-500 text-white"
                                                    : "bg-gray-300 text-gray-600"
                                            }`}
                                        >
                                            {isCompleted ? (
                                                <CheckCircle className="h-5 w-5" />
                                            ) : (
                                                <Icon className="h-5 w-5" />
                                            )}
                                        </div>
                                        <span className="text-xs font-medium text-center leading-tight">
                                            {step.title}
                                        </span>
                                    </div>
                                )
                            })}
                        </div>
                    </CardContent>
                </Card>

                {/* Step content */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            {React.createElement(steps[currentStep - 1].icon, { className: "h-6 w-6" })}
                            {steps[currentStep - 1].title}
                        </CardTitle>
                        <CardDescription>{steps[currentStep - 1].description}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {currentStep === 1 && (
                            <Step1CompanyInfo
                                data={formData}
                                setData={updateSection}
                                errors={errors}
                                logoPreview={effectiveLogoPreview}
                                onLogoFile={handleLogoFile}
                            />
                        )}
                        {currentStep === 2 && (
                            <Step2IndustryType data={formData} setData={updateSection} errors={errors} />
                        )}
                        {currentStep === 3 && (
                            <Step3EmailSettings data={formData} setData={updateSection} errors={errors} />
                        )}
                        {currentStep === 4 && (
                            <Step4InvoiceSettings data={formData} setData={updateSection} errors={errors} />
                        )}
                        {currentStep === 5 && (
                            <Step5MahnungSettings data={formData} setData={updateSection} errors={errors} />
                        )}
                        {currentStep === 6 && (
                            <Step6BankingInfo data={formData} setData={updateSection} errors={errors} />
                        )}
                        {currentStep === 7 && (
                            <Step7FirstUser data={formData} setData={updateSection} errors={errors} />
                        )}
                        {currentStep === 8 && (
                            <Step8Review data={formData} logoPreview={effectiveLogoPreview} />
                        )}
                    </CardContent>
                </Card>

                {/* Navigation */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="flex justify-between">
                            <div className="flex gap-2">
                                <Button
                                    variant="outline"
                                    onClick={handleBack}
                                    disabled={currentStep === 1 || processing}
                                >
                                    <ArrowLeft className="mr-2 h-4 w-4" />
                                    Zurück
                                </Button>
                                <Button variant="ghost" onClick={handleCancel} disabled={processing}>
                                    <X className="mr-2 h-4 w-4" />
                                    Abbrechen
                                </Button>
                            </div>
                            <div>
                                {currentStep < TOTAL_STEPS ? (
                                    <Button onClick={handleNext} disabled={processing}>
                                        Weiter
                                        <ArrowRight className="ml-2 h-4 w-4" />
                                    </Button>
                                ) : (
                                    <Button onClick={handleComplete} disabled={processing} size="lg">
                                        <CheckCircle className="mr-2 h-5 w-5" />
                                        {processing ? "Erstelle Firma…" : "Firma erstellen"}
                                    </Button>
                                )}
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    )
}
