"use client"

import { Head, Link } from "@inertiajs/react"
import { useTranslation } from "react-i18next"
import AppLayout from "@/layouts/app-layout"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion"
import {
    ArrowLeft,
    BookOpen,
    Users,
    ReceiptEuro,
    FileText,
    Settings,
    Package,
    Clock,
    AlertCircle,
    Info,
    Calendar,
    TrendingUp,
    EuroIcon,
    HelpCircle,
} from "lucide-react"

interface HelpShowProps {
    user: any
    category: string
}

export default function HelpShow({
    user, category }: HelpShowProps) {
    const { t } = useTranslation()

    const categoryTitles: Record<string, { title: string; icon: any; color: string }> = {
        "getting-started": { title: t('pages.help.catGettingStarted'), icon: BookOpen, color: "bg-blue-500" },
        customers: { title: t('pages.help.catCustomers'), icon: Users, color: "bg-green-500" },
        invoices: { title: t('pages.help.catInvoices'), icon: ReceiptEuro, color: "bg-purple-500" },
        offers: { title: t('pages.help.catOffers'), icon: FileText, color: "bg-orange-500" },
        products: { title: t('pages.help.catProducts'), icon: Package, color: "bg-indigo-500" },
        payments: { title: t('pages.help.catPayments'), icon: EuroIcon, color: "bg-emerald-500" },
        expenses: { title: t('pages.help.catExpenses'), icon: ReceiptEuro, color: "bg-red-500" },
        reports: { title: t('pages.help.catReports'), icon: TrendingUp, color: "bg-cyan-500" },
        calendar: { title: t('pages.help.catCalendar'), icon: Calendar, color: "bg-pink-500" },
        erechnung: { title: t('pages.help.catErechnung'), icon: FileText, color: "bg-teal-500" },
        reminders: { title: t('pages.help.catReminders'), icon: AlertCircle, color: "bg-amber-500" },
        settings: { title: t('pages.help.catSettings'), icon: Settings, color: "bg-gray-500" },
    }

    const categoryArticles: Record<string, Array<{ id: string; titleKey: string; contentKey: string }>> = {
        "getting-started": Array.from({ length: 15 }, (_, i) => ({ id: String(i + 1), titleKey: `pages.help.gs${i+1}Title`, contentKey: `pages.help.gs${i+1}Content` })),
        customers: Array.from({ length: 12 }, (_, i) => ({ id: String(i + 1), titleKey: `pages.help.cu${i+1}Title`, contentKey: `pages.help.cu${i+1}Content` })),
        invoices: Array.from({ length: 20 }, (_, i) => ({ id: String(i + 1), titleKey: `pages.help.inv${i+1}Title`, contentKey: `pages.help.inv${i+1}Content` })),
        offers: Array.from({ length: 12 }, (_, i) => ({ id: String(i + 1), titleKey: `pages.help.off${i+1}Title`, contentKey: `pages.help.off${i+1}Content` })),
        products: Array.from({ length: 14 }, (_, i) => ({ id: String(i + 1), titleKey: `pages.help.pr${i+1}Title`, contentKey: `pages.help.pr${i+1}Content` })),
        payments: Array.from({ length: 10 }, (_, i) => ({ id: String(i + 1), titleKey: `pages.help.pay${i+1}Title`, contentKey: `pages.help.pay${i+1}Content` })),
        expenses: Array.from({ length: 11 }, (_, i) => ({ id: String(i + 1), titleKey: `pages.help.exp${i+1}Title`, contentKey: `pages.help.exp${i+1}Content` })),
        reports: Array.from({ length: 9 }, (_, i) => ({ id: String(i + 1), titleKey: `pages.help.rep${i+1}Title`, contentKey: `pages.help.rep${i+1}Content` })),
        calendar: Array.from({ length: 7 }, (_, i) => ({ id: String(i + 1), titleKey: `pages.help.cal${i+1}Title`, contentKey: `pages.help.cal${i+1}Content` })),
        erechnung: Array.from({ length: 8 }, (_, i) => ({ id: String(i + 1), titleKey: `pages.help.er${i+1}Title`, contentKey: `pages.help.er${i+1}Content` })),
        reminders: Array.from({ length: 10 }, (_, i) => ({ id: String(i + 1), titleKey: `pages.help.rem${i+1}Title`, contentKey: `pages.help.rem${i+1}Content` })),
        settings: Array.from({ length: 18 }, (_, i) => ({ id: String(i + 1), titleKey: `pages.help.set${i+1}Title`, contentKey: `pages.help.set${i+1}Content` })),
    }

    const articles = categoryArticles[category] || []
    const categoryInfo = categoryTitles[category] || { title: t('pages.help.unknownCategory'), icon: HelpCircle, color: "bg-gray-500" }
    const CategoryIcon = categoryInfo.icon

    return (
        <AppLayout user={user}>
            <Head title={`${categoryInfo.title} - ${t('pages.help.title')}`} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/help">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                {t('pages.help.backToOverview')}
                            </Link>
                        </Button>
                        <div className="flex items-center space-x-3">
                            <div className={`p-2 rounded-lg ${categoryInfo.color}`}>
                                <CategoryIcon className="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <h1 className="text-1xl font-bold tracking-tight dark:text-gray-100">{categoryInfo.title}</h1>
                                <p className="text-muted-foreground">
                                    {articles.length} {t('pages.help.articlesAvailable')}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Articles */}
                {articles.length > 0 ? (
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('pages.help.articles')}</CardTitle>
                            <CardDescription>
                                {t('pages.help.articlesDesc', { category: categoryInfo.title.toLowerCase() })}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Accordion type="single" collapsible className="w-full">
                                {articles.map((article) => (
                                    <AccordionItem key={article.id} value={`article-${article.id}`}>
                                        <AccordionTrigger className="text-left font-semibold">
                                            {t(article.titleKey)}
                                        </AccordionTrigger>
                                        <AccordionContent className="text-muted-foreground whitespace-pre-line">
                                            {t(article.contentKey)}
                                        </AccordionContent>
                                    </AccordionItem>
                                ))}
                            </Accordion>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardContent className="pt-6">
                            <div className="text-center py-8">
                                <Info className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                                <h3 className="text-lg font-semibold mb-2">{t('pages.help.noResults')}</h3>
                                <p className="text-muted-foreground">
                                    {t('pages.help.noArticlesInCategory')}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Quick Actions */}
                <Card>
                    <CardHeader>
                        <CardTitle>{t('common.quickActions')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-wrap gap-2">
                            <Button variant="outline" asChild>
                                <Link href="/help">
                                    <ArrowLeft className="mr-2 h-4 w-4" />
                                    {t('pages.help.backToHelp')}
                                </Link>
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href="/help?tab=faq">
                                    <HelpCircle className="mr-2 h-4 w-4" />
                                    {t('pages.help.showFaq')}
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    )
}
