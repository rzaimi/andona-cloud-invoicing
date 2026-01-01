"use client"

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Link } from "@inertiajs/react"
import { route } from "ziggy-js"

interface EmailLogsTabProps {
    emailLogs: any
}

export default function EmailLogsTab({ emailLogs }: EmailLogsTabProps) {
    return (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>E-Mail-Verlauf</CardTitle>
                    <CardDescription>
                        Übersicht über alle versendeten E-Mails
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    {emailLogs ? (
                        <p className="text-sm text-muted-foreground">
                            E-Mail-Logs werden hier angezeigt. Diese Funktion wird in Kürze verfügbar sein.
                        </p>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            <Link href={route("settings.email-logs")} className="text-blue-600 hover:underline">
                                E-Mail-Verlauf anzeigen
                            </Link>
                        </p>
                    )}
                </CardContent>
            </Card>
        </div>
    )
}

