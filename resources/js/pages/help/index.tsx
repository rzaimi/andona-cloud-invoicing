"use client"

import { useState } from "react"
import { Head, Link } from "@inertiajs/react"
import { useTranslation } from "react-i18next"
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

export default function HelpIndex({
    user, stats }: HelpProps) {
    const { t } = useTranslation()
    const [searchQuery, setSearchQuery] = useState("")

    const helpCategories = [
        { id: "getting-started", title: t('pages.help.catGettingStarted'), description: t('pages.help.catGettingStartedDesc'), icon: BookOpen, color: "bg-blue-500", articles: 15 },
        { id: "customers", title: t('pages.help.catCustomers'), description: t('pages.help.catCustomersDesc'), icon: Users, color: "bg-green-500", articles: 12 },
        { id: "invoices", title: t('pages.help.catInvoices'), description: t('pages.help.catInvoicesDesc'), icon: ReceiptEuro, color: "bg-purple-500", articles: 20 },
        { id: "offers", title: t('pages.help.catOffers'), description: t('pages.help.catOffersDesc'), icon: FileText, color: "bg-orange-500", articles: 12 },
        { id: "products", title: t('pages.help.catProducts'), description: t('pages.help.catProductsDesc'), icon: Package, color: "bg-indigo-500", articles: 14 },
        { id: "payments", title: t('pages.help.catPayments'), description: t('pages.help.catPaymentsDesc'), icon: ReceiptEuro, color: "bg-emerald-500", articles: 10 },
        { id: "expenses", title: t('pages.help.catExpenses'), description: t('pages.help.catExpensesDesc'), icon: ReceiptEuro, color: "bg-red-500", articles: 11 },
        { id: "reports", title: t('pages.help.catReports'), description: t('pages.help.catReportsDesc'), icon: FileText, color: "bg-cyan-500", articles: 9 },
        { id: "calendar", title: t('pages.help.catCalendar'), description: t('pages.help.catCalendarDesc'), icon: Clock, color: "bg-pink-500", articles: 7 },
        { id: "erechnung", title: t('pages.help.catErechnung'), description: t('pages.help.catErechnungDesc'), icon: FileText, color: "bg-teal-500", articles: 8 },
        { id: "reminders", title: t('pages.help.catReminders'), description: t('pages.help.catRemindersDesc'), icon: AlertCircle, color: "bg-amber-500", articles: 10 },
        { id: "settings", title: t('pages.help.catSettings'), description: t('pages.help.catSettingsDesc'), icon: Settings, color: "bg-gray-500", articles: 18 },
    ]

    const popularArticles = [
        { id: 1, title: t('pages.help.art1Title'), category: t('pages.help.catInvoices'), views: 1250, rating: 4.8, lastUpdated: "2024-01-15" },
        { id: 2, title: t('pages.help.art2Title'), category: t('pages.help.catCustomers'), views: 1100, rating: 4.7, lastUpdated: "2024-01-16" },
        { id: 3, title: t('pages.help.art3Title'), category: t('pages.help.catOffers'), views: 950, rating: 4.9, lastUpdated: "2024-01-12" },
        { id: 4, title: t('pages.help.art4Title'), category: t('pages.help.catSettings'), views: 890, rating: 4.6, lastUpdated: "2024-01-08" },
        { id: 5, title: t('pages.help.art5Title'), category: t('pages.help.catPayments'), views: 850, rating: 4.8, lastUpdated: "2024-01-14" },
        { id: 6, title: t('pages.help.art6Title'), category: t('pages.help.catExpenses'), views: 780, rating: 4.7, lastUpdated: "2024-01-13" },
        { id: 7, title: t('pages.help.art7Title'), category: t('pages.help.catReminders'), views: 720, rating: 4.9, lastUpdated: "2024-01-11" },
        { id: 8, title: t('pages.help.art8Title'), category: t('pages.help.catErechnung'), views: 680, rating: 4.6, lastUpdated: "2024-01-10" },
        { id: 9, title: t('pages.help.art9Title'), category: t('pages.help.catInvoices'), views: 650, rating: 4.8, lastUpdated: "2024-01-09" },
        { id: 10, title: t('pages.help.art10Title'), category: t('pages.help.catProducts'), views: 620, rating: 4.7, lastUpdated: "2024-01-14" },
        { id: 11, title: t('pages.help.art11Title'), category: t('pages.help.catReports'), views: 580, rating: 4.6, lastUpdated: "2024-01-12" },
        { id: 12, title: t('pages.help.art12Title'), category: t('pages.help.catSettings'), views: 550, rating: 4.5, lastUpdated: "2024-01-08" },
    ]

    const faqItems = [
        { question: t('pages.help.faq1Q'), answer: t('pages.help.faq1A') },
        { question: t('pages.help.faq2Q'), answer: t('pages.help.faq2A') },
        { question: t('pages.help.faq3Q'), answer: t('pages.help.faq3A') },
        { question: t('pages.help.faq4Q'), answer: t('pages.help.faq4A') },
        { question: t('pages.help.faq5Q'), answer: t('pages.help.faq5A') },
        { question: t('pages.help.faq6Q'), answer: t('pages.help.faq6A') },
        { question: t('pages.help.faq7Q'), answer: t('pages.help.faq7A') },
        { question: t('pages.help.faq8Q'), answer: t('pages.help.faq8A') },
        { question: t('pages.help.faq9Q'), answer: t('pages.help.faq9A') },
        { question: t('pages.help.faq10Q'), answer: t('pages.help.faq10A') },
        { question: t('pages.help.faq11Q'), answer: t('pages.help.faq11A') },
        { question: t('pages.help.faq12Q'), answer: t('pages.help.faq12A') },
        { question: t('pages.help.faq13Q'), answer: t('pages.help.faq13A') },
        { question: t('pages.help.faq14Q'), answer: t('pages.help.faq14A') },
        { question: t('pages.help.faq15Q'), answer: t('pages.help.faq15A') },
        { question: t('pages.help.faq16Q'), answer: t('pages.help.faq16A') },
    ]

    const supportOptions = [
        { title: t('pages.help.supportEmailTitle'), description: t('pages.help.supportEmailDesc'), icon: Mail, action: "support@rechnungssystem.de", responseTime: t('pages.help.supportEmailResponse'), available: true },
        { title: t('pages.help.supportPhoneTitle'), description: t('pages.help.supportPhoneDesc'), icon: Phone, action: "+49 (0) 123 456 789", responseTime: t('pages.help.supportPhoneResponse'), available: true },
        { title: t('pages.help.supportChatTitle'), description: t('pages.help.supportChatDesc'), icon: MessageCircle, action: t('pages.help.supportChatAction'), responseTime: t('pages.help.supportChatResponse'), available: false },
        { title: t('pages.help.supportVideoTitle'), description: t('pages.help.supportVideoDesc'), icon: Video, action: t('pages.help.supportVideoAction'), responseTime: t('pages.help.supportVideoResponse'), available: true },
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
            <Head title={t('pages.help.title')} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-1xl font-bold tracking-tight dark:text-gray-100">{t('pages.help.title')}</h1>
                        <p className="text-muted-foreground">
                            {t('pages.help.subtitle2')}
                        </p>
                    </div>
                    <Button asChild>
                        <Link href="/help/contact">
                            <HelpCircle className="mr-2 h-4 w-4" />
                            {t('pages.help.contact')}
                        </Link>
                    </Button>
                </div>

                {/* Search */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                placeholder={t('pages.help.searchArticles')}
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="pl-10"
                            />
                        </div>
                    </CardContent>
                </Card>

                <Tabs defaultValue="categories" className="space-y-6">
                    <TabsList className="grid w-full grid-cols-4">
                        <TabsTrigger value="categories">{t('pages.help.categories')}</TabsTrigger>
                        <TabsTrigger value="popular">{t('pages.help.popularArticles')}</TabsTrigger>
                        <TabsTrigger value="faq">{t('pages.help.faq')}</TabsTrigger>
                        <TabsTrigger value="support">{t('pages.help.support')}</TabsTrigger>
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
                                                        {category.articles} {t('pages.help.articles')}
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
                                                <p className="text-sm text-muted-foreground">{article.views} {t('pages.help.views')}</p>
                                                <Button variant="ghost" size="sm" className="mt-2">
                                                    {t('pages.help.read')} <ExternalLink className="ml-2 h-4 w-4" />
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
                                <CardTitle>{t('pages.help.faq')}</CardTitle>
                                <CardDescription>{t('pages.help.faqDesc')}</CardDescription>
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
                                                        {t('pages.help.available')}
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="outline" className="mt-1">
                                                        <AlertCircle className="h-3 w-3 mr-1" />
                                                        {t('pages.help.comingSoon')}
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
                                <CardTitle>{t('pages.help.furtherResources')}</CardTitle>
                                <CardDescription>{t('pages.help.additionalMaterials')}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="grid gap-4 md:grid-cols-3">
                                    <Button variant="outline" className="h-auto p-4 flex-col bg-transparent">
                                        <Download className="h-8 w-8 mb-2" />
                                        <span className="font-semibold">{t('pages.help.userGuide')}</span>
                                        <span className="text-xs text-muted-foreground">PDF Download</span>
                                    </Button>
                                    <Button variant="outline" className="h-auto p-4 flex-col bg-transparent">
                                        <Video className="h-8 w-8 mb-2" />
                                        <span className="font-semibold">{t('pages.help.videoTutorials')}</span>
                                        <span className="text-xs text-muted-foreground">{t('pages.help.youtubeChannel')}</span>
                                    </Button>
                                    <Button variant="outline" className="h-auto p-4 flex-col bg-transparent">
                                        <BookOpen className="h-8 w-8 mb-2" />
                                        <span className="font-semibold">Changelog</span>
                                        <span className="text-xs text-muted-foreground">{t('pages.help.newFeatures')}</span>
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
