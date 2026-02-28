"use client"

import type React from "react"
import { useState } from "react"
import { Head, Link, router, useForm, usePage } from "@inertiajs/react"
import { useTranslation } from "react-i18next"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Plus, Edit, Trash2, ArrowLeft } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem } from "@/types"

interface ExpenseCategory {
    id: string
    name: string
    expenses_count: number
}

interface ExpensesCategoriesProps {
    categories: ExpenseCategory[]
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Ausgaben", href: "/expenses" },
    { title: "Kategorien" },
]

export default function ExpensesCategories() {
    const { t } = useTranslation()
    // @ts-ignore
    const { categories } = usePage<ExpensesCategoriesProps>().props
    const [editingId, setEditingId] = useState<string | null>(null)
    const [showCreateForm, setShowCreateForm] = useState(false)

    const { data: createData, setData: setCreateData, post: createPost, processing: createProcessing, errors: createErrors, reset: resetCreate } = useForm({
        name: "",
    })

    const { data: editData, setData: setEditData, put: editPut, processing: editProcessing, errors: editErrors, reset: resetEdit } = useForm({
        name: "",
    })

    const handleCreate = (e: React.FormEvent) => {
        e.preventDefault()
        createPost("/expenses/categories", {
            onSuccess: () => {
                resetCreate()
                setShowCreateForm(false)
            },
        })
    }

    const handleEdit = (category: ExpenseCategory) => {
        setEditingId(category.id)
        setEditData("name", category.name)
    }

    const handleUpdate = (categoryId: string) => {
        editPut(`/expenses/categories/${categoryId}`, {
            onSuccess: () => {
                setEditingId(null)
                resetEdit()
            },
        })
    }

    const handleDelete = (category: ExpenseCategory) => {
        if (category.expenses_count > 0) {
            alert(t('pages.expenses.categoryDeleteError'))
            return
        }
        if (confirm(t('pages.expenses.categoryDeleteConfirm', { name: category.name }))) {
            router.delete(`/expenses/categories/${category.id}`)
        }
    }

    const cancelEdit = () => {
        setEditingId(null)
        resetEdit()
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('pages.expenses.categories')} />

            <div className="flex flex-1 flex-col gap-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href="/expenses">
                            <Button variant="ghost" size="sm">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                {t('common.back')}
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-1xl font-bold text-gray-900">{t('pages.expenses.categories')}</h1>
                            <p className="text-gray-600">{t('pages.expenses.categoriesDesc')}</p>
                        </div>
                    </div>

                    <Button onClick={() => setShowCreateForm(!showCreateForm)}>
                        <Plus className="mr-2 h-4 w-4" />
                        Neue Kategorie
                    </Button>
                </div>

                {showCreateForm && (
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('pages.categories.create')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleCreate} className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="name">Kategoriename *</Label>
                                    <Input
                                        id="name"
                                        value={createData.name}
                                        onChange={(e) => setCreateData("name", e.target.value)}
                                        placeholder={t('pages.expenses.categoryPlaceholder')}
                                        required
                                    />
                                    {createErrors.name && (
                                        <p className="text-sm text-red-600">{createErrors.name}</p>
                                    )}
                                </div>
                                <div className="flex gap-2">
                                    <Button type="submit" disabled={createProcessing}>
                                        {createProcessing ? "Wird erstellt..." : "Erstellen"}
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => {
                                            setShowCreateForm(false)
                                            resetCreate()
                                        }}
                                    >
                                        {t('common.cancel')}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Kategorien</CardTitle>
                        <CardDescription>{t('pages.expenses.allCategoriesDesc')}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{t('common.name')}</TableHead>
                                    <TableHead>{t('pages.expenses.categoryCount')}</TableHead>
                                    <TableHead>{t('common.actions')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {categories.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={3} className="text-center text-gray-500 py-8">
                                            Keine Kategorien gefunden
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    categories.map((category) => (
                                        <TableRow key={category.id}>
                                            <TableCell>
                                                {editingId === category.id ? (
                                                    <div className="space-y-2">
                                                        <Input
                                                            value={editData.name}
                                                            onChange={(e) => setEditData("name", e.target.value)}
                                                            className="w-full"
                                                        />
                                                        {editErrors.name && (
                                                            <p className="text-sm text-red-600">{editErrors.name}</p>
                                                        )}
                                                    </div>
                                                ) : (
                                                    <div className="font-medium">{category.name}</div>
                                                )}
                                            </TableCell>
                                            <TableCell>{category.expenses_count}</TableCell>
                                            <TableCell>
                                                {editingId === category.id ? (
                                                    <div className="flex gap-2">
                                                        <Button
                                                            size="sm"
                                                            onClick={() => handleUpdate(category.id)}
                                                            disabled={editProcessing}
                                                        >
                                                            {editProcessing ? "Speichern..." : "Speichern"}
                                                        </Button>
                                                        <Button size="sm" variant="outline" onClick={cancelEdit}>
                                                            {t('common.cancel')}
                                                        </Button>
                                                    </div>
                                                ) : (
                                                    <div className="flex gap-2">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => handleEdit(category)}
                                                        >
                                                            <Edit className="h-4 w-4" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => handleDelete(category)}
                                                            disabled={category.expenses_count > 0}
                                                        >
                                                            <Trash2 className="h-4 w-4 text-red-600" />
                                                        </Button>
                                                    </div>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    )
}



