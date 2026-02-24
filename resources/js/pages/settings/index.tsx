"use client"

import { Head, Link, router, useForm, usePage } from "@inertiajs/react"
import { useState, useEffect } from "react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Switch } from "@/components/ui/switch"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { 
    Settings, 
    Mail, 
    Bell,
    AlarmClock,
    FileCheck, 
    CreditCard, 
    LayoutTemplate, 
    Download, 
    User, 
    Lock, 
    Palette,
    AlertCircle,
    CheckCircle2,
    Eye,
    ExternalLink,
    Save,
    Building2,
    FileText,
    Database
} from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem } from "@/types"
import { route } from "ziggy-js"

// Import existing components
import CompanySettingsTab from "./tabs/company"
import CompanyInfoTab from "./tabs/company-info"
import EmailSettingsTab from "./tabs/email"
import RemindersSettingsTab from "./tabs/reminders"
import ERechnungSettingsTab from "./tabs/erechnung"
import PaymentMethodsSettingsTab from "./tabs/payment-methods"
import EmailLogsTab from "./tabs/email-logs"
import ProfileSettingsTab from "./tabs/profile"
import PasswordSettingsTab from "./tabs/password"
import AppearanceSettingsTab from "./tabs/appearance"
import DatevSettingsTab from "./tabs/datev"
import CompanySettingsAdminTab from "./tabs/company-settings"

interface SettingsPageProps {
    company: any
    settings: any
    companySettings?: any
    emailSettings: any
    reminderSettings: any
    erechnungSettings: any
    paymentMethodSettings: any
    datevSettings: any
    emailLogs?: any
    emailLogsStats?: any
    emailLogsFilters?: any
    user?: any
    activeTab?: string
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Einstellungen" },
]

