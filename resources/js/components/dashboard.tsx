"use client"

import { useState } from "react"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Building2, Users, FileText, Receipt, Settings } from "lucide-react"
import CompanyManagement from "./company-management"
import UserManagement from "./user-management"
import CustomerManagement from "./customer-management"
import InvoiceManagement from "./invoice-management"
import SettingsPanel from "./settings-panel"

export default function Dashboard() {
    const [activeTab, setActiveTab] = useState("overview")

    // Mock user data - would come from Laravel API
    const currentUser = {
        id: 1,
        name: "John Doe",
        email: "john@example.com",
        role: "admin", // admin or user
        company: {
            id: 1,
            name: "Acme Corp",
            settings: {
                currency: "USD",
                tax_rate: 0.21,
                invoice_prefix: "INV-",
                offer_prefix: "OFF-",
            },
        },
    }

    const stats = {
        totalInvoices: 156,
        totalOffers: 43,
        totalCustomers: 89,
        monthlyRevenue: 45230,
    }

    return (
        <div className="flex h-screen bg-gray-100">
            {/* Sidebar */}
            <div className="w-64 bg-white shadow-lg">
                <div className="p-6">
                    <h1 className="text-xl font-bold text-gray-800">Invoice System</h1>
                    <p className="text-sm text-gray-600">{currentUser.company.name}</p>
                </div>

                <nav className="mt-6">
                    <div className="px-6 py-2">
                        <Button
                            variant={activeTab === "overview" ? "default" : "ghost"}
                            className="w-full justify-start"
                            onClick={() => setActiveTab("overview")}
                        >
                            <FileText className="mr-2 h-4 w-4" />
                            Overview
                        </Button>
                    </div>

                    {currentUser.role === "admin" && (
                        <div className="px-6 py-2">
                            <Button
                                variant={activeTab === "companies" ? "default" : "ghost"}
                                className="w-full justify-start"
                                onClick={() => setActiveTab("companies")}
                            >
                                <Building2 className="mr-2 h-4 w-4" />
                                Companies
                            </Button>
                        </div>
                    )}

                    <div className="px-6 py-2">
                        <Button
                            variant={activeTab === "users" ? "default" : "ghost"}
                            className="w-full justify-start"
                            onClick={() => setActiveTab("users")}
                        >
                            <Users className="mr-2 h-4 w-4" />
                            Users
                        </Button>
                    </div>

                    <div className="px-6 py-2">
                        <Button
                            variant={activeTab === "customers" ? "default" : "ghost"}
                            className="w-full justify-start"
                            onClick={() => setActiveTab("customers")}
                        >
                            <Users className="mr-2 h-4 w-4" />
                            Customers
                        </Button>
                    </div>

                    <div className="px-6 py-2">
                        <Button
                            variant={activeTab === "invoices" ? "default" : "ghost"}
                            className="w-full justify-start"
                            onClick={() => setActiveTab("invoices")}
                        >
                            <Receipt className="mr-2 h-4 w-4" />
                            Invoices & Offers
                        </Button>
                    </div>

                    <div className="px-6 py-2">
                        <Button
                            variant={activeTab === "settings" ? "default" : "ghost"}
                            className="w-full justify-start"
                            onClick={() => setActiveTab("settings")}
                        >
                            <Settings className="mr-2 h-4 w-4" />
                            Settings
                        </Button>
                    </div>
                </nav>
            </div>

            {/* Main Content */}
            <div className="flex-1 overflow-auto">
                <div className="p-8">
                    {activeTab === "overview" && (
                        <div className="space-y-6">
                            <div>
                                <h2 className="text-3xl font-bold text-gray-900">Dashboard</h2>
                                <p className="text-gray-600">Welcome back, {currentUser.name}</p>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">Total Invoices</CardTitle>
                                        <Receipt className="h-4 w-4 text-muted-foreground" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold">{stats.totalInvoices}</div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">Total Offers</CardTitle>
                                        <FileText className="h-4 w-4 text-muted-foreground" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold">{stats.totalOffers}</div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">Customers</CardTitle>
                                        <Users className="h-4 w-4 text-muted-foreground" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold">{stats.totalCustomers}</div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">Monthly Revenue</CardTitle>
                                        <Receipt className="h-4 w-4 text-muted-foreground" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold">${stats.monthlyRevenue.toLocaleString()}</div>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    )}

                    {activeTab === "companies" && currentUser.role === "admin" && <CompanyManagement />}

                    {activeTab === "users" && <UserManagement currentUser={currentUser} />}

                    {activeTab === "customers" && <CustomerManagement companyId={currentUser.company.id} />}

                    {activeTab === "invoices" && (
                        <InvoiceManagement companyId={currentUser.company.id} settings={currentUser.company.settings} />
                    )}

                    {activeTab === "settings" && <SettingsPanel company={currentUser.company} />}
                </div>
            </div>
        </div>
    )
}
