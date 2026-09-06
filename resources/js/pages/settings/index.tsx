"use client"

import { Head, Link, router, usePage } from "@inertiajs/react"
import { useEffect, useMemo, useRef, useState } from "react"
import { Tabs, TabsContent } from "@/components/ui/tabs"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { Badge } from "@/components/ui/badge"
import { Input } from "@/components/ui/input"
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select"
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
    Building2,
    FileText,
    Database,
    Search,
    ArrowUpRight,
} from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem, PageProps } from "@/types"
import { cn } from "@/lib/utils"
import { route } from "ziggy-js"

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
import NotificationsSettingsTab from "./tabs/notifications"

interface SettingsPageProps extends PageProps {
    company: any
    settings: any
    companySettings?: any
    emailSettings: any
    reminderSettings: any
    erechnungSettings: any
    paymentMethodSettings: any
    datevSettings: any
    notificationSettings?: any
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

const TAB_IDS = [
    "company-info",
    "company",
    "email",
    "reminders",
    "erechnung",
    "payment-methods",
    "notifications",
    "email-logs",
    "datev",
    "profile",
    "password",
    "appearance",
    "company-settings",
] as const

interface NavItem {
    id?: string
    href?: string
    label: string
    description?: string
    icon: any
    keywords?: string[]
    badge?: string
}

interface NavGroup {
    label: string
    items: NavItem[]
}

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
        notificationSettings,
        emailLogs,
        emailLogsStats,
        emailLogsFilters,
        user,
        activeTab = "company-info",
        flash,
        auth,
    } = page.props

    const authUser = auth?.user || user
    const roles: string[] = Array.isArray(authUser?.roles)
        ? authUser.roles.map((r: any) => (typeof r === "string" ? r : r?.name))
        : []
    const permissions: string[] = authUser?.permissions ?? []
    const isSuperAdmin = roles.includes("super_admin") || permissions.includes("manage_companies")
    const canImportExport = permissions.includes("manage_settings") || isSuperAdmin

    const validTabs = [...TAB_IDS].filter((tab) => tab !== "company-settings" || isSuperAdmin)

    const tabFromUrl = () => {
        if (typeof window === "undefined") {
            return null
        }
        const tab = new URLSearchParams(window.location.search).get("tab")
        return tab && validTabs.includes(tab as (typeof TAB_IDS)[number]) ? tab : null
    }

    const [currentTab, setCurrentTab] = useState(() => {
        const tab = tabFromUrl() || activeTab || "company-info"
        return validTabs.includes(tab as (typeof TAB_IDS)[number]) ? tab : "company-info"
    })

    const [search, setSearch] = useState("")
    const searchRef = useRef<HTMLInputElement>(null)

    useEffect(() => {
        const urlTab = tabFromUrl()
        if (urlTab) {
            setCurrentTab(urlTab)
        } else if (activeTab && validTabs.includes(activeTab as (typeof TAB_IDS)[number])) {
            setCurrentTab(activeTab)
        }
    }, [activeTab])

    // "/" focuses the settings search from anywhere on the page
    useEffect(() => {
        const onKeyDown = (e: KeyboardEvent) => {
            if (e.key !== "/" || e.metaKey || e.ctrlKey || e.altKey) return
            const target = e.target as HTMLElement | null
            if (
                target &&
                (target.tagName === "INPUT" ||
                    target.tagName === "TEXTAREA" ||
                    target.tagName === "SELECT" ||
                    target.isContentEditable)
            ) {
                return
            }
            e.preventDefault()
            searchRef.current?.focus()
        }
        window.addEventListener("keydown", onKeyDown)
        return () => window.removeEventListener("keydown", onKeyDown)
    }, [])

    const handleTabChange = (value: string) => {
        if (!validTabs.includes(value as (typeof TAB_IDS)[number])) {
            return
        }
        setCurrentTab(value)
        router.get(route("settings.index"), { tab: value }, {
            preserveState: true,
            preserveScroll: true,
        })
    }

    const navGroups: NavGroup[] = [
        {
            label: "Firma",
            items: [
                {
                    id: "company-info",
                    label: "Firmendaten",
                    description: "Stammdaten, Logo und Bankverbindung Ihrer Firma",
                    icon: Building2,
                    keywords: ["adresse", "logo", "iban", "bic", "bank", "bankverbindung", "steuernummer", "ust-id", "umsatzsteuer", "geschäftsführer", "handelsregister", "telefon", "website", "kleinunternehmer"],
                },
                {
                    id: "company",
                    label: "Nummern & Texte",
                    description: "Nummernkreise, Formate, Steuersätze und Standardtexte",
                    icon: Settings,
                    keywords: ["rechnungsnummer", "nummernkreis", "format", "zähler", "counter", "fußzeile", "zahlungsziel", "währung", "steuersatz", "mwst", "trennzeichen", "datumsformat", "storno", "angebotsnummer"],
                },
                {
                    href: "/invoice-layouts",
                    label: "Rechnungslayouts",
                    icon: LayoutTemplate,
                    keywords: ["pdf", "design", "vorlage", "briefpapier", "layout"],
                },
                {
                    href: "/offer-layouts",
                    label: "Angebotslayouts",
                    icon: LayoutTemplate,
                    keywords: ["pdf", "design", "vorlage", "angebot", "layout"],
                },
            ],
        },
        {
            label: "Finanzen",
            items: [
                {
                    id: "payment-methods",
                    label: "Zahlungen",
                    description: "Verfügbare Zahlungsmethoden und Standardauswahl",
                    icon: CreditCard,
                    keywords: ["zahlungsmethode", "überweisung", "sepa", "lastschrift", "paypal", "bar", "skonto"],
                },
                {
                    id: "reminders",
                    label: "Mahnwesen",
                    description: "Fristen, Gebühren und automatischer Versand von Mahnungen",
                    icon: AlarmClock,
                    keywords: ["mahnung", "gebühr", "verzugszinsen", "inkasso", "zahlungserinnerung", "eskalation", "überfällig", "frist"],
                },
                {
                    id: "erechnung",
                    label: "E-Rechnung",
                    description: "XRechnung- und ZUGFeRD-Einstellungen nach EN 16931",
                    icon: FileCheck,
                    keywords: ["xrechnung", "zugferd", "leitweg", "en16931", "elektronisch"],
                },
                {
                    id: "datev",
                    label: "DATEV",
                    description: "Exporteinstellungen für Ihren Steuerberater",
                    icon: Database,
                    keywords: ["export", "buchhaltung", "steuerberater", "kontenrahmen", "skr", "beraternummer", "mandant"],
                },
            ],
        },
        {
            label: "Kommunikation",
            items: [
                {
                    id: "email",
                    label: "E-Mail-Versand",
                    description: "SMTP-Zugangsdaten für den Versand von Belegen",
                    icon: Mail,
                    keywords: ["smtp", "server", "port", "absender", "versand", "verschlüsselung", "tls"],
                },
                {
                    id: "notifications",
                    label: "Benachrichtigungen",
                    description: "Interne Hinweise bei wichtigen Ereignissen",
                    icon: Bell,
                    keywords: ["hinweis", "notification", "ereignis"],
                },
                {
                    id: "email-logs",
                    label: "E-Mail-Verlauf",
                    description: "Alle versendeten E-Mails im Überblick",
                    icon: FileText,
                    keywords: ["verlauf", "protokoll", "gesendet", "historie", "log", "fehlgeschlagen"],
                },
            ],
        },
        {
            label: "Konto",
            items: [
                {
                    id: "profile",
                    label: "Profil",
                    description: "Ihr Name und Ihre Anmelde-E-Mail",
                    icon: User,
                    keywords: ["name", "konto", "account"],
                },
                {
                    id: "password",
                    label: "Passwort",
                    description: "Passwort ändern",
                    icon: Lock,
                    keywords: ["kennwort", "sicherheit", "login"],
                },
                {
                    id: "appearance",
                    label: "Erscheinungsbild",
                    description: "Helles oder dunkles Erscheinungsbild",
                    icon: Palette,
                    keywords: ["dunkel", "hell", "dark", "theme", "darstellung"],
                },
                ...(canImportExport
                    ? [{
                        href: "/settings/import-export",
                        label: "Import / Export",
                        icon: Download,
                        keywords: ["backup", "csv", "daten", "sicherung"],
                    }]
                    : []),
                ...(isSuperAdmin
                    ? [{
                        id: "company-settings",
                        label: "Erweiterte Schlüssel",
                        description: "Direkter Zugriff auf alle gespeicherten Konfigurationswerte",
                        icon: Settings,
                        keywords: ["keys", "roh", "erweitert", "debug"],
                        badge: "Admin",
                    }]
                    : []),
            ],
        },
    ]

    const normalizedSearch = search.trim().toLowerCase()

    const matchesSearch = (item: NavItem, groupLabel: string) => {
        if (!normalizedSearch) return true
        const haystack = [item.label, groupLabel, ...(item.keywords ?? [])].join(" ").toLowerCase()
        return haystack.includes(normalizedSearch)
    }

    const filteredGroups = useMemo(
        () =>
            navGroups
                .map((group) => ({
                    ...group,
                    items: group.items.filter((item) => matchesSearch(item, group.label)),
                }))
                .filter((group) => group.items.length > 0),
        // navGroups is rebuilt each render but its content only depends on permissions
        [normalizedSearch, isSuperAdmin, canImportExport],
    )

    const openFirstMatch = () => {
        const first = filteredGroups[0]?.items[0]
        if (!first) return
        if (first.href) {
            router.visit(first.href)
        } else if (first.id) {
            handleTabChange(first.id)
            setSearch("")
        }
    }

    const allItems = navGroups.flatMap((g) => g.items)
    const activeItem = allItems.find((item) => item.id === currentTab)

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Einstellungen" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-xl font-bold">Einstellungen</h1>
                    <p className="text-muted-foreground">
                        Firma, Finanzen, Versand und Ihr Konto an einem Ort.
                    </p>
                </div>

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

                {/* Mobile: compact grouped dropdown instead of a tall nav card */}
                <div className="lg:hidden">
                    <Select
                        value={currentTab}
                        onValueChange={(value) => {
                            if (value.startsWith("href:")) {
                                router.visit(value.slice(5))
                            } else {
                                handleTabChange(value)
                            }
                        }}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Bereich wählen…" />
                        </SelectTrigger>
                        <SelectContent>
                            {navGroups.map((group) => (
                                <SelectGroup key={group.label}>
                                    <SelectLabel>{group.label}</SelectLabel>
                                    {group.items.map((item) => (
                                        <SelectItem
                                            key={item.id ?? item.href}
                                            value={item.id ?? `href:${item.href}`}
                                        >
                                            {item.label}
                                        </SelectItem>
                                    ))}
                                </SelectGroup>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <Tabs value={currentTab} onValueChange={handleTabChange} className="flex flex-col gap-6 lg:flex-row">
                    <nav className="hidden w-60 shrink-0 self-start lg:sticky lg:top-6 lg:block">
                        <div className="space-y-5 rounded-lg border bg-card p-3">
                            <div className="relative">
                                <Search className="absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    ref={searchRef}
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyDown={(e) => {
                                        if (e.key === "Enter") {
                                            e.preventDefault()
                                            openFirstMatch()
                                        }
                                        if (e.key === "Escape") {
                                            setSearch("")
                                            searchRef.current?.blur()
                                        }
                                    }}
                                    placeholder="Suchen…  ( / )"
                                    className="h-9 pl-8"
                                    aria-label="Einstellungen durchsuchen"
                                />
                            </div>

                            {filteredGroups.length === 0 && (
                                <p className="px-2 pb-2 text-sm text-muted-foreground">
                                    Keine Einstellungen gefunden.
                                </p>
                            )}

                            {filteredGroups.map((group) => (
                                <div key={group.label}>
                                    <p className="mb-1.5 px-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                        {group.label}
                                    </p>
                                    <div className="space-y-0.5">
                                        {group.items.map((item) => {
                                            const Icon = item.icon
                                            if (item.href) {
                                                return (
                                                    <Link
                                                        key={item.href}
                                                        href={item.href}
                                                        className="group flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-sm text-muted-foreground hover:bg-muted hover:text-foreground"
                                                    >
                                                        <Icon className="h-4 w-4 shrink-0" />
                                                        <span className="flex-1 truncate">{item.label}</span>
                                                        <ArrowUpRight className="h-3.5 w-3.5 opacity-0 transition-opacity group-hover:opacity-60" />
                                                    </Link>
                                                )
                                            }
                                            return (
                                                <button
                                                    key={item.id}
                                                    type="button"
                                                    onClick={() => {
                                                        handleTabChange(item.id!)
                                                        setSearch("")
                                                    }}
                                                    className={cn(
                                                        "flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm",
                                                        currentTab === item.id
                                                            ? "bg-primary/10 font-medium text-foreground"
                                                            : "text-muted-foreground hover:bg-muted hover:text-foreground",
                                                    )}
                                                >
                                                    <Icon className="h-4 w-4 shrink-0" />
                                                    <span className="flex-1 truncate">{item.label}</span>
                                                    {item.badge && (
                                                        <Badge variant="outline" className="h-5 px-1.5 text-[10px] font-medium">
                                                            {item.badge}
                                                        </Badge>
                                                    )}
                                                </button>
                                            )
                                        })}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </nav>

                    <div className="min-w-0 flex-1 space-y-6">
                        {activeItem?.description && (
                            <div className="flex items-start gap-3">
                                <div className="mt-0.5 rounded-md border bg-muted/50 p-2">
                                    <activeItem.icon className="h-4 w-4 text-muted-foreground" />
                                </div>
                                <div>
                                    <h2 className="flex items-center gap-2 text-base font-semibold leading-tight">
                                        {activeItem.label}
                                        {activeItem.badge && (
                                            <Badge variant="outline" className="h-5 px-1.5 text-[10px] font-medium">
                                                {activeItem.badge}
                                            </Badge>
                                        )}
                                    </h2>
                                    <p className="text-sm text-muted-foreground">{activeItem.description}</p>
                                </div>
                            </div>
                        )}

                        <TabsContent value="company-info" className="mt-0 space-y-6">
                            <CompanyInfoTab company={company} />
                        </TabsContent>
                        <TabsContent value="company" className="mt-0 space-y-6">
                            <CompanySettingsTab company={company} settings={settings} />
                        </TabsContent>
                        <TabsContent value="email" className="mt-0 space-y-6">
                            <EmailSettingsTab emailSettings={emailSettings} />
                        </TabsContent>
                        <TabsContent value="reminders" className="mt-0 space-y-6">
                            <RemindersSettingsTab reminderSettings={reminderSettings} />
                        </TabsContent>
                        <TabsContent value="erechnung" className="mt-0 space-y-6">
                            <ERechnungSettingsTab erechnungSettings={erechnungSettings} />
                        </TabsContent>
                        <TabsContent value="payment-methods" className="mt-0 space-y-6">
                            <PaymentMethodsSettingsTab paymentMethodSettings={paymentMethodSettings} />
                        </TabsContent>
                        <TabsContent value="notifications" className="mt-0 space-y-6">
                            <NotificationsSettingsTab notificationSettings={notificationSettings} />
                        </TabsContent>
                        <TabsContent value="email-logs" className="mt-0 space-y-6">
                            <EmailLogsTab
                                emailLogs={emailLogs}
                                emailLogsStats={emailLogsStats}
                                emailLogsFilters={emailLogsFilters}
                            />
                        </TabsContent>
                        <TabsContent value="datev" className="mt-0 space-y-6">
                            <DatevSettingsTab datevSettings={datevSettings} />
                        </TabsContent>
                        <TabsContent value="company-settings" className="mt-0 space-y-6">
                            <CompanySettingsAdminTab companySettings={companySettings ?? []} />
                        </TabsContent>
                        <TabsContent value="profile" className="mt-0 space-y-6">
                            <ProfileSettingsTab user={authUser} />
                        </TabsContent>
                        <TabsContent value="password" className="mt-0 space-y-6">
                            <PasswordSettingsTab />
                        </TabsContent>
                        <TabsContent value="appearance" className="mt-0 space-y-6">
                            <AppearanceSettingsTab />
                        </TabsContent>
                    </div>
                </Tabs>
            </div>
        </AppLayout>
    )
}