export default function SettingsIndex() {
    const page = usePage<SettingsPageProps>()
    const { 
        company, 
        settings, 
        companySettings,
        emailSettings, 
        reminderSettings, 
        erechnungSettings,
        paymentMethodSettings,
        datevSettings,
        emailLogs,
        emailLogsStats,
        emailLogsFilters,
        user,
        activeTab = "company",
        flash
    } = page.props

    const isSuperAdmin = Array.isArray(user?.roles)
        ? user.roles.some((r: any) => (typeof r === 'string' ? r : r?.name) === 'super_admin')
        : false

    // Valid tab values
    const validTabs = [
        'company', 'company-info', 'email', 'reminders', 'erechnung', 
        'payment-methods', 'email-logs', 
        'datev', 'profile', 'password', 'appearance',
        ...(isSuperAdmin ? ['company-settings'] : []),
    ]

    // Get tab from URL - prioritize URL over prop
    const getTabFromUrl = () => {
        if (typeof window !== 'undefined') {
            const urlParams = new URLSearchParams(window.location.search)
            const tab = urlParams.get("tab")
            // Validate tab value
            if (tab && validTabs.includes(tab)) {
                return tab
            }
        }
        return null
    }

    // Initialize with URL tab if available, otherwise use prop
    const [currentTab, setCurrentTab] = useState(() => {
        const urlTab = getTabFromUrl()
        const tab = urlTab || activeTab || 'company'
        // Ensure tab is valid
        return validTabs.includes(tab) ? tab : 'company'
    })

    // Update tab when URL changes (on mount and when activeTab prop changes)
    useEffect(() => {
        const urlTab = getTabFromUrl()
        // Always prioritize URL parameter
        if (urlTab) {
            setCurrentTab(urlTab)
        } else if (activeTab) {
            setCurrentTab(activeTab)
        }
    }, [activeTab])

    const handleTabChange = (value: string) => {
        if (value && validTabs.includes(value)) {
            setCurrentTab(value)
            // Update URL without page reload
            router.get(route("settings.index"), { tab: value }, {
                preserveState: true,
                preserveScroll: true,
            })
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Einstellungen" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-1xl font-bold tracking-tight">Einstellungen</h1>
                        <p className="text-muted-foreground">
                            Verwalten Sie alle Einstellungen für Ihr Unternehmen und Ihr Profil
                        </p>
                    </div>
                </div>

                {/* Success/Error Messages */}
                {flash?.success && (
                    <Alert className="border-green-200 bg-green-50">
                        <CheckCircle2 className="h-4 w-4 text-green-600" />
                        <AlertDescription className="text-green-800">{flash.success}</AlertDescription>
                    </Alert>
                )}
                {flash?.error && (
                    <Alert variant="destructive">
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>{flash.error}</AlertDescription>
                    </Alert>
                )}

                {/* Tabs - Vertical Layout */}
                <Tabs value={currentTab} onValueChange={handleTabChange} className="flex flex-col lg:flex-row gap-6">
                    {/* Vertical Tabs List */}
                    <div className="w-full lg:w-64 flex-shrink-0">
                        <TabsList className="flex flex-col h-auto w-full p-1 bg-muted">
                            <TabsTrigger 
                                value="company-info" 
                                className="w-full justify-start gap-2 data-[state=active]:bg-background"
                            >
                                <Building2 className="h-4 w-4" />
                                <span>Firmendaten</span>
                            </TabsTrigger>
                            <TabsTrigger 
                                value="company" 
                                className="w-full justify-start gap-2 data-[state=active]:bg-background"
                            >
                                <Settings className="h-4 w-4" />
                                <span>Dokumenteneinstellungen</span>
                            </TabsTrigger>
                            <TabsTrigger 
                                value="email" 
                                className="w-full justify-start gap-2 data-[state=active]:bg-background"
                            >
                                <Mail className="h-4 w-4" />
                                <span>E-Mail</span>
                            </TabsTrigger>
                            <TabsTrigger 
                                value="reminders" 
                                className="w-full justify-start gap-2 data-[state=active]:bg-background"
                            >
                                <AlarmClock className="h-4 w-4" />
                                <span>Mahnungen</span>
                            </TabsTrigger>
                            <TabsTrigger 
                                value="erechnung" 
                                className="w-full justify-start gap-2 data-[state=active]:bg-background"
                            >
                                <FileCheck className="h-4 w-4" />
                                <span>E-Rechnung</span>
                            </TabsTrigger>
                            <TabsTrigger 
                                value="payment-methods" 
                                className="w-full justify-start gap-2 data-[state=active]:bg-background"
                            >
                                <CreditCard className="h-4 w-4" />
                                <span>Zahlungen</span>
                            </TabsTrigger>
                            <TabsTrigger 
                                value="email-logs" 
                                className="w-full justify-start gap-2 data-[state=active]:bg-background"
                            >
                                <FileText className="h-4 w-4" />
                                <span>E-Mail-Verlauf</span>
                            </TabsTrigger>
                            <TabsTrigger 
                                value="datev" 
                                className="w-full justify-start gap-2 data-[state=active]:bg-background"
                            >
                                <Database className="h-4 w-4" />
                                <span>DATEV</span>
                            </TabsTrigger>
                            {isSuperAdmin && (
                                <TabsTrigger 
                                    value="company-settings" 
                                    className="w-full justify-start gap-2 data-[state=active]:bg-background"
                                >
                                    <Settings className="h-4 w-4" />
                                    <span>Erweiterte Einstellungen</span>
                                </TabsTrigger>
                            )}
                            <TabsTrigger 
                                value="profile" 
                                className="w-full justify-start gap-2 data-[state=active]:bg-background"
                            >
                                <User className="h-4 w-4" />
                                <span>Profil</span>
                            </TabsTrigger>
                            <TabsTrigger 
                                value="password" 
                                className="w-full justify-start gap-2 data-[state=active]:bg-background"
                            >
                                <Lock className="h-4 w-4" />
                                <span>Passwort</span>
                            </TabsTrigger>
                            <TabsTrigger 
                                value="appearance" 
                                className="w-full justify-start gap-2 data-[state=active]:bg-background"
                            >
                                <Palette className="h-4 w-4" />
                                <span>Erscheinungsbild</span>
                            </TabsTrigger>
                        </TabsList>
                    </div>

                    {/* Tab Contents */}
                    <div className="flex-1 min-w-0">
                        <TabsContent value="company-info" className="space-y-6 mt-0">
                            <CompanyInfoTab company={company} />
                        </TabsContent>

                        <TabsContent value="company" className="space-y-6 mt-0">
                            <CompanySettingsTab company={company} settings={settings} />
                        </TabsContent>

                        <TabsContent value="email" className="space-y-6 mt-0">
                            <EmailSettingsTab emailSettings={emailSettings} />
                        </TabsContent>

                        <TabsContent value="reminders" className="space-y-6 mt-0">
                            <RemindersSettingsTab reminderSettings={reminderSettings} />
                        </TabsContent>

                        <TabsContent value="erechnung" className="space-y-6 mt-0">
                            <ERechnungSettingsTab erechnungSettings={erechnungSettings} />
                        </TabsContent>

                        <TabsContent value="payment-methods" className="space-y-6 mt-0">
                            <PaymentMethodsSettingsTab paymentMethodSettings={paymentMethodSettings} />
                        </TabsContent>

                        <TabsContent value="email-logs" className="space-y-6 mt-0">
                            <EmailLogsTab 
                                emailLogs={emailLogs} 
                                emailLogsStats={emailLogsStats}
                                emailLogsFilters={emailLogsFilters}
                            />
                        </TabsContent>

                        <TabsContent value="datev" className="space-y-6 mt-0">
                            <DatevSettingsTab datevSettings={datevSettings} />
                        </TabsContent>

                        <TabsContent value="company-settings" className="space-y-6 mt-0">
                            <CompanySettingsAdminTab companySettings={companySettings ?? []} />
                        </TabsContent>

                        <TabsContent value="profile" className="space-y-6 mt-0">
                            <ProfileSettingsTab user={user} />
                        </TabsContent>

                        <TabsContent value="password" className="space-y-6 mt-0">
                            <PasswordSettingsTab />
                        </TabsContent>

                        <TabsContent value="appearance" className="space-y-6 mt-0">
                            <AppearanceSettingsTab />
                        </TabsContent>
                    </div>
                </Tabs>
            </div>
        </AppLayout>
    )
}

