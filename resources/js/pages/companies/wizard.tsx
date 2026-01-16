import AppLayout from "@/layouts/app-layout"
import { BreadcrumbItem } from "@/types"
import { useForm, usePage, router } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Progress } from "@/components/ui/progress"
import { Building2, Mail, FileText, Bell, Landmark, User, CheckCircle, ArrowLeft, ArrowRight, X } from "lucide-react"
import React, { useState, useEffect } from "react"

// Import step components
import Step1CompanyInfo from "./wizard/Step1CompanyInfo"
import Step2EmailSettings from "./wizard/Step2EmailSettings"
import Step3InvoiceSettings from "./wizard/Step3InvoiceSettings"
import Step4MahnungSettings from "./wizard/Step4MahnungSettings"
import Step5BankingInfo from "./wizard/Step5BankingInfo"
import Step6FirstUser from "./wizard/Step6FirstUser"
import Step7Review from "./wizard/Step7Review"

interface WizardProps {
    wizardData: {
        step: number
        company_info: any
        email_settings: any
        invoice_settings: any
        mahnung_settings: any
        banking_info: any
        first_user: any
    }
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Firmen", href: "/companies" },
    { title: "Neue Firma erstellen" },
]

const steps = [
    { number: 1, title: "Firmeninformationen", icon: Building2, description: "Grundlegende Unternehmensdaten" },
    { number: 2, title: "E-Mail Konfiguration", icon: Mail, description: "SMTP-Einstellungen für E-Mail-Versand" },
    { number: 3, title: "Rechnungseinstellungen", icon: FileText, description: "Präfixe, Steuersätze und Formate" },
    { number: 4, title: "Mahnungseinstellungen", icon: Bell, description: "Intervalle und Gebühren" },
    { number: 5, title: "Bankinformationen", icon: Landmark, description: "IBAN und BIC für Zahlungen" },
    { number: 6, title: "Erster Benutzer", icon: User, description: "Admin-Benutzer erstellen (optional)" },
    { number: 7, title: "Überprüfen & Erstellen", icon: CheckCircle, description: "Zusammenfassung aller Einstellungen" },
]

export default function CompanyWizard() {
    const { wizardData } = usePage<WizardProps>().props
    const [currentStep, setCurrentStep] = useState(wizardData?.step || 1)

    const { data, setData, post, processing, errors } = useForm({
        ...wizardData,
        step: currentStep,
        action: 'next' as 'next' | 'back',
    })

    useEffect(() => {
        if (wizardData) {
            setData(wizardData as any)
            setCurrentStep(wizardData.step)
        }
    }, [wizardData])

    const handleNext = () => {
        // Update form data with current step and action
        setData((prev) => ({
            ...prev,
            step: currentStep,
            action: 'next'
        }))
        
        // Use setTimeout to ensure state is updated before posting
        setTimeout(() => {
            post(route('companies.wizard.update'), {
                preserveScroll: true,
                onSuccess: (page: any) => {
                    const newWizardData = page.props.wizardData
                    if (newWizardData) {
                        setCurrentStep(newWizardData.step)
                    }
                },
            })
        }, 0)
    }

    const handleBack = () => {
        if (currentStep > 1) {
            // Update form data with current step and action
            setData((prev) => ({
                ...prev,
                step: currentStep,
                action: 'back'
            }))
            
            // Use setTimeout to ensure state is updated before posting
            setTimeout(() => {
                post(route('companies.wizard.update'), {
                    preserveScroll: true,
                    onSuccess: (page: any) => {
                        const newWizardData = page.props.wizardData
                        if (newWizardData) {
                            setCurrentStep(newWizardData.step)
                        }
                    },
                })
            }, 0)
        }
    }

    const handleComplete = () => {
        post(route('companies.wizard.complete'), {
            preserveScroll: false,
        })
    }

    const handleCancel = () => {
        if (confirm('Möchten Sie den Wizard wirklich abbrechen? Alle Eingaben gehen verloren.')) {
            router.post(route('companies.wizard.cancel'), {})
        }
    }

    const progress = ((currentStep - 1) / 6) * 100

    return (
        <AppLayout title="Neue Firma erstellen" breadcrumbs={breadcrumbs}>
            <div className="max-w-5xl mx-auto space-y-6">
                {/* Header */}
                <div>
                    <h1 className="text-1xl font-bold">Neue Firma erstellen</h1>
                    <p className="text-muted-foreground">Folgen Sie den Schritten, um eine neue Firma einzurichten</p>
                </div>

                {/* Progress Bar */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="space-y-4">
                            <div className="flex justify-between text-sm">
                                <span className="font-medium">Schritt {currentStep} von 7</span>
                                <span className="text-muted-foreground">{Math.round(progress)}% abgeschlossen</span>
                            </div>
                            <Progress value={progress} className="h-2" />
                        </div>
                    </CardContent>
                </Card>

                {/* Steps Navigation */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="grid grid-cols-7 gap-2">
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

                {/* Current Step Content */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            {React.createElement(steps[currentStep - 1].icon, { className: "h-6 w-6" })}
                            {steps[currentStep - 1].title}
                        </CardTitle>
                        <CardDescription>{steps[currentStep - 1].description}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {currentStep === 1 && <Step1CompanyInfo data={data} setData={setData} errors={errors} />}
                        {currentStep === 2 && <Step2EmailSettings data={data} setData={setData} errors={errors} />}
                        {currentStep === 3 && <Step3InvoiceSettings data={data} setData={setData} errors={errors} />}
                        {currentStep === 4 && <Step4MahnungSettings data={data} setData={setData} errors={errors} />}
                        {currentStep === 5 && <Step5BankingInfo data={data} setData={setData} errors={errors} />}
                        {currentStep === 6 && <Step6FirstUser data={data} setData={setData} errors={errors} />}
                        {currentStep === 7 && <Step7Review data={data} />}
                    </CardContent>
                </Card>

                {/* Navigation Buttons */}
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
                                {currentStep < 7 ? (
                                    <Button onClick={handleNext} disabled={processing}>
                                        Weiter
                                        <ArrowRight className="ml-2 h-4 w-4" />
                                    </Button>
                                ) : (
                                    <Button onClick={handleComplete} disabled={processing} size="lg">
                                        <CheckCircle className="mr-2 h-5 w-5" />
                                        {processing ? "Erstelle Firma..." : "Firma erstellen"}
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
