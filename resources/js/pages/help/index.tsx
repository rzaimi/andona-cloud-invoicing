"use client"

import { useState } from "react"
import { Head, Link } from "@inertiajs/react"
import AppLayout from "@/layouts/app-layout"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion"
import {
    Search,
    BookOpen,
    Users,
    ReceiptEuro,
    FileText,
    Settings,
    Package,
    HelpCircle,
    ExternalLink,
    Mail,
    Phone,
    MessageCircle,
    Video,
    Download,
    Star,
    Clock,
    CheckCircle,
    AlertCircle,
    Info,
} from "lucide-react"

interface HelpProps {
    user: any
    stats: any
}

export default function HelpIndex({ user, stats }: HelpProps) {
    const [searchQuery, setSearchQuery] = useState("")

    const helpCategories = [
        {
            id: "getting-started",
            title: "Erste Schritte",
            description: "Grundlagen für den Einstieg in das Rechnungssystem",
            icon: BookOpen,
            color: "bg-blue-500",
            articles: 15,
        },
        {
            id: "customers",
            title: "Kundenverwaltung",
            description: "Kunden anlegen, bearbeiten und verwalten",
            icon: Users,
            color: "bg-green-500",
            articles: 12,
        },
        {
            id: "invoices",
            title: "Rechnungen",
            description: "Rechnungen erstellen, versenden und verwalten",
            icon: ReceiptEuro,
            color: "bg-purple-500",
            articles: 20,
        },
        {
            id: "offers",
            title: "Angebote",
            description: "Angebote erstellen und in Rechnungen umwandeln",
            icon: FileText,
            color: "bg-orange-500",
            articles: 12,
        },
        {
            id: "products",
            title: "Produktverwaltung",
            description: "Produkte, Kategorien und Lagerbestand verwalten",
            icon: Package,
            color: "bg-indigo-500",
            articles: 14,
        },
        {
            id: "payments",
            title: "Zahlungen",
            description: "Zahlungen erfassen, verwalten und Rechnungen als bezahlt markieren",
            icon: ReceiptEuro,
            color: "bg-emerald-500",
            articles: 10,
        },
        {
            id: "expenses",
            title: "Ausgaben",
            description: "Ausgaben erfassen, kategorisieren und Belege verwalten",
            icon: ReceiptEuro,
            color: "bg-red-500",
            articles: 11,
        },
        {
            id: "reports",
            title: "Berichte",
            description: "Umsatzberichte, Gewinn & Verlust, MwSt-Berichte und mehr",
            icon: FileText,
            color: "bg-cyan-500",
            articles: 9,
        },
        {
            id: "calendar",
            title: "Kalender",
            description: "Termine verwalten, Fälligkeiten überwachen und Ereignisse planen",
            icon: Clock,
            color: "bg-pink-500",
            articles: 7,
        },
        {
            id: "erechnung",
            title: "E-Rechnung",
            description: "Elektronische Rechnungen im XRechnung und ZUGFeRD Format",
            icon: FileText,
            color: "bg-teal-500",
            articles: 8,
        },
        {
            id: "reminders",
            title: "Mahnungen",
            description: "Automatisierte Mahnverfahren nach deutschem Recht",
            icon: AlertCircle,
            color: "bg-amber-500",
            articles: 10,
        },
        {
            id: "settings",
            title: "Einstellungen",
            description: "System konfigurieren, Layouts anpassen und Einstellungen verwalten",
            icon: Settings,
            color: "bg-gray-500",
            articles: 18,
        },
    ]

    const popularArticles = [
        {
            id: 1,
            title: "Wie erstelle ich meine erste Rechnung?",
            category: "Rechnungen",
            views: 1250,
            rating: 4.8,
            lastUpdated: "2024-01-15",
        },
        {
            id: 2,
            title: "Kunden anlegen und verwalten",
            category: "Kundenverwaltung",
            views: 1100,
            rating: 4.7,
            lastUpdated: "2024-01-16",
        },
        {
            id: 3,
            title: "Angebote in Rechnungen umwandeln",
            category: "Angebote",
            views: 950,
            rating: 4.9,
            lastUpdated: "2024-01-12",
        },
        {
            id: 4,
            title: "Rechnungslayouts anpassen",
            category: "Einstellungen",
            views: 890,
            rating: 4.6,
            lastUpdated: "2024-01-08",
        },
        {
            id: 5,
            title: "Zahlungen erfassen und Rechnungen als bezahlt markieren",
            category: "Zahlungen",
            views: 850,
            rating: 4.8,
            lastUpdated: "2024-01-14",
        },
        {
            id: 6,
            title: "Ausgaben erfassen und kategorisieren",
            category: "Ausgaben",
            views: 780,
            rating: 4.7,
            lastUpdated: "2024-01-13",
        },
        {
            id: 7,
            title: "Mahnungen automatisch versenden",
            category: "Mahnungen",
            views: 720,
            rating: 4.9,
            lastUpdated: "2024-01-11",
        },
        {
            id: 8,
            title: "E-Rechnung (XRechnung/ZUGFeRD) erstellen",
            category: "E-Rechnung",
            views: 680,
            rating: 4.6,
            lastUpdated: "2024-01-10",
        },
        {
            id: 9,
            title: "Rechnungskorrekturen und Stornierungen",
            category: "Rechnungen",
            views: 650,
            rating: 4.8,
            lastUpdated: "2024-01-09",
        },
        {
            id: 10,
            title: "Lagerbestand verwalten und Warnungen einrichten",
            category: "Produktverwaltung",
            views: 620,
            rating: 4.7,
            lastUpdated: "2024-01-14",
        },
        {
            id: 11,
            title: "Gewinn & Verlust Berichte erstellen",
            category: "Berichte",
            views: 580,
            rating: 4.6,
            lastUpdated: "2024-01-12",
        },
        {
            id: 12,
            title: "E-Mail-Einstellungen für Rechnungsversand konfigurieren",
            category: "Einstellungen",
            views: 550,
            rating: 4.5,
            lastUpdated: "2024-01-08",
        },
    ]

    const faqItems = [
        {
            question: "Wie kann ich meine Rechnungsnummer anpassen?",
            answer:
                "Gehen Sie zu Einstellungen > Firmeneinstellungen und passen Sie das Rechnungsnummern-Format an. Sie können Präfixe (z.B. 'RE-'), Jahresangaben und die Startnummer konfigurieren. Das Format wird automatisch für alle neuen Rechnungen verwendet.",
        },
        {
            question: "Kann ich mehrere Steuersätze verwenden?",
            answer:
                "Ja, das System unterstützt verschiedene Steuersätze. Sie können für jedes Produkt einen individuellen Steuersatz festlegen (19%, 7%, 0% etc.). Standardmäßig wird der in den Firmeneinstellungen hinterlegte Steuersatz verwendet.",
        },
        {
            question: "Wie versende ich Rechnungen per E-Mail?",
            answer:
                'Öffnen Sie die Rechnung und klicken Sie auf "Versenden". Das System generiert automatisch eine PDF und versendet diese mit einer professionellen E-Mail-Vorlage. Stellen Sie sicher, dass Ihre E-Mail-Einstellungen (SMTP) in den Einstellungen konfiguriert sind.',
        },
        {
            question: "Kann ich Angebote automatisch in Rechnungen umwandeln?",
            answer:
                "Ja, bei angenommenen Angeboten können Sie mit einem Klick eine Rechnung erstellen. Alle Daten (Kunde, Positionen, Beträge) werden automatisch übernommen. Sie können die Rechnung vor dem Speichern noch anpassen.",
        },
        {
            question: "Wie funktioniert die Lagerverwaltung?",
            answer:
                "Das System verfolgt automatisch Ihren Lagerbestand, wenn Sie die Lagerverwaltung für ein Produkt aktivieren. Bei jeder Rechnung wird der Bestand reduziert. Sie erhalten Warnungen bei niedrigen Lagerständen (Mindestbestand) und können Lagerbestände manuell anpassen.",
        },
        {
            question: "Wie erfasse ich Zahlungen auf Rechnungen?",
            answer:
                "Gehen Sie zu Zahlungen > Neue Zahlung und wählen Sie die entsprechende Rechnung aus. Geben Sie den Betrag, das Zahlungsdatum und die Zahlungsmethode ein. Wenn die Rechnung vollständig bezahlt ist, wird sie automatisch als 'Bezahlt' markiert.",
        },
        {
            question: "Wie funktionieren Mahnungen?",
            answer:
                "Das System unterstützt ein 5-stufiges Mahnverfahren nach deutschem Recht. Konfigurieren Sie die Mahnintervalle und Gebühren in Einstellungen > Erinnerungen. Das System sendet automatisch Mahnungen basierend auf dem Fälligkeitsdatum der Rechnung.",
        },
        {
            question: "Kann ich Rechnungen korrigieren oder stornieren?",
            answer:
                "Ja, Sie können Rechnungskorrekturen (Stornorechnungen) erstellen. Öffnen Sie die zu korrigierende Rechnung und wählen Sie 'Korrigieren'. Geben Sie den Korrekturgrund an. Das System erstellt automatisch eine Stornorechnung mit negativen Beträgen.",
        },
        {
            question: "Wie erstelle ich E-Rechnungen (XRechnung/ZUGFeRD)?",
            answer:
                "Aktivieren Sie E-Rechnung in Einstellungen > E-Rechnung. Wählen Sie das Format (XRechnung oder ZUGFeRD) und geben Sie Ihre elektronische Adresse an. Beim Erstellen einer Rechnung können Sie dann die E-Rechnung generieren.",
        },
        {
            question: "Wie erfasse ich Ausgaben?",
            answer:
                "Gehen Sie zu Ausgaben > Neue Ausgabe. Geben Sie Titel, Betrag, Datum, Kategorie und optional einen Beleg an. Das System berechnet automatisch MwSt. und Netto-Betrag basierend auf dem Steuersatz.",
        },
        {
            question: "Wie erstelle ich Berichte?",
            answer:
                "Gehen Sie zu Berichte und wählen Sie den gewünschten Berichtstyp (Umsatz, Gewinn & Verlust, MwSt., Ausgaben, etc.). Sie können Zeiträume filtern und die Berichte als PDF exportieren.",
        },
        {
            question: "Kann ich mehrere Firmen verwalten?",
            answer:
                "Ja, Super-Administratoren können mehrere Firmen verwalten und zwischen ihnen wechseln. Jede Firma hat ihre eigenen Daten, Einstellungen und Benutzer. Die Daten sind vollständig voneinander isoliert.",
        },
        {
            question: "Wie funktioniert der Kalender?",
            answer:
                "Der Kalender zeigt automatisch fällige Rechnungen, ablaufende Angebote und Ihre eigenen Termine. Sie können neue Termine erstellen, bearbeiten und löschen. Der Kalender hilft Ihnen, wichtige Fristen im Blick zu behalten.",
        },
        {
            question: "Kann ich meine Daten exportieren?",
            answer:
                "Ja, Sie können alle Ihre Daten (Kunden, Produkte, Rechnungen, Ausgaben) in verschiedenen Formaten exportieren. Gehen Sie zu den jeweiligen Listenansichten und nutzen Sie die Export-Funktion.",
        },
        {
            question: "Wie ändere ich mein Passwort?",
            answer:
                "Gehen Sie zu Einstellungen > Passwort. Geben Sie Ihr aktuelles Passwort und das neue Passwort ein. Das neue Passwort muss mindestens 8 Zeichen lang sein.",
        },
        {
            question: "Was sind Rollen und Berechtigungen?",
            answer:
                "Das System hat drei Hauptrollen: Super-Admin (voller Zugriff auf alle Firmen), Admin (voller Zugriff innerhalb einer Firma) und Benutzer (eingeschränkter Zugriff). Berechtigungen können individuell angepasst werden.",
        },
    ]

    const supportOptions = [
        {
            title: "E-Mail Support",
            description: "Senden Sie uns Ihre Frage per E-Mail",
            icon: Mail,
            action: "support@rechnungssystem.de",
            responseTime: "Antwort innerhalb von 24 Stunden",
            available: true,
        },
        {
            title: "Telefon Support",
            description: "Sprechen Sie direkt mit unserem Support-Team",
            icon: Phone,
            action: "+49 (0) 123 456 789",
            responseTime: "Mo-Fr 9:00-17:00 Uhr",
            available: true,
        },
        {
            title: "Live Chat",
            description: "Sofortige Hilfe über unseren Live Chat",
            icon: MessageCircle,
            action: "Chat starten",
            responseTime: "Sofortige Antwort",
            available: false,
        },
        {
            title: "Video Call",
            description: "Persönliche Beratung per Videoanruf",
            icon: Video,
            action: "Termin buchen",
            responseTime: "Nach Vereinbarung",
            available: true,
        },
    ]

    const filteredCategories = helpCategories.filter(
        (category) =>
            category.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
            category.description.toLowerCase().includes(searchQuery.toLowerCase()),
    )

    const filteredArticles = popularArticles.filter(
        (article) =>
            article.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
            article.category.toLowerCase().includes(searchQuery.toLowerCase()),
    )

    return (
        <AppLayout user={user}>
            <Head title="Hilfe & Support" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-1xl font-bold tracking-tight dark:text-gray-100">Hilfe & Support</h1>
                        <p className="text-muted-foreground">
                            Finden Sie Antworten auf Ihre Fragen und lernen Sie das System kennen
                        </p>
                    </div>
                    <Button asChild>
                        <Link href="/help/contact">
                            <HelpCircle className="mr-2 h-4 w-4" />
                            Kontakt aufnehmen
                        </Link>
                    </Button>
                </div>

                {/* Search */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                placeholder="Durchsuchen Sie unsere Hilfe-Artikel..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="pl-10"
                            />
                        </div>
                    </CardContent>
                </Card>

                <Tabs defaultValue="categories" className="space-y-6">
                    <TabsList className="grid w-full grid-cols-4">
                        <TabsTrigger value="categories">Kategorien</TabsTrigger>
                        <TabsTrigger value="popular">Beliebte Artikel</TabsTrigger>
                        <TabsTrigger value="faq">FAQ</TabsTrigger>
                        <TabsTrigger value="support">Support</TabsTrigger>
                    </TabsList>

                    {/* Categories Tab */}
                    <TabsContent value="categories" className="space-y-6">
                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            {filteredCategories.map((category) => (
                                <Link key={category.id} href={`/help/${category.id}`}>
                                    <Card className="hover:shadow-md transition-shadow cursor-pointer group h-full">
                                        <CardHeader>
                                            <div className="flex items-center space-x-3">
                                                <div className={`p-2 rounded-lg ${category.color} group-hover:scale-110 transition-transform`}>
                                                    <category.icon className="h-6 w-6 text-white" />
                                                </div>
                                                <div className="flex-1">
                                                    <CardTitle className="text-lg">{category.title}</CardTitle>
                                                    <Badge variant="secondary" className="mt-1">
                                                        {category.articles} Artikel
                                                    </Badge>
                                                </div>
                                            </div>
                                        </CardHeader>
                                        <CardContent>
                                            <CardDescription>{category.description}</CardDescription>
                                        </CardContent>
                                    </Card>
                                </Link>
                            ))}
                        </div>
                    </TabsContent>

                    {/* Popular Articles Tab */}
                    <TabsContent value="popular" className="space-y-6">
                        <div className="space-y-4">
                            {filteredArticles.map((article) => (
                                <Card key={article.id} className="hover:shadow-md transition-shadow">
                                    <CardContent className="pt-6">
                                        <div className="flex items-start justify-between">
                                            <div className="flex-1">
                                                <h3 className="font-semibold text-lg mb-2">{article.title}</h3>
                                                <div className="flex items-center space-x-4 text-sm text-muted-foreground">
                                                    <Badge variant="outline">{article.category}</Badge>
                                                    <div className="flex items-center">
                                                        <Star className="h-4 w-4 fill-yellow-400 text-yellow-400 mr-1" />
                                                        {article.rating}
                                                    </div>
                                                    <div className="flex items-center">
                                                        <Clock className="h-4 w-4 mr-1" />
                                                        {new Date(article.lastUpdated).toLocaleDateString("de-DE")}
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="text-right">
                                                <p className="text-sm text-muted-foreground">{article.views} Aufrufe</p>
                                                <Button variant="ghost" size="sm" className="mt-2">
                                                    Lesen <ExternalLink className="ml-2 h-4 w-4" />
                                                </Button>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </TabsContent>

                    {/* FAQ Tab */}
                    <TabsContent value="faq" className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Häufig gestellte Fragen</CardTitle>
                                <CardDescription>Hier finden Sie Antworten auf die am häufigsten gestellten Fragen</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Accordion type="single" collapsible className="w-full">
                                    {faqItems.map((item, index) => (
                                        <AccordionItem key={index} value={`item-${index}`}>
                                            <AccordionTrigger className="text-left">{item.question}</AccordionTrigger>
                                            <AccordionContent className="text-muted-foreground">{item.answer}</AccordionContent>
                                        </AccordionItem>
                                    ))}
                                </Accordion>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Support Tab */}
                    <TabsContent value="support" className="space-y-6">
                        <div className="grid gap-6 md:grid-cols-2">
                            {supportOptions.map((option, index) => (
                                <Card key={index} className={`${!option.available ? "opacity-60" : ""}`}>
                                    <CardHeader>
                                        <div className="flex items-center space-x-3">
                                            <div className="p-2 rounded-lg bg-primary">
                                                <option.icon className="h-6 w-6 text-primary-foreground" />
                                            </div>
                                            <div className="flex-1">
                                                <CardTitle className="text-lg">{option.title}</CardTitle>
                                                {option.available ? (
                                                    <Badge variant="secondary" className="mt-1">
                                                        <CheckCircle className="h-3 w-3 mr-1" />
                                                        Verfügbar
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="outline" className="mt-1">
                                                        <AlertCircle className="h-3 w-3 mr-1" />
                                                        Bald verfügbar
                                                    </Badge>
                                                )}
                                            </div>
                                        </div>
                                    </CardHeader>
                                    <CardContent>
                                        <CardDescription className="mb-4">{option.description}</CardDescription>
                                        <div className="flex items-center justify-between">
                                            <div className="text-sm text-muted-foreground">
                                                <Info className="h-4 w-4 inline mr-1" />
                                                {option.responseTime}
                                            </div>
                                            <Button
                                                variant={option.available ? "default" : "secondary"}
                                                size="sm"
                                                disabled={!option.available}
                                            >
                                                {option.action}
                                            </Button>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>

                        {/* Additional Resources */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Weitere Ressourcen</CardTitle>
                                <CardDescription>Zusätzliche Materialien und Downloads</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="grid gap-4 md:grid-cols-3">
                                    <Button variant="outline" className="h-auto p-4 flex-col bg-transparent">
                                        <Download className="h-8 w-8 mb-2" />
                                        <span className="font-semibold">Benutzerhandbuch</span>
                                        <span className="text-xs text-muted-foreground">PDF Download</span>
                                    </Button>
                                    <Button variant="outline" className="h-auto p-4 flex-col bg-transparent">
                                        <Video className="h-8 w-8 mb-2" />
                                        <span className="font-semibold">Video-Tutorials</span>
                                        <span className="text-xs text-muted-foreground">YouTube Kanal</span>
                                    </Button>
                                    <Button variant="outline" className="h-auto p-4 flex-col bg-transparent">
                                        <BookOpen className="h-8 w-8 mb-2" />
                                        <span className="font-semibold">Changelog</span>
                                        <span className="text-xs text-muted-foreground">Neue Features</span>
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    )
}
