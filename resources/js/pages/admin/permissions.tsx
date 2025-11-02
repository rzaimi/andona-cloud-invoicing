"use client"

import { Head, Link, useForm } from "@inertiajs/react"
import AppLayout from "@/layouts/app-layout"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"

interface Props {
    permissions: string[]
}

export default function PermissionsIndex({ permissions }: Props) {
    const form = useForm({ name: "" })

    return (
        <AppLayout breadcrumbs={[{ title: "Dashboard", href: "/dashboard" }, { title: "Berechtigungen" }]}>
            <Head title="Berechtigungen" />

            <div className="grid gap-6 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Neue Berechtigung</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form
                            className="space-y-4"
                            onSubmit={(e) => {
                                e.preventDefault()
                                form.post(route("permissions.store"))
                            }}
                        >
                            <div className="space-y-2">
                                <Label>Bezeichnung</Label>
                                <Input value={form.data.name} onChange={(e) => form.setData("name", e.target.value)} />
                            </div>
                            <Button type="submit" disabled={form.processing}>Erstellen</Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Alle Berechtigungen</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-2">
                            {permissions.map((p) => (
                                <div key={p} className="flex items-center justify-between border rounded px-3 py-2">
                                    <span>{p}</span>
                                    <Button variant="outline" asChild>
                                        <Link href={route("permissions.destroy", p)} method="delete" as="button">
                                            Löschen
                                        </Link>
                                    </Button>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    )
}


