"use client"

import type React from "react"

import { useState } from "react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
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

interface User {
    id: number
    name: string
    email: string
    role: "admin" | "user"
    company_id: number
    status: "active" | "inactive"
    created_at: string
}

interface UserManagementProps {
    currentUser: {
        id: number
        role: string
        company: { id: number }
    }
}

export default function UserManagement({ currentUser }: UserManagementProps) {
    const [users, setUsers] = useState<User[]>([
        {
            id: 1,
            name: "John Doe",
            email: "john@acme.com",
            role: "admin",
            company_id: 1,
            status: "active",
            created_at: "2024-01-15",
        },
        {
            id: 2,
            name: "Jane Smith",
            email: "jane@acme.com",
            role: "user",
            company_id: 1,
            status: "active",
            created_at: "2024-02-01",
        },
    ])

    const [isDialogOpen, setIsDialogOpen] = useState(false)
    const [editingUser, setEditingUser] = useState<User | null>(null)
    const [formData, setFormData] = useState({
        name: "",
        email: "",
        role: "user" as "admin" | "user",
        password: "",
    })

    // Filter users based on current user's company
    const filteredUsers = users.filter((user) => user.company_id === currentUser.company.id)

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()

        if (editingUser) {
            // Update existing user
            setUsers(
                users.map((user) =>
                    user.id === editingUser.id
                        ? { ...user, name: formData.name, email: formData.email, role: formData.role }
                        : user,
                ),
            )
        } else {
            // Create new user
            const newUser: User = {
                id: Date.now(),
                name: formData.name,
                email: formData.email,
                role: formData.role,
                company_id: currentUser.company.id,
                status: "active",
                created_at: new Date().toISOString().split("T")[0],
            }
            setUsers([...users, newUser])
        }

        setIsDialogOpen(false)
        setEditingUser(null)
        setFormData({ name: "", email: "", role: "user", password: "" })
    }

    const handleEdit = (user: User) => {
        setEditingUser(user)
        setFormData({
            name: user.name,
            email: user.email,
            role: user.role,
            password: "",
        })
        setIsDialogOpen(true)
    }

    const handleDelete = (id: number) => {
        setUsers(users.filter((user) => user.id !== id))
    }

    return (
        <div className="space-y-6">
            <div className="flex justify-between items-center">
                <div>
                    <h2 className="text-3xl font-bold text-gray-900">User Management</h2>
                    <p className="text-gray-600">Manage users in your company</p>
                </div>

                {currentUser.role === "admin" && (
                    <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
                        <DialogTrigger asChild>
                            <Button
                                onClick={() => {
                                    setEditingUser(null)
                                    setFormData({ name: "", email: "", role: "user", password: "" })
                                }}
                            >
                                <Plus className="mr-2 h-4 w-4" />
                                Add User
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="sm:max-w-[425px]">
                            <DialogHeader>
                                <DialogTitle>{editingUser ? "Edit User" : "Add New User"}</DialogTitle>
                                <DialogDescription>
                                    {editingUser ? "Update user information" : "Create a new user account"}
                                </DialogDescription>
                            </DialogHeader>
                            <form onSubmit={handleSubmit}>
                                <div className="grid gap-4 py-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Full Name</Label>
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
                                        <Label htmlFor="role">Role</Label>
                                        <Select
                                            value={formData.role}
                                            onValueChange={(value: "admin" | "user") => setFormData({ ...formData, role: value })}
                                        >
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="user">User</SelectItem>
                                                <SelectItem value="admin">Admin</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    {!editingUser && (
                                        <div className="grid gap-2">
                                            <Label htmlFor="password">Password</Label>
                                            <Input
                                                id="password"
                                                type="password"
                                                value={formData.password}
                                                onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                                                required
                                            />
                                        </div>
                                    )}
                                </div>
                                <DialogFooter>
                                    <Button type="submit">{editingUser ? "Update User" : "Create User"}</Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                )}
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Company Users</CardTitle>
                    <CardDescription>Users in your company</CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Role</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Created</TableHead>
                                {currentUser.role === "admin" && <TableHead>Actions</TableHead>}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {filteredUsers.map((user) => (
                                <TableRow key={user.id}>
                                    <TableCell className="font-medium">{user.name}</TableCell>
                                    <TableCell>{user.email}</TableCell>
                                    <TableCell>
                                        <Badge variant={user.role === "admin" ? "default" : "secondary"}>{user.role}</Badge>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant={user.status === "active" ? "default" : "secondary"}>{user.status}</Badge>
                                    </TableCell>
                                    <TableCell>{user.created_at}</TableCell>
                                    {currentUser.role === "admin" && (
                                        <TableCell>
                                            <div className="flex space-x-2">
                                                <Button variant="outline" size="sm" onClick={() => handleEdit(user)}>
                                                    <Edit className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => handleDelete(user.id)}
                                                    disabled={user.id === currentUser.id}
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    )}
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    )
}
