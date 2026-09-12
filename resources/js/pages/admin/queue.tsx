"use client"

import { Head, router } from "@inertiajs/react"
import AppLayout from "@/layouts/app-layout"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { ListTodo, RefreshCw, RotateCcw, Trash2 } from "lucide-react"
import { route } from "ziggy-js"

type PendingJob = {
    id: number
    queue: string
    name: string
    attempts: number
    reserved: boolean
    available_at: string
    created_at: string
}

type FailedJob = {
    id: number
    uuid: string
    queue: string
    name: string
    exception: string
    failed_at: string
}

interface Props {
    connection: string
    pending_count: number
    failed_count: number
    pending: PendingJob[]
    failed: FailedJob[]
}

function formatDate(value: string | null): string {
    if (!value) {
        return "—"
    }

    const date = new Date(value)

    if (Number.isNaN(date.getTime())) {
        return value
    }

    return date.toLocaleString("de-DE", { dateStyle: "short", timeStyle: "short" })
}

export default function QueueMonitor({ connection, pending_count, failed_count, pending, failed }: Props) {
    const refresh = () => router.reload()

    const retry = (uuid: string) => {
        router.post(route("system-health.queue.retry", uuid))
    }

    const retryAll = () => {
        if (!confirm("Alle fehlgeschlagenen Aufträge erneut einreihen?")) {
            return
        }

        router.post(route("system-health.queue.retry-all"))
    }

    const forget = (uuid: string) => {
        if (!confirm("Diesen fehlgeschlagenen Auftrag endgültig entfernen?")) {
            return
        }

        router.delete(route("system-health.queue.forget", uuid))
    }

    const flushFailed = () => {
        if (!confirm("Alle fehlgeschlagenen Aufträge unwiderruflich löschen?")) {
            return
        }

        router.post(route("system-health.queue.flush-failed"))
    }

    const clearPending = () => {
        if (!confirm("Alle ausstehenden Aufträge löschen? Bereits versendete E-Mails in der Queue gehen verloren.")) {
            return
        }

        router.post(route("system-health.queue.clear-pending"))
    }

    return (
        <AppLayout breadcrumbs={[{ title: "Dashboard", href: "/dashboard" }, { title: "Warteschlange" }]}>
            <Head title="Warteschlange" />

            <div className="space-y-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 className="text-xl font-bold text-gray-900 dark:text-gray-100">Warteschlange</h1>
                        <p className="mt-2 text-muted-foreground">
                            Ausstehende und fehlgeschlagene Hintergrundaufträge ({connection})
                        </p>
                    </div>
                    <Button variant="outline" onClick={refresh}>
                        <RefreshCw className="mr-2 h-4 w-4" />
                        Aktualisieren
                    </Button>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Ausstehend</CardTitle>
                            <ListTodo className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-bold">{pending_count}</p>
                            <p className="text-sm text-muted-foreground">Jobs warten auf den Worker</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Fehlgeschlagen</CardTitle>
                            <Trash2 className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-bold">{failed_count}</p>
                            <p className="text-sm text-muted-foreground">Jobs, die wiederholt werden können</p>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <CardTitle>Ausstehende Aufträge</CardTitle>
                            <CardDescription>Die letzten 100 Einträge. E-Mails bleiben hier, bis der Queue-Worker läuft.</CardDescription>
                        </div>
                        {pending_count > 0 && (
                            <Button variant="destructive" size="sm" onClick={clearPending}>
                                <Trash2 className="mr-2 h-4 w-4" />
                                Ausstehende löschen
                            </Button>
                        )}
                    </CardHeader>
                    <CardContent>
                        {pending.length === 0 ? (
                            <p className="text-sm text-muted-foreground">Keine ausstehenden Aufträge.</p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Auftrag</TableHead>
                                        <TableHead>Queue</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Versuche</TableHead>
                                        <TableHead>Erstellt</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {pending.map((job) => (
                                        <TableRow key={job.id}>
                                            <TableCell className="font-medium">{job.name}</TableCell>
                                            <TableCell>{job.queue}</TableCell>
                                            <TableCell>
                                                {job.reserved ? (
                                                    <Badge variant="secondary">In Bearbeitung</Badge>
                                                ) : (
                                                    <Badge variant="outline">Wartend</Badge>
                                                )}
                                            </TableCell>
                                            <TableCell>{job.attempts}</TableCell>
                                            <TableCell>{formatDate(job.created_at)}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <CardTitle>Fehlgeschlagene Aufträge</CardTitle>
                            <CardDescription>Die letzten 100 Fehler. Erneut einreihen oder entfernen.</CardDescription>
                        </div>
                        {failed_count > 0 && (
                            <div className="flex flex-wrap gap-2">
                                <Button variant="outline" size="sm" onClick={retryAll}>
                                    <RotateCcw className="mr-2 h-4 w-4" />
                                    Alle erneut
                                </Button>
                                <Button variant="destructive" size="sm" onClick={flushFailed}>
                                    <Trash2 className="mr-2 h-4 w-4" />
                                    Alle löschen
                                </Button>
                            </div>
                        )}
                    </CardHeader>
                    <CardContent>
                        {failed.length === 0 ? (
                            <p className="text-sm text-muted-foreground">Keine fehlgeschlagenen Aufträge.</p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Auftrag</TableHead>
                                        <TableHead>Queue</TableHead>
                                        <TableHead>Fehler</TableHead>
                                        <TableHead>Fehlgeschlagen</TableHead>
                                        <TableHead className="text-right">Aktionen</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {failed.map((job) => (
                                        <TableRow key={job.uuid}>
                                            <TableCell className="font-medium">{job.name}</TableCell>
                                            <TableCell>{job.queue}</TableCell>
                                            <TableCell className="max-w-md truncate text-sm text-muted-foreground" title={job.exception}>
                                                {job.exception}
                                            </TableCell>
                                            <TableCell>{formatDate(job.failed_at)}</TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Button variant="outline" size="sm" onClick={() => retry(job.uuid)}>
                                                        Erneut
                                                    </Button>
                                                    <Button variant="ghost" size="sm" onClick={() => forget(job.uuid)}>
                                                        Entfernen
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    )
}
