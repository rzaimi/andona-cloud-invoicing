"use client"

import type React from "react"

import { useState } from "react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Badge } from "@/components/ui/badge"
import { Plus, Edit, Trash2 } from "lucide-react"

interface Company {
    id: number
    name: string
    email: string
    phone: string
    address: string
    status: "active" | "inactive"
    users_count: number
    created_at: string
}

export default function CompanyManagement() {
    const [companies, setCompanies] = useState<Company[]>([
        {
            id: 1,
            name: "Acme Corp",
            email: "admin@acme.com",
            phone: "+1 234 567 8900",
            address: "123 Business St, City, State 12345",
            status: "active",
            users_count: 5,
            created_at: "2024-01-15",
        },
        {
            id: 2,
            name: "Tech Solutions Ltd",
            email: "info@techsolutions.com",
            phone: "+1 234 567 8901",
            address: "456 Tech Ave, City, State 12346",
            status: "active",
            users_count: 3,
            created_at: "2024-02-20",
        },
    ])

    const [isDialogOpen, setIsDialogOpen] = useState(false)
    const [editingCompany, setEditingCompany] = useState<Company | null>(null)
    const [formData, setFormData] = useState({
        name: "",
        email: "",
        phone: "",
        address: "",
    })

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()

        if (editingCompany) {
            // Update existing company
            setCompanies(
                companies.map((company) => (company.id === editingCompany.id ? { ...company, ...formData } : company)),
            )
        } else {
            // Create new company
            const newCompany: Company = {
                id: Date.now(),
                ...formData,
                status: "active",
                users_count: 0,
                created_at: new Date().toISOString().split("T")[0],
            }
            setCompanies([...companies, newCompany])
        }

        setIsDialogOpen(false)
        setEditingCompany(null)
        setFormData({ name: "", email: "", phone: "", address: "" })
    }

    const handleEdit = (company: Company) => {
        setEditingCompany(company)
        setFormData({
            name: company.name,
            email: company.email,
            phone: company.phone,
            address: company.address,
        })
        setIsDialogOpen(true)
    }

    const handleDelete = (id: number) => {
        setCompanies(companies.filter((company) => company.id !== id))
    }

    return (
        <div className="space-y-6">
            <div className="flex justify-between items-center">
                <div>
                    <h2 className="text-3xl font-bold text-gray-900">Company Management</h2>
                    <p className="text-gray-600">Manage all companies in the system</p>
                </div>

                <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
                    <DialogTrigger asChild>
                        <Button
                            onClick={() => {
                                setEditingCompany(null)
                                setFormData({ name: "", email: "", phone: "", address: "" })
                            }}
                        >
                            <Plus className="mr-2 h-4 w-4" />
                            Add Company
                        </Button>
                    </DialogTrigger>
                    <DialogContent className="sm:max-w-[425px]">
                        <DialogHeader>
                            <DialogTitle>{editingCompany ? "Edit Company" : "Add New Company"}</DialogTitle>
                            <DialogDescription>
                                {editingCompany ? "Update company information" : "Create a new company in the system"}
                            </DialogDescription>
                        </DialogHeader>
                        <form onSubmit={handleSubmit}>
                            <div className="grid gap-4 py-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Company Name</Label>
                                    <Input
                                        id="name"
                                        value={formData.name}
                                        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                        required
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={formData.email}
                                        onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                                        required
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="phone">Phone</Label>
                                    <Input
                                        id="phone"
                                        value={formData.phone}
                                        onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                                        required
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="address">Address</Label>
                                    <Input
                                        id="address"
                                        value={formData.address}
                                        onChange={(e) => setFormData({ ...formData, address: e.target.value })}
                                        required
                                    />
                                </div>
                            </div>
                            <DialogFooter>
                                <Button type="submit">{editingCompany ? "Update Company" : "Create Company"}</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Companies</CardTitle>
                    <CardDescription>All registered companies in the system</CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Company Name</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Phone</TableHead>
                                <TableHead>Users</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Created</TableHead>
                                <TableHead>Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {companies.map((company) => (
                                <TableRow key={company.id}>
                                    <TableCell className="font-medium">{company.name}</TableCell>
                                    <TableCell>{company.email}</TableCell>
                                    <TableCell>{company.phone}</TableCell>
                                    <TableCell>{company.users_count}</TableCell>
                                    <TableCell>
                                        <Badge variant={company.status === "active" ? "default" : "secondary"}>{company.status}</Badge>
                                    </TableCell>
                                    <TableCell>{company.created_at}</TableCell>
                                    <TableCell>
                                        <div className="flex space-x-2">
                                            <Button variant="outline" size="sm" onClick={() => handleEdit(company)}>
                                                <Edit className="h-4 w-4" />
                                            </Button>
                                            <Button variant="outline" size="sm" onClick={() => handleDelete(company.id)}>
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    )
}
