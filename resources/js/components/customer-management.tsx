"use client"

import type React from "react"

import { useState } from "react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
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

interface Customer {
    id: number
    name: string
    email: string
    phone: string
    address: string
    company_id: number
    tax_number?: string
    status: "active" | "inactive"
    created_at: string
}

interface CustomerManagementProps {
    companyId: number
}

export default function CustomerManagement({ companyId }: CustomerManagementProps) {
    const [customers, setCustomers] = useState<Customer[]>([
        {
            id: 1,
            name: "ABC Corporation",
            email: "contact@abc-corp.com",
            phone: "+1 555 123 4567",
            address: "123 Main St, City, State 12345",
            company_id: 1,
            tax_number: "TAX123456789",
            status: "active",
            created_at: "2024-01-15",
        },
        {
            id: 2,
            name: "XYZ Ltd",
            email: "info@xyz-ltd.com",
            phone: "+1 555 987 6543",
            address: "456 Business Ave, City, State 12346",
            company_id: 1,
            tax_number: "TAX987654321",
            status: "active",
            created_at: "2024-02-01",
        },
    ])

    const [isDialogOpen, setIsDialogOpen] = useState(false)
    const [editingCustomer, setEditingCustomer] = useState<Customer | null>(null)
    const [formData, setFormData] = useState({
        name: "",
        email: "",
        phone: "",
        address: "",
        tax_number: "",
    })

    // Filter customers by company
    const filteredCustomers = customers.filter((customer) => customer.company_id === companyId)

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()

        if (editingCustomer) {
            // Update existing customer
            setCustomers(
                customers.map((customer) => (customer.id === editingCustomer.id ? { ...customer, ...formData } : customer)),
            )
        } else {
            // Create new customer
            const newCustomer: Customer = {
                id: Date.now(),
                ...formData,
                company_id: companyId,
                status: "active",
                created_at: new Date().toISOString().split("T")[0],
            }
            setCustomers([...customers, newCustomer])
        }

        setIsDialogOpen(false)
        setEditingCustomer(null)
        setFormData({ name: "", email: "", phone: "", address: "", tax_number: "" })
    }

    const handleEdit = (customer: Customer) => {
        setEditingCustomer(customer)
        setFormData({
            name: customer.name,
            email: customer.email,
            phone: customer.phone,
            address: customer.address,
            tax_number: customer.tax_number || "",
        })
        setIsDialogOpen(true)
    }

    const handleDelete = (id: number) => {
        setCustomers(customers.filter((customer) => customer.id !== id))
    }

    return (
        <div className="space-y-6">
            <div className="flex justify-between items-center">
                <div>
                    <h2 className="text-3xl font-bold text-gray-900">Customer Management</h2>
                    <p className="text-gray-600">Manage your company's customers</p>
                </div>

                <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
                    <DialogTrigger asChild>
                        <Button
                            onClick={() => {
                                setEditingCustomer(null)
                                setFormData({ name: "", email: "", phone: "", address: "", tax_number: "" })
                            }}
                        >
                            <Plus className="mr-2 h-4 w-4" />
                            Add Customer
                        </Button>
                    </DialogTrigger>
                    <DialogContent className="sm:max-w-[500px]">
                        <DialogHeader>
                            <DialogTitle>{editingCustomer ? "Edit Customer" : "Add New Customer"}</DialogTitle>
                            <DialogDescription>
                                {editingCustomer ? "Update customer information" : "Create a new customer record"}
                            </DialogDescription>
                        </DialogHeader>
                        <form onSubmit={handleSubmit}>
                            <div className="grid gap-4 py-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Customer Name</Label>
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
                                    <Textarea
                                        id="address"
                                        value={formData.address}
                                        onChange={(e) => setFormData({ ...formData, address: e.target.value })}
                                        required
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="tax_number">Tax Number (Optional)</Label>
                                    <Input
                                        id="tax_number"
                                        value={formData.tax_number}
                                        onChange={(e) => setFormData({ ...formData, tax_number: e.target.value })}
                                    />
                                </div>
                            </div>
                            <DialogFooter>
                                <Button type="submit">{editingCustomer ? "Update Customer" : "Create Customer"}</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Customers</CardTitle>
                    <CardDescription>Your company's customer database</CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Customer Name</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Phone</TableHead>
                                <TableHead>Tax Number</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Created</TableHead>
                                <TableHead>Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {filteredCustomers.map((customer) => (
                                <TableRow key={customer.id}>
                                    <TableCell className="font-medium">{customer.name}</TableCell>
                                    <TableCell>{customer.email}</TableCell>
                                    <TableCell>{customer.phone}</TableCell>
                                    <TableCell>{customer.tax_number || "-"}</TableCell>
                                    <TableCell>
                                        <Badge variant={customer.status === "active" ? "default" : "secondary"}>{customer.status}</Badge>
                                    </TableCell>
                                    <TableCell>{customer.created_at}</TableCell>
                                    <TableCell>
                                        <div className="flex space-x-2">
                                            <Button variant="outline" size="sm" onClick={() => handleEdit(customer)}>
                                                <Edit className="h-4 w-4" />
                                            </Button>
                                            <Button variant="outline" size="sm" onClick={() => handleDelete(customer.id)}>
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
