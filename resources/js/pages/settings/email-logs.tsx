import AppLayout from "@/layouts/app-layout"
import { BreadcrumbItem } from "@/types"
import { useState } from "react"
import { router, usePage } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Badge } from "@/components/ui/badge"
import { Mail, Search, FileText, AlertTriangle, CheckCircle, Clock, Filter } from "lucide-react"
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/components/ui/dialog"

interface EmailLog {
    id: string
    recipient_email: string
    recipient_name?: string
    subject: string
    body?: string
    type: string
    type_name: string
    status: string
    sent_at: string
    customer?: {
        id: string
        name: string
        email: string
    }
    metadata?: any
}

interface EmailLogsProps {
    logs: {
        data: EmailLog[]
        links: any[]
    }
    filters: {
        type?: string
        status?: string
        search?: string
    }
    stats: {
        total: number
        invoice: number
        offer: number
        mahnung: number
        failed: number
    }
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Einstellungen", href: "/settings" },
    { title: "E-Mail-Verlauf" },
]

export default function EmailLogs() {
    const { logs, filters, stats } = usePage<EmailLogsProps>().props
    const [search, setSearch] = useState(filters.search || "")
    const [type, setType] = useState(filters.type || "all")
    const [status, setStatus] = useState(filters.status || "all")
    const [selectedLog, setSelectedLog] = useState<EmailLog | null>(null)
    const [detailsOpen, setDetailsOpen] = useState(false)

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault()
        const params: any = {}
        if (search) params.search = search
        if (type && type !== 'all') params.type = type
        if (status && status !== 'all') params.status = status
        router.get("/settings/email-logs", params, { preserveScroll: true })
    }

    const handleTypeChange = (newType: string) => {
        setType(newType)
        const params: any = {}
        if (search) params.search = search
        if (newType && newType !== 'all') params.type = newType
        if (status && status !== 'all') params.status = status
        router.get("/settings/email-logs", params, { preserveScroll: true })
    }

    const handleStatusChange = (newStatus: string) => {
        setStatus(newStatus)
        const params: any = {}
        if (search) params.search = search
        if (type && type !== 'all') params.type = type
        if (newStatus && newStatus !== 'all') params.status = newStatus
        router.get("/settings/email-logs", params, { preserveScroll: true })
    }

    const getTypeBadge = (log: EmailLog) => {
        const badges: Record<string, { label: string; color: string }> = {
            invoice: { label: "Rechnung", color: "bg-blue-100 text-blue-800" },
            offer: { label: "Angebot", color: "bg-purple-100 text-purple-800" },
            mahnung: { label: "Mahnung", color: "bg-orange-100 text-orange-800" },
            reminder: { label: "Erinnerung", color: "bg-yellow-100 text-yellow-800" },
            payment_received: { label: "Zahlung", color: "bg-green-100 text-green-800" },
        }
        const badge = badges[log.type] || { label: log.type_name, color: "bg-gray-100 text-gray-800" }
        return <Badge className={badge.color}>{badge.label}</Badge>
    }

    const getStatusBadge = (status: string) => {
        return status === 'sent' ? (
            <Badge className="bg-green-100 text-green-800 flex items-center gap-1">
                <CheckCircle className="h-3 w-3" />
                Versendet
            </Badge>
        ) : (
            <Badge className="bg-red-100 text-red-800 flex items-center gap-1">
                <AlertTriangle className="h-3 w-3" />
                Fehlgeschlagen
            </Badge>
        )
    }

    const viewDetails = (log: EmailLog) => {
        setSelectedLog(log)
        setDetailsOpen(true)
    }

    return (
        <AppLayout title="E-Mail-Verlauf" breadcrumbs={breadcrumbs}>
            <div className="space-y-6">
                <div>
                    <h1 className="text-3xl font-bold flex items-center gap-2">
                        <Mail className="h-8 w-8" />
                        E-Mail-Verlauf
                    </h1>
                    <p className="text-muted-foreground">
                        Alle an Kunden gesendeten E-Mails anzeigen und verwalten
                    </p>
                </div>

                {/* Statistics Cards */}
                <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-medium">Gesamt</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-medium">Rechnungen</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-blue-600">{stats.invoice}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-medium">Angebote</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-purple-600">{stats.offer}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-medium">Mahnungen</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-orange-600">{stats.mahnung}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-medium">Fehlgeschlagen</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-red-600">{stats.failed}</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle>Filter</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSearch} className="flex flex-col md:flex-row gap-4">
                            <div className="flex-1 relative">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                                <Input
                                    placeholder="E-Mail, Empfänger oder Betreff suchen..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-10"
                                />
                            </div>
                            <Select value={type} onValueChange={handleTypeChange}>
                                <SelectTrigger className="w-full md:w-[180px]">
                                    <SelectValue placeholder="Typ" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Alle Typen</SelectItem>
                                    <SelectItem value="invoice">Rechnung</SelectItem>
                                    <SelectItem value="offer">Angebot</SelectItem>
                                    <SelectItem value="mahnung">Mahnung</SelectItem>
                                    <SelectItem value="reminder">Erinnerung</SelectItem>
                                </SelectContent>
                            </Select>
                            <Select value={status} onValueChange={handleStatusChange}>
                                <SelectTrigger className="w-full md:w-[180px]">
                                    <SelectValue placeholder="Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Alle Status</SelectItem>
                                    <SelectItem value="sent">Versendet</SelectItem>
                                    <SelectItem value="failed">Fehlgeschlagen</SelectItem>
                                </SelectContent>
                            </Select>
                            <Button type="submit">
                                <Filter className="mr-2 h-4 w-4" />
                                Filtern
                            </Button>
                            {(filters.search || filters.type || filters.status) && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => {
                                        setSearch("")
                                        setType("all")
                                        setStatus("all")
                                        router.get("/settings/email-logs")
                                    }}
                                >
                                    Zurücksetzen
                                </Button>
                            )}
                        </form>
                    </CardContent>
                </Card>

                {/* Email Logs Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>E-Mails ({logs.data.length})</CardTitle>
                        <CardDescription>Alle gesendeten E-Mails</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Datum</TableHead>
                                    <TableHead>Typ</TableHead>
                                    <TableHead>Empfänger</TableHead>
                                    <TableHead>Betreff</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Aktion</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {logs.data.map((log) => (
                                    <TableRow key={log.id}>
                                        <TableCell>
                                            {new Date(log.sent_at).toLocaleString("de-DE", {
                                                day: "2-digit",
                                                month: "2-digit",
                                                year: "numeric",
                                                hour: "2-digit",
                                                minute: "2-digit",
                                            })}
                                        </TableCell>
                                        <TableCell>{getTypeBadge(log)}</TableCell>
                                        <TableCell>
                                            <div>
                                                <div className="font-medium">{log.recipient_name || "—"}</div>
                                                <div className="text-sm text-gray-500">{log.recipient_email}</div>
                                            </div>
                                        </TableCell>
                                        <TableCell className="max-w-md truncate">{log.subject}</TableCell>
                                        <TableCell>{getStatusBadge(log.status)}</TableCell>
                                        <TableCell>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => viewDetails(log)}
                                            >
                                                <FileText className="h-4 w-4 mr-1" />
                                                Details
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>

                        {logs.data.length === 0 && (
                            <div className="text-center py-8">
                                <p className="text-gray-500">
                                    {filters.search || filters.type || filters.status
                                        ? "Keine E-Mails gefunden."
                                        : "Noch keine E-Mails versendet."}
                                </p>
                            </div>
                        )}

                        {/* Pagination */}
                        {logs.links && logs.links.length > 3 && (
                            <div className="flex justify-center mt-6 gap-2">
                                {logs.links.map((link, index) => (
                                    <Button
                                        key={index}
                                        variant={link.active ? "default" : "outline"}
                                        size="sm"
                                        disabled={!link.url}
                                        onClick={() => link.url && router.get(link.url)}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Email Details Modal */}
            <Dialog open={detailsOpen} onOpenChange={setDetailsOpen}>
                <DialogContent className="max-w-3xl max-h-[80vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>E-Mail Details</DialogTitle>
                        <DialogDescription>
                            Vollständige Informationen zur gesendeten E-Mail
                        </DialogDescription>
                    </DialogHeader>
                    {selectedLog && (
                        <div className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Versendet am</p>
                                    <p className="text-sm">
                                        {new Date(selectedLog.sent_at).toLocaleString("de-DE", {
                                            day: "2-digit",
                                            month: "long",
                                            year: "numeric",
                                            hour: "2-digit",
                                            minute: "2-digit",
                                        })}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Typ</p>
                                    <div>{getTypeBadge(selectedLog)}</div>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Empfänger</p>
                                    <p className="text-sm font-medium">{selectedLog.recipient_name || "—"}</p>
                                    <p className="text-sm text-gray-600">{selectedLog.recipient_email}</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Status</p>
                                    <div>{getStatusBadge(selectedLog.status)}</div>
                                </div>
                            </div>

                            <div className="border-t pt-4">
                                <p className="text-sm font-medium text-gray-500 mb-2">Betreff</p>
                                <p className="text-sm">{selectedLog.subject}</p>
                            </div>

                            {selectedLog.body && (
                                <div className="border-t pt-4">
                                    <p className="text-sm font-medium text-gray-500 mb-2">Nachricht</p>
                                    <div className="bg-gray-50 p-4 rounded-lg text-sm whitespace-pre-wrap">
                                        {selectedLog.body}
                                    </div>
                                </div>
                            )}

                            {selectedLog.metadata && Object.keys(selectedLog.metadata).length > 0 && (
                                <div className="border-t pt-4">
                                    <p className="text-sm font-medium text-gray-500 mb-2">Zusätzliche Informationen</p>
                                    <div className="bg-gray-50 p-4 rounded-lg space-y-2">
                                        {selectedLog.metadata.invoice_number && (
                                            <div className="flex justify-between text-sm">
                                                <span className="text-gray-600">Rechnungsnummer:</span>
                                                <span className="font-medium">{selectedLog.metadata.invoice_number}</span>
                                            </div>
                                        )}
                                        {selectedLog.metadata.reminder_level_name && (
                                            <div className="flex justify-between text-sm">
                                                <span className="text-gray-600">Mahnstufe:</span>
                                                <span className="font-medium">{selectedLog.metadata.reminder_level_name}</span>
                                            </div>
                                        )}
                                        {selectedLog.metadata.reminder_fee > 0 && (
                                            <div className="flex justify-between text-sm">
                                                <span className="text-gray-600">Mahngebühr:</span>
                                                <span className="font-medium text-orange-600">
                                                    {new Intl.NumberFormat("de-DE", {
                                                        style: "currency",
                                                        currency: "EUR",
                                                    }).format(selectedLog.metadata.reminder_fee)}
                                                </span>
                                            </div>
                                        )}
                                        {selectedLog.metadata.days_overdue && (
                                            <div className="flex justify-between text-sm">
                                                <span className="text-gray-600">Tage überfällig:</span>
                                                <span className="font-medium text-red-600">
                                                    {selectedLog.metadata.days_overdue} Tage
                                                </span>
                                            </div>
                                        )}
                                        {selectedLog.metadata.has_pdf_attachment && (
                                            <div className="flex justify-between text-sm">
                                                <span className="text-gray-600">PDF-Anhang:</span>
                                                <span className="text-green-600">✓ Ja</span>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </AppLayout>
    )
}


