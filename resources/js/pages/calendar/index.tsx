"use client"

import { useState } from "react"
import { Head, Link } from "@inertiajs/react"
import AppLayout from "@/layouts/app-layout"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import {
    ChevronLeft,
    ChevronRight,
    Plus,
    Clock,
    Users,
    Receipt,
    FileText,
    AlertTriangle,
    CheckCircle,
    Filter,
    Download,
} from "lucide-react"

interface CalendarEvent {
    id: string
    title: string
    type: string
    date: string
    time: string
    customer?: string
    amount?: number
    status?: string
    description?: string
    invoice_id?: string
    offer_id?: string
    recurring?: string
}

interface CalendarProps {
    user: any
    stats: any
    events?: CalendarEvent[]
}

export default function CalendarIndex({ user, stats, events: propEvents = [] }: CalendarProps) {
    const [currentDate, setCurrentDate] = useState(new Date())
    const [selectedView, setSelectedView] = useState("month")
    const [selectedFilter, setSelectedFilter] = useState("all")

    // Use events from props, fallback to empty array
    const events = propEvents.length > 0 ? propEvents : []

    const eventTypes = {
        invoice_due: {
            label: "Rechnung fällig",
            color: "bg-red-500",
            icon: Receipt,
            textColor: "text-red-700",
        },
        offer_expiry: {
            label: "Angebot läuft ab",
            color: "bg-orange-500",
            icon: FileText,
            textColor: "text-orange-700",
        },
        appointment: {
            label: "Termin",
            color: "bg-blue-500",
            icon: Users,
            textColor: "text-blue-700",
        },
        inventory: {
            label: "Lager",
            color: "bg-purple-500",
            icon: AlertTriangle,
            textColor: "text-purple-700",
        },
        report: {
            label: "Bericht",
            color: "bg-green-500",
            icon: CheckCircle,
            textColor: "text-green-700",
        },
    }

    // Filter events based on selected filter
    const filteredEvents = selectedFilter === "all" 
        ? events 
        : events.filter((event) => event.type === selectedFilter)

    const upcomingEvents = filteredEvents
        .filter((event) => {
            const eventDate = new Date(event.date)
            const today = new Date()
            today.setHours(0, 0, 0, 0)
            return eventDate >= today
        })
        .sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime())
        .slice(0, 5)

    const overdueInvoices = filteredEvents.filter((event) => event.type === "invoice_due" && event.status === "overdue")

    const expiringOffers = filteredEvents.filter((event) => event.type === "offer_expiry" && (event.status === "expiring" || event.status === "expired"))

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat("de-DE", {
            style: "currency",
            currency: "EUR",
        }).format(amount)
    }

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString("de-DE", {
            weekday: "long",
            year: "numeric",
            month: "long",
            day: "numeric",
        })
    }

    const formatTime = (timeString: string) => {
        return timeString
    }

    const getDaysInMonth = (date: Date) => {
        return new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate()
    }

    const getFirstDayOfMonth = (date: Date) => {
        const firstDay = new Date(date.getFullYear(), date.getMonth(), 1).getDay()
        return firstDay === 0 ? 6 : firstDay - 1 // Convert Sunday (0) to 6, Monday (1) to 0, etc.
    }

    const renderCalendarGrid = () => {
        const daysInMonth = getDaysInMonth(currentDate)
        const firstDay = getFirstDayOfMonth(currentDate)
        const days = []

        // Empty cells for days before the first day of the month
        for (let i = 0; i < firstDay; i++) {
            days.push(<div key={`empty-${i}`} className="h-24 border border-border"></div>)
        }

        // Days of the month
        for (let day = 1; day <= daysInMonth; day++) {
            const dateString = `${currentDate.getFullYear()}-${String(currentDate.getMonth() + 1).padStart(2, "0")}-${String(day).padStart(2, "0")}`
            const dayEvents = filteredEvents.filter((event) => event.date === dateString)
            const isToday = new Date().toDateString() === new Date(dateString).toDateString()

            days.push(
                <div key={day} className={`h-24 border border-border p-1 ${isToday ? "bg-blue-50" : ""}`}>
                    <div className={`text-sm font-medium mb-1 ${isToday ? "text-blue-600" : ""}`}>{day}</div>
                    <div className="space-y-1">
                        {dayEvents.slice(0, 2).map((event) => {
                            const eventType = eventTypes[event.type as keyof typeof eventTypes]
                            return (
                                <div
                                    key={event.id}
                                    className={`text-xs p-1 rounded truncate ${eventType.color} text-white`}
                                    title={event.title}
                                >
                                    {event.title}
                                </div>
                            )
                        })}
                        {dayEvents.length > 2 && (
                            <div className="text-xs text-muted-foreground">+{dayEvents.length - 2} weitere</div>
                        )}
                    </div>
                </div>,
            )
        }

        return days
    }

    return (
        <AppLayout user={user}>
            <Head title="Kalender" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Kalender</h1>
                        <p className="text-muted-foreground">Termine, Fristen und wichtige Ereignisse im Überblick</p>
                    </div>
                    <div className="flex items-center space-x-2">
                        <Select value={selectedFilter} onValueChange={setSelectedFilter}>
                            <SelectTrigger className="w-40">
                                <Filter className="mr-2 h-4 w-4" />
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Alle Ereignisse</SelectItem>
                                <SelectItem value="invoice_due">Rechnungen</SelectItem>
                                <SelectItem value="offer_expiry">Angebote</SelectItem>
                                <SelectItem value="appointment">Termine</SelectItem>
                                <SelectItem value="inventory">Lager</SelectItem>
                                <SelectItem value="report">Berichte</SelectItem>
                            </SelectContent>
                        </Select>
                        <Button>
                            <Plus className="mr-2 h-4 w-4" />
                            Termin hinzufügen
                        </Button>
                    </div>
                </div>

                {/* Alert Cards */}
                {(overdueInvoices.length > 0 || expiringOffers.length > 0) && (
                    <div className="grid gap-4 md:grid-cols-2">
                        {overdueInvoices.length > 0 && (
                            <Card className="border-red-200 bg-red-50">
                                <CardHeader className="pb-3">
                                    <div className="flex items-center space-x-2">
                                        <AlertTriangle className="h-5 w-5 text-red-600" />
                                        <CardTitle className="text-red-800">Überfällige Rechnungen</CardTitle>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-red-700 mb-3">
                                        {overdueInvoices.length} Rechnung{overdueInvoices.length !== 1 ? "en" : ""} überfällig
                                    </p>
                                    <Button variant="destructive" size="sm" asChild>
                                        <Link href="/invoices?status=overdue">Jetzt bearbeiten</Link>
                                    </Button>
                                </CardContent>
                            </Card>
                        )}

                        {expiringOffers.length > 0 && (
                            <Card className="border-orange-200 bg-orange-50">
                                <CardHeader className="pb-3">
                                    <div className="flex items-center space-x-2">
                                        <Clock className="h-5 w-5 text-orange-600" />
                                        <CardTitle className="text-orange-800">Ablaufende Angebote</CardTitle>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-orange-700 mb-3">
                                        {expiringOffers.length} Angebot{expiringOffers.length !== 1 ? "e" : ""} läuft bald ab
                                    </p>
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href="/offers?status=expiring">Angebote prüfen</Link>
                                    </Button>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                )}

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Calendar */}
                    <div className="lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center space-x-4">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => setCurrentDate(new Date(currentDate.getFullYear(), currentDate.getMonth() - 1))}
                                        >
                                            <ChevronLeft className="h-4 w-4" />
                                        </Button>
                                        <h2 className="text-xl font-semibold">
                                            {currentDate.toLocaleDateString("de-DE", { month: "long", year: "numeric" })}
                                        </h2>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => setCurrentDate(new Date(currentDate.getFullYear(), currentDate.getMonth() + 1))}
                                        >
                                            <ChevronRight className="h-4 w-4" />
                                        </Button>
                                    </div>
                                    <div className="flex items-center space-x-2">
                                        <Button variant="outline" size="sm" onClick={() => setCurrentDate(new Date())}>
                                            Heute
                                        </Button>
                                        <Select value={selectedView} onValueChange={setSelectedView}>
                                            <SelectTrigger className="w-32">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="month">Monat</SelectItem>
                                                <SelectItem value="week">Woche</SelectItem>
                                                <SelectItem value="day">Tag</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent>
                                {/* Calendar Grid */}
                                <div className="grid grid-cols-7 gap-0 border border-border">
                                    {/* Day headers */}
                                    {["Mo", "Di", "Mi", "Do", "Fr", "Sa", "So"].map((day) => (
                                        <div
                                            key={day}
                                            className="h-10 border border-border bg-muted flex items-center justify-center font-medium text-sm"
                                        >
                                            {day}
                                        </div>
                                    ))}
                                    {/* Calendar days */}
                                    {renderCalendarGrid()}
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-6">
                        {/* Upcoming Events */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center">
                                    <Clock className="mr-2 h-5 w-5" />
                                    Anstehende Ereignisse
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {upcomingEvents.map((event) => {
                                    const eventType = eventTypes[event.type as keyof typeof eventTypes]
                                    const EventIcon = eventType.icon

                                    return (
                                        <div key={event.id} className="flex items-start space-x-3 p-3 rounded-lg border">
                                            <div className={`p-1 rounded ${eventType.color}`}>
                                                <EventIcon className="h-4 w-4 text-white" />
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <p className="font-medium text-sm truncate">{event.title}</p>
                                                <p className="text-xs text-muted-foreground">
                                                    {formatDate(event.date)} um {formatTime(event.time)}
                                                </p>
                                                {event.customer && <p className="text-xs text-muted-foreground">{event.customer}</p>}
                                                {event.amount && (
                                                    <p className="text-xs font-medium text-green-600">{formatCurrency(event.amount)}</p>
                                                )}
                                            </div>
                                        </div>
                                    )
                                })}
                            </CardContent>
                        </Card>

                        {/* Event Legend */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Ereignistypen</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {Object.entries(eventTypes).map(([key, type]) => {
                                    const TypeIcon = type.icon
                                    return (
                                        <div key={key} className="flex items-center space-x-3">
                                            <div className={`p-1 rounded ${type.color}`}>
                                                <TypeIcon className="h-4 w-4 text-white" />
                                            </div>
                                            <span className="text-sm">{type.label}</span>
                                        </div>
                                    )
                                })}
                            </CardContent>
                        </Card>

                        {/* Quick Actions */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Schnellaktionen</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                <Button variant="outline" size="sm" className="w-full justify-start bg-transparent" asChild>
                                    <Link href="/invoices/create">
                                        <Receipt className="mr-2 h-4 w-4" />
                                        Neue Rechnung
                                    </Link>
                                </Button>
                                <Button variant="outline" size="sm" className="w-full justify-start bg-transparent" asChild>
                                    <Link href="/offers/create">
                                        <FileText className="mr-2 h-4 w-4" />
                                        Neues Angebot
                                    </Link>
                                </Button>
                                <Button variant="outline" size="sm" className="w-full justify-start bg-transparent" asChild>
                                    <Link href="/customers/create">
                                        <Users className="mr-2 h-4 w-4" />
                                        Neuer Kunde
                                    </Link>
                                </Button>
                                <Button variant="outline" size="sm" className="w-full justify-start bg-transparent">
                                    <Download className="mr-2 h-4 w-4" />
                                    Kalender exportieren
                                </Button>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    )
}
