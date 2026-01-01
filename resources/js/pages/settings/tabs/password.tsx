"use client"

import { useForm } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { route } from "ziggy-js"

export default function PasswordSettingsTab() {
    const { data, setData, put, processing, errors } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    })

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        put(route("password.update"))
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Passwort ändern</CardTitle>
                    <CardDescription>
                        Aktualisieren Sie Ihr Passwort für mehr Sicherheit
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="current_password">Aktuelles Passwort</Label>
                        <Input
                            id="current_password"
                            type="password"
                            value={data.current_password}
                            onChange={(e) => setData("current_password", e.target.value)}
                            required
                        />
                        {errors.current_password && <p className="text-sm text-red-600">{errors.current_password}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="password">Neues Passwort</Label>
                        <Input
                            id="password"
                            type="password"
                            value={data.password}
                            onChange={(e) => setData("password", e.target.value)}
                            required
                        />
                        {errors.password && <p className="text-sm text-red-600">{errors.password}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="password_confirmation">Passwort bestätigen</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            value={data.password_confirmation}
                            onChange={(e) => setData("password_confirmation", e.target.value)}
                            required
                        />
                        {errors.password_confirmation && <p className="text-sm text-red-600">{errors.password_confirmation}</p>}
                    </div>
                </CardContent>
            </Card>

            <div className="flex justify-end">
                <Button type="submit" disabled={processing}>
                    {processing ? "Speichert..." : "Passwort aktualisieren"}
                </Button>
            </div>
        </form>
    )
}

