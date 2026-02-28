"use client"

import { useState } from "react"
import { Head, Link, router, useForm, usePage } from "@inertiajs/react"
import { useTranslation } from "react-i18next"
import { route } from "ziggy-js"
import AppLayout from "@/layouts/app-layout"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/components/ui/dialog"
import {
    ChevronLeft,
    ChevronRight,
    Plus,
    Clock,
    Users,
    EuroIcon,
    FileText,
    AlertTriangle,
    CheckCircle,
    Filter,
    Download,
    Edit,
    Trash2,
    CalendarDays,
} from "lucide-react"
import type { BreadcrumbItem } from "@/types"
import { formatCurrency as formatCurrencyUtil } from "@/utils/formatting"

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
    location?: string
    invoice_id?: string
    offer_id?: string
    recurring?: string
    is_custom?: boolean
    calendar_event_id?: string
}

interface CalendarProps {
    events?: CalendarEvent[]
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Kalender" },
]

export default function CalendarIndex({ events: propEvents = [] }: CalendarProps) {
    const { t } = useTranslation()
    const { auth } = usePage<{ auth: { user: { company?: { settings?: Record<string, string> } } } }>().props
    const settings = auth?.user?.company?.settings
    const [currentDate, setCurrentDate] = useState(new Date())
    const [selectedView, setSelectedView] = useState("month")
    const [selectedFilter, setSelectedFilter] = useState("all")
    const [createDialogOpen, setCreateDialogOpen] = useState(false)
    const [editDialogOpen, setEditDialogOpen] = useState(false)
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false)
    const [selectedEvent, setSelectedEvent] = useState<CalendarEvent | null>(null)

    const { data, setData, post, processing, errors, reset } = useForm({
        title: "",
        type: "appointment",
        date: new Date().toISOString().split("T")[0],
        time: "09:00",
        description: "",
        location: "",
    })

    const editForm = useForm({
        title: "",
        type: "appointment",
        date: "",
        time: "",
        description: "",
        location: "",
    })

    const events = propEvents ?? []

    const eventTypes = {
        invoice_due: {
            label: t('pages.calendar.invoiceDue'),
            color: "bg-red-500",
            icon: EuroIcon,
            textColor: "text-red-700",
        },
        offer_expiry: {
            label: t('pages.calendar.offerExpiring'),
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

    const formatCurrency = (amount: number) => formatCurrencyUtil(amount, settings)

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

    const renderDayCell = (dateString: string, heightClass = "h-24") => {
        const dayEvents = filteredEvents.filter((event) => event.date === dateString)
        const isToday = new Date().toDateString() === new Date(dateString + "T12:00:00").toDateString()
        const dayNum = parseInt(dateString.split("-")[2], 10)

        return (
            <div className={`${heightClass} border border-border p-1 ${isToday ? "bg-blue-50 dark:bg-blue-950/20" : ""}`}>
                <div className={`text-sm font-medium mb-1 ${isToday ? "text-blue-600" : ""}`}>{dayNum}</div>
                <div className="space-y-1">
                    {dayEvents.slice(0, 2).map((event) => {
                        const eventType = eventTypes[event.type as keyof typeof eventTypes]
                        const canEdit = event.is_custom && event.calendar_event_id
                        return (
                            <div
                                key={event.id}
                                className={`text-xs p-1 rounded truncate ${eventType.color} text-white cursor-pointer hover:opacity-80 transition-opacity group/item relative`}
                                title={event.title}
                                onClick={() => {
                                    if (canEdit) {
                                        setSelectedEvent(event)
                                        editForm.setData({
                                            title: event.title,
                                            type: event.type,
                                            date: event.date,
                                            time: event.time,
                                            description: event.description || "",
                                            location: event.location || "",
                                        })
                                        setEditDialogOpen(true)
                                    }
                                }}
                            >
                                {event.title}
                                {canEdit && (
                                    <div className="absolute right-1 top-1/2 -translate-y-1/2 opacity-0 group-hover/item:opacity-100 transition-opacity">
                                        <Edit className="h-2.5 w-2.5 text-white" />
                                    </div>
                                )}
                            </div>
                        )
                    })}
                    {dayEvents.length > 2 && (
                        <div className="text-xs text-muted-foreground">+{dayEvents.length - 2} weitere</div>
                    )}
                </div>
            </div>
        )
    }

    const renderCalendarGrid = () => {
        if (selectedView === "week") {
            // Find the Monday of the current week
            const weekStart = new Date(currentDate)
            const dayOfWeek = weekStart.getDay()
            const diff = dayOfWeek === 0 ? -6 : 1 - dayOfWeek
            weekStart.setDate(weekStart.getDate() + diff)

            const cells = []
            for (let i = 0; i < 7; i++) {
                const d = new Date(weekStart)
                d.setDate(weekStart.getDate() + i)
                const ds = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`
                cells.push(<div key={ds}>{renderDayCell(ds, "h-48")}</div>)
            }
            return cells
        }

        if (selectedView === "day") {
            const ds = `${currentDate.getFullYear()}-${String(currentDate.getMonth() + 1).padStart(2, "0")}-${String(currentDate.getDate()).padStart(2, "0")}`
            return [<div key={ds} className="col-span-7">{renderDayCell(ds, "min-h-64")}</div>]
        }

        // Month view
        const daysInMonth = getDaysInMonth(currentDate)
        const firstDay = getFirstDayOfMonth(currentDate)
        const days = []

        for (let i = 0; i < firstDay; i++) {
            days.push(<div key={`empty-${i}`} className="h-24 border border-border"></div>)
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const ds = `${currentDate.getFullYear()}-${String(currentDate.getMonth() + 1).padStart(2, "0")}-${String(day).padStart(2, "0")}`
            days.push(<div key={day}>{renderDayCell(ds)}</div>)
        }

        return days
    }

    const handleExportIcs = () => {
        const lines = [
            "BEGIN:VCALENDAR",
            "VERSION:2.0",
            "PRODID:-//AndoBill//Calendar//DE",
            "CALSCALE:GREGORIAN",
        ]
        filteredEvents.forEach((ev) => {
            const dtStart = ev.date.replace(/-/g, "") + "T" + ev.time.replace(":", "") + "00"
            lines.push(
                "BEGIN:VEVENT",
                `UID:${ev.id}@andobill`,
                `DTSTART:${dtStart}`,
                `SUMMARY:${ev.title}`,
                ev.description ? `DESCRIPTION:${ev.description}` : "",
                ev.location ? `LOCATION:${ev.location}` : "",
                "END:VEVENT",
            )
        })
        lines.push("END:VCALENDAR")
        const blob = new Blob([lines.filter(Boolean).join("\r\n")], { type: "text/calendar;charset=utf-8;" })
        const url = URL.createObjectURL(blob)
        const a = document.createElement("a")
        a.href = url
        a.download = `kalender-${new Date().toISOString().split("T")[0]}.ics`
        document.body.appendChild(a)
        a.click()
        document.body.removeChild(a)
        URL.revokeObjectURL(url)
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('pages.calendar.title')} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-1xl font-bold tracking-tight dark:text-gray-100">{t('pages.calendar.title')}</h1>
                        <p className="text-muted-foreground">{t('pages.calendar.subtitle')}</p>
                    </div>
                    <div className="flex items-center space-x-2">
                        <Select value={selectedFilter} onValueChange={setSelectedFilter}>
                            <SelectTrigger className="w-40">
                                <Filter className="mr-2 h-4 w-4" />
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Alle Ereignisse</SelectItem>
                                <SelectItem value="invoice_due">{t('nav.invoices')}</SelectItem>
                                <SelectItem value="offer_expiry">{t('nav.offers')}</SelectItem>
                                <SelectItem value="appointment">Termine</SelectItem>
                                <SelectItem value="inventory">Lager</SelectItem>
                                <SelectItem value="report">{t('nav.reports')}</SelectItem>
                            </SelectContent>
                        </Select>
                        <Button onClick={() => setCreateDialogOpen(true)}>
                            <Plus className="mr-2 h-4 w-4" />
                            {t('pages.calendar.addAppointment')}
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
                                        <CardTitle className="text-red-800">{t('pages.dashboard.overdueInvoices')}</CardTitle>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-red-700 mb-3">
                                        {overdueInvoices.length} {t('pages.calendar.overdueInvoiceCount', { count: overdueInvoices.length })}
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
                                        <CardTitle className="text-orange-800">{t('pages.calendar.expiringOffers')}</CardTitle>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-orange-700 mb-3">
                                        {expiringOffers.length} {t('pages.calendar.expiringOfferCount', { count: expiringOffers.length })}
                                    </p>
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href="/offers?status=expiring">{t('pages.calendar.checkOffers')}</Link>
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
                                        onClick={() => {
                                            const d = new Date(currentDate)
                                            if (selectedView === "month") d.setMonth(d.getMonth() - 1)
                                            else if (selectedView === "week") d.setDate(d.getDate() - 7)
                                            else d.setDate(d.getDate() - 1)
                                            setCurrentDate(d)
                                        }}
                                    >
                                        <ChevronLeft className="h-4 w-4" />
                                    </Button>
                                    <h2 className="text-xl font-semibold min-w-48 text-center">
                                        {selectedView === "month" && currentDate.toLocaleDateString("de-DE", { month: "long", year: "numeric" })}
                                        {selectedView === "week" && (() => {
                                            const ws = new Date(currentDate)
                                            const dow = ws.getDay()
                                            ws.setDate(ws.getDate() - (dow === 0 ? 6 : dow - 1))
                                            const we = new Date(ws); we.setDate(ws.getDate() + 6)
                                            return `${ws.toLocaleDateString("de-DE", { day: "numeric", month: "short" })} – ${we.toLocaleDateString("de-DE", { day: "numeric", month: "short", year: "numeric" })}`
                                        })()}
                                        {selectedView === "day" && currentDate.toLocaleDateString("de-DE", { weekday: "long", day: "numeric", month: "long", year: "numeric" })}
                                    </h2>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => {
                                            const d = new Date(currentDate)
                                            if (selectedView === "month") d.setMonth(d.getMonth() + 1)
                                            else if (selectedView === "week") d.setDate(d.getDate() + 7)
                                            else d.setDate(d.getDate() + 1)
                                            setCurrentDate(d)
                                        }}
                                    >
                                        <ChevronRight className="h-4 w-4" />
                                    </Button>
                                    </div>
                                    <div className="flex items-center space-x-2">
                                        <Button variant="outline" size="sm" onClick={() => setCurrentDate(new Date())}>
                                            Heute
                                        </Button>
                                        <div className="flex items-center gap-1 rounded-md border p-1">
                                            {(["month", "week", "day"] as const).map((v) => (
                                                <Button
                                                    key={v}
                                                    variant={selectedView === v ? "default" : "ghost"}
                                                    size="sm"
                                                    className="h-7 px-2 text-xs"
                                                    onClick={() => setSelectedView(v)}
                                                >
                                                    {v === "month" ? "Monat" : v === "week" ? "Woche" : "Tag"}
                                                </Button>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent>
                                {/* Calendar Grid */}
                                <div className="grid grid-cols-7 gap-0 border border-border">
                                    {/* Day headers */}
                                    {selectedView !== "day" && ["Mo", "Di", "Mi", "Do", "Fr", "Sa", "So"].map((day) => (
                                        <div
                                            key={day}
                                            className="h-10 border border-border bg-muted flex items-center justify-center font-medium text-sm"
                                        >
                                            {day}
                                        </div>
                                    ))}
                                    {selectedView === "day" && (
                                        <div className="col-span-7 h-10 border border-border bg-muted flex items-center justify-center font-medium text-sm">
                                            {currentDate.toLocaleDateString("de-DE", { weekday: "long" })}
                                        </div>
                                    )}
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
                                {upcomingEvents.length === 0 && (
                                    <div className="flex flex-col items-center justify-center py-6 text-center text-muted-foreground">
                                        <CalendarDays className="h-8 w-8 mb-2 opacity-40" />
                                        <p className="text-sm">Keine anstehenden Ereignisse</p>
                                    </div>
                                )}
                                {upcomingEvents.map((event) => {
                                    const eventType = eventTypes[event.type as keyof typeof eventTypes]
                                    const EventIcon = eventType.icon
                                    const canEdit = event.is_custom && event.calendar_event_id

                                    return (
                                        <div key={event.id} className="flex items-start space-x-3 p-3 rounded-lg border group">
                                            <div className={`p-1 rounded ${eventType.color}`}>
                                                <EventIcon className="h-4 w-4 text-white" />
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <p className="font-medium text-sm truncate">{event.title}</p>
                                                <p className="text-xs text-muted-foreground">
                                                    {formatDate(event.date)} um {formatTime(event.time)}
                                                </p>
                                                {event.location && <p className="text-xs text-muted-foreground">📍 {event.location}</p>}
                                                {event.customer && <p className="text-xs text-muted-foreground">{event.customer}</p>}
                                                {event.amount && (
                                                    <p className="text-xs font-medium text-green-600">{formatCurrency(event.amount)}</p>
                                                )}
                                            </div>
                                            {canEdit && (
                                                <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="h-7 w-7 p-0"
                                                        onClick={() => {
                                                            setSelectedEvent(event)
                                                            editForm.setData({
                                                                title: event.title,
                                                                type: event.type,
                                                                date: event.date,
                                                                time: event.time,
                                                                description: event.description || "",
                                                                location: event.location || "",
                                                            })
                                                            setEditDialogOpen(true)
                                                        }}
                                                    >
                                                        <Edit className="h-3.5 w-3.5" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="h-7 w-7 p-0 text-destructive hover:text-destructive"
                                                        onClick={() => {
                                                            setSelectedEvent(event)
                                                            setDeleteDialogOpen(true)
                                                        }}
                                                    >
                                                        <Trash2 className="h-3.5 w-3.5" />
                                                    </Button>
                                                </div>
                                            )}
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
                                        <EuroIcon className="mr-2 h-4 w-4" />
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
                                <Button variant="outline" size="sm" className="w-full justify-start bg-transparent" onClick={handleExportIcs}>
                                    <Download className="mr-2 h-4 w-4" />
                                    Kalender exportieren (.ics)
                                </Button>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                {/* Create Event Dialog */}
                <Dialog open={createDialogOpen} onOpenChange={setCreateDialogOpen}>
                    <DialogContent className="sm:max-w-[500px]">
                        <DialogHeader>
                            <DialogTitle>Neuen Termin erstellen</DialogTitle>
                            <DialogDescription>
                                Erstellen Sie einen neuen Termin oder Ereignis im Kalender
                            </DialogDescription>
                        </DialogHeader>
                        <form
                            onSubmit={(e) => {
                                e.preventDefault()
                                post(route("calendar.store"), {
                                    onSuccess: () => {
                                        reset()
                                        setCreateDialogOpen(false)
                                        router.reload({ only: ['events'] })
                                    },
                                })
                            }}
                            className="space-y-4"
                        >
                            <div className="space-y-2">
                                <Label htmlFor="title">Titel *</Label>
                                <Input
                                    id="title"
                                    value={data.title}
                                    onChange={(e) => setData("title", e.target.value)}
                                    placeholder="z.B. Kundenbesuch, Meeting, etc."
                                    required
                                />
                                {errors.title && <p className="text-sm text-red-600">{errors.title}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="type">Typ *</Label>
                                <Select value={data.type} onValueChange={(value) => setData("type", value)}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="appointment">Termin</SelectItem>
                                        <SelectItem value="report">Bericht</SelectItem>
                                        <SelectItem value="inventory">Lager</SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.type && <p className="text-sm text-red-600">{errors.type}</p>}
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="date">Datum *</Label>
                                    <Input
                                        id="date"
                                        type="date"
                                        value={data.date}
                                        onChange={(e) => setData("date", e.target.value)}
                                        required
                                    />
                                    {errors.date && <p className="text-sm text-red-600">{errors.date}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="time">Uhrzeit *</Label>
                                    <Input
                                        id="time"
                                        type="time"
                                        value={data.time}
                                        onChange={(e) => setData("time", e.target.value)}
                                        required
                                    />
                                    {errors.time && <p className="text-sm text-red-600">{errors.time}</p>}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="location">Ort</Label>
                                <Input
                                    id="location"
                                    value={data.location}
                                    onChange={(e) => setData("location", e.target.value)}
                                    placeholder={t('pages.calendar.locationPlaceholder')}
                                />
                                {errors.location && <p className="text-sm text-red-600">{errors.location}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="description">{t('common.description')}</Label>
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData("description", e.target.value)}
                                    placeholder={t('pages.calendar.descriptionPlaceholder')}
                                    rows={3}
                                />
                                {errors.description && <p className="text-sm text-red-600">{errors.description}</p>}
                            </div>

                            <div className="flex justify-end gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => {
                                        reset()
                                        setCreateDialogOpen(false)
                                    }}
                                >
                                    {t('common.cancel')}
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing ? "Wird erstellt..." : "Termin erstellen"}
                                </Button>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>

                {/* Edit Event Dialog */}
                <Dialog open={editDialogOpen} onOpenChange={setEditDialogOpen}>
                    <DialogContent className="sm:max-w-[500px]">
                        <DialogHeader>
                            <DialogTitle>Termin bearbeiten</DialogTitle>
                            <DialogDescription>
                                Bearbeiten Sie die Termindetails
                            </DialogDescription>
                        </DialogHeader>
                        {selectedEvent && (
                            <form
                                onSubmit={(e) => {
                                    e.preventDefault()
                                    editForm.put(route("calendar.update", selectedEvent.calendar_event_id || selectedEvent.id), {
                                        onSuccess: () => {
                                            setEditDialogOpen(false)
                                            setSelectedEvent(null)
                                            router.reload({ only: ['events'] })
                                        },
                                    })
                                }}
                                className="space-y-4"
                            >
                                <div className="space-y-2">
                                    <Label htmlFor="edit-title">Titel *</Label>
                                    <Input
                                        id="edit-title"
                                        value={editForm.data.title}
                                        onChange={(e) => editForm.setData("title", e.target.value)}
                                        placeholder="z.B. Kundenbesuch, Meeting, etc."
                                        required
                                    />
                                    {editForm.errors.title && <p className="text-sm text-red-600">{editForm.errors.title}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="edit-type">Typ *</Label>
                                    <Select value={editForm.data.type} onValueChange={(value) => editForm.setData("type", value)}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="appointment">Termin</SelectItem>
                                            <SelectItem value="report">Bericht</SelectItem>
                                            <SelectItem value="inventory">Lager</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {editForm.errors.type && <p className="text-sm text-red-600">{editForm.errors.type}</p>}
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="edit-date">Datum *</Label>
                                        <Input
                                            id="edit-date"
                                            type="date"
                                            value={editForm.data.date}
                                            onChange={(e) => editForm.setData("date", e.target.value)}
                                            required
                                        />
                                        {editForm.errors.date && <p className="text-sm text-red-600">{editForm.errors.date}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="edit-time">Uhrzeit *</Label>
                                        <Input
                                            id="edit-time"
                                            type="time"
                                            value={editForm.data.time}
                                            onChange={(e) => editForm.setData("time", e.target.value)}
                                            required
                                        />
                                        {editForm.errors.time && <p className="text-sm text-red-600">{editForm.errors.time}</p>}
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="edit-location">Ort</Label>
                                    <Input
                                        id="edit-location"
                                        value={editForm.data.location}
                                        onChange={(e) => editForm.setData("location", e.target.value)}
                                        placeholder={t('pages.calendar.locationPlaceholder')}
                                    />
                                    {editForm.errors.location && <p className="text-sm text-red-600">{editForm.errors.location}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="edit-description">{t('common.description')}</Label>
                                    <Textarea
                                        id="edit-description"
                                        value={editForm.data.description}
                                        onChange={(e) => editForm.setData("description", e.target.value)}
                                        placeholder={t('pages.calendar.descriptionPlaceholder')}
                                        rows={3}
                                    />
                                    {editForm.errors.description && <p className="text-sm text-red-600">{editForm.errors.description}</p>}
                                </div>

                                <div className="flex justify-end gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => {
                                            setEditDialogOpen(false)
                                            setSelectedEvent(null)
                                        }}
                                    >
                                        {t('common.cancel')}
                                    </Button>
                                    <Button type="submit" disabled={editForm.processing}>
                                        {editForm.processing ? "Wird gespeichert..." : "Speichern"}
                                    </Button>
                                </div>
                            </form>
                        )}
                    </DialogContent>
                </Dialog>

                {/* Delete Confirmation Dialog */}
                <Dialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
                    <DialogContent className="sm:max-w-[400px]">
                        <DialogHeader>
                            <DialogTitle>{t('pages.calendar.deleteAppointment')}</DialogTitle>
                            <DialogDescription>
                                {t('pages.calendar.deleteConfirmText')}
                            </DialogDescription>
                        </DialogHeader>
                        {selectedEvent && (
                            <div className="space-y-4">
                                <div className="p-3 bg-muted rounded-lg">
                                    <p className="font-medium">{selectedEvent.title}</p>
                                    <p className="text-sm text-muted-foreground">
                                        {formatDate(selectedEvent.date)} um {formatTime(selectedEvent.time)}
                                    </p>
                                </div>
                                <div className="flex justify-end gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => {
                                            setDeleteDialogOpen(false)
                                            setSelectedEvent(null)
                                        }}
                                    >
                                        {t('common.cancel')}
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        onClick={() => {
                                            router.delete(route("calendar.destroy", selectedEvent.calendar_event_id || selectedEvent.id), {
                                                onSuccess: () => {
                                                    setDeleteDialogOpen(false)
                                                    setSelectedEvent(null)
                                                    router.reload({ only: ['events'] })
                                                },
                                            })
                                        }}
                                    >
                                        {t('common.delete')}
                                    </Button>
                                </div>
                            </div>
                        )}
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    )
}
