"use client"

import { useState } from "react"
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/components/ui/dialog"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { ClipboardList, User, Clock } from "lucide-react"
import axios from "axios"

interface AuditLog {
    id: number
    action: string
    old_status: string | null
    new_status: string | null
    changes: Record<string, any> | null
    notes: string | null
    user: {
        name: string
        email: string
    } | null
    ip_address: string | null
    created_at: string
}

interface InvoiceAuditLogDialogProps {
    invoiceId: string
}

export function InvoiceAuditLogDialog({ invoiceId }: InvoiceAuditLogDialogProps) {
    const [open, setOpen] = useState(false)
    const [logs, setLogs] = useState<AuditLog[]>([])
    const [loading, setLoading] = useState(false)

    const loadAuditLog = async () => {
        setLoading(true)
        try {
            const response = await axios.get(`/invoices/${invoiceId}/audit-log`)
            setLogs(response.data.logs)
        } catch (error) {
            console.error('Failed to load audit log:', error)
        } finally {
            setLoading(false)
        }
    }

    const handleOpen = () => {
        setOpen(true)
        loadAuditLog()
    }

    const getActionBadge = (action: string) => {
        const actionConfig: Record<string, { label: string; variant: "default" | "secondary" | "destructive" | "outline" }> = {
            created: { label: "Erstellt", variant: "default" },
            updated: { label: "Bearbeitet", variant: "secondary" },
            status_changed: { label: "Status geändert", variant: "secondary" },
            sent: { label: "Versendet", variant: "outline" },
            paid: { label: "Bezahlt", variant: "default" },
            corrected: { label: "Storniert", variant: "destructive" },
        }

        const config = actionConfig[action] || { label: action, variant: "outline" as const }
        return <Badge variant={config.variant}>{config.label}</Badge>
    }

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleString('de-DE', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
        })
    }

    return (
        <>
            <Button variant="outline" size="sm" onClick={handleOpen}>
                <ClipboardList className="mr-2 h-4 w-4" />
                Änderungsverlauf
            </Button>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-w-3xl max-h-[80vh]">
                    <DialogHeader>
                        <DialogTitle>Änderungsverlauf (Audit Log)</DialogTitle>
                        <DialogDescription>
                            Vollständige Historie aller Änderungen an dieser Rechnung (GoBD-konform)
                        </DialogDescription>
                    </DialogHeader>

                    <div className="max-h-[500px] overflow-y-auto pr-4">
                        {loading ? (
                            <div className="flex items-center justify-center py-8">
                                <p className="text-muted-foreground">Lade Änderungsverlauf...</p>
                            </div>
                        ) : logs.length === 0 ? (
                            <div className="flex items-center justify-center py-8">
                                <p className="text-muted-foreground">Keine Änderungen gefunden</p>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {logs.map((log) => (
                                    <div key={log.id} className="border rounded-lg p-4 space-y-3">
                                        <div className="flex items-start justify-between">
                                            <div className="flex items-center gap-2">
                                                {getActionBadge(log.action)}
                                                {log.old_status && log.new_status && (
                                                    <span className="text-sm text-muted-foreground">
                                                        {log.old_status} → {log.new_status}
                                                    </span>
                                                )}
                                            </div>
                                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                                <Clock className="h-3 w-3" />
                                                {formatDate(log.created_at)}
                                            </div>
                                        </div>

                                        {log.notes && (
                                            <p className="text-sm">{log.notes}</p>
                                        )}

                                        {log.changes && Object.keys(log.changes).length > 0 && (
                                            <div className="bg-gray-50 rounded p-3 space-y-1">
                                                <p className="text-xs font-medium text-gray-700 mb-2">Änderungen:</p>
                                                {Object.entries(log.changes).map(([key, value]: [string, any]) => (
                                                    <div key={key} className="text-xs">
                                                        <span className="font-medium">{key}:</span>{' '}
                                                        {value.old !== undefined ? (
                                                            <>
                                                                <span className="text-red-600">{JSON.stringify(value.old)}</span>
                                                                {' → '}
                                                                <span className="text-green-600">{JSON.stringify(value.new)}</span>
                                                            </>
                                                        ) : (
                                                            <span>{JSON.stringify(value)}</span>
                                                        )}
                                                    </div>
                                                ))}
                                            </div>
                                        )}

                                        {log.user && (
                                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                                <User className="h-3 w-3" />
                                                {log.user.name}
                                                {log.ip_address && (
                                                    <span className="text-xs">({log.ip_address})</span>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </DialogContent>
            </Dialog>
        </>
    )
}
