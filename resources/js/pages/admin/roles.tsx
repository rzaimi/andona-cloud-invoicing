"use client"

import { Head, Link, useForm, usePage } from "@inertiajs/react"
import AppLayout from "@/layouts/app-layout"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"

type Role = {
    id: string
    name: string
    permissions: { id: string; name: string }[]
}

interface Props {
    roles: Role[]
    permissions: string[]
}

export default function RolesIndex({ roles, permissions }: Props) {
    const createForm = useForm({ name: "", permissions: [] as string[] })
    const updateForms = Object.fromEntries(
        roles.map((r) => [
            r.id,
            useForm({ name: r.name, permissions: r.permissions.map((p) => p.name) }),
        ])
    ) as Record<string, ReturnType<typeof useForm<{ name: string; permissions: string[] }>>>

    return (
        <AppLayout breadcrumbs={[{ title: "Dashboard", href: "/dashboard" }, { title: "Rollen" }]}>
            <Head title="Rollenverwaltung" />

            <div className="grid gap-6 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Neue Rolle</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form
                            className="space-y-4"
                            onSubmit={(e) => {
                                e.preventDefault()
                                createForm.post(route("roles.store"))
                            }}
                        >
                            <div className="space-y-2">
                                <Label>Bezeichnung</Label>
                                <Input value={createForm.data.name} onChange={(e) => createForm.setData("name", e.target.value)} />
                            </div>
                            <div className="space-y-2">
                                <Label>Berechtigungen</Label>
                                <div className="grid gap-2 md:grid-cols-2">
                                    {permissions.map((p) => {
                                        const checked = createForm.data.permissions.includes(p)
                                        return (
                                            <label key={p} className="flex items-center gap-2 text-sm">
                                                <input
                                                    type="checkbox"
                                                    checked={checked}
                                                    onChange={(e) => {
                                                        if (e.target.checked) createForm.setData("permissions", [...createForm.data.permissions, p])
                                                        else createForm.setData("permissions", createForm.data.permissions.filter((x) => x !== p))
                                                    }}
                                                />
                                                <span>{p}</span>
                                            </label>
                                        )
                                    })}
                                </div>
                            </div>
                            <Button type="submit" disabled={createForm.processing}>{t('common.create')}</Button>
                        </form>
                    </CardContent>
                </Card>

                <div className="space-y-6">
                    {roles.map((role) => {
                        const form = updateForms[role.id]
                        return (
                            <Card key={role.id}>
                                <CardHeader>
                                    <CardTitle>Rolle: {role.name}</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <form
                                        className="space-y-4"
                                        onSubmit={(e) => {
                                            e.preventDefault()
                                            form.put(route("roles.update", role.id))
                                        }}
                                    >
                                        <div className="space-y-2">
                                            <Label>Bezeichnung</Label>
                                            <Input value={form.data.name} onChange={(e) => form.setData("name", e.target.value)} />
                                        </div>
                                        <div className="space-y-2">
                                            <Label>Berechtigungen</Label>
                                            <div className="grid gap-2 md:grid-cols-2">
                                                {permissions.map((p) => {
                                                    const checked = form.data.permissions.includes(p)
                                                    return (
                                                        <label key={p} className="flex items-center gap-2 text-sm">
                                                            <input
                                                                type="checkbox"
                                                                checked={checked}
                                                                onChange={(e) => {
                                                                    if (e.target.checked) form.setData("permissions", [...form.data.permissions, p])
                                                                    else form.setData("permissions", form.data.permissions.filter((x) => x !== p))
                                                                }}
                                                            />
                                                            <span>{p}</span>
                                                        </label>
                                                    )
                                                })}
                                            </div>
                                        </div>
                                        <div className="flex gap-2 justify-end">
                                            <Button variant="outline" asChild>
                                                <Link href={route("roles.destroy", role.id)} method="delete" as="button">
                                                    {t('common.delete')}
                                                </Link>
                                            </Button>
                                            <Button type="submit" disabled={form.processing}>{t('common.save')}</Button>
                                        </div>
                                    </form>
                                </CardContent>
                            </Card>
                        )
                    })}
                </div>
            </div>
        </AppLayout>
    )
}


