"use client"

import { Head, router, usePage } from "@inertiajs/react"
import AppLayout from "@/layouts/app-layout"
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Progress } from "@/components/ui/progress"
import { Button } from "@/components/ui/button"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { 
    Server, 
    Database, 
    HardDrive, 
    Zap, 
    Activity, 
    CheckCircle2, 
    AlertCircle, 
    XCircle,
    Terminal,
    RefreshCw,
    Trash2,
    Settings,
    Route,
    Eye,
    Wrench,
    PlayCircle,
    FileText,
    Clock
} from "lucide-react"
import { useState, useEffect } from "react"
import { route } from "@/plugins/ziggy"

interface SystemInfo {
    php_version: string
    laravel_version: string
    server_software: string
    server_name: string
    timezone: string
    environment: string
    debug_mode: boolean
    memory_limit: string
    max_execution_time: string
    upload_max_filesize: string
    post_max_size: string
}

interface DatabaseInfo {
    driver: string
    version: string
    size_mb: string | number
    connection: string
    status: string
    error?: string
}

interface StorageInfo {
    local: {
        total_gb: number
        used_gb: number
        free_gb: number
        usage_percent: number
    }
    public: {
        total_gb: number
        used_gb: number
        free_gb: number
        usage_percent: number
    }
    status: string
    error?: string
}

interface CacheInfo {
    driver: string
    status: string
    working: boolean
    error?: string
}

interface PerformanceMetrics {
    database_query_ms: number
    cache_operation_ms: number
    total_page_load_ms: number
    status: string
    error?: string
}

interface LaravelAbout {
    environment: {
        application_name: string
        laravel_version: string
        php_version: string
        composer_version: string
        environment: string
        debug_mode: string
        url: string
        maintenance_mode: string
        timezone: string
        locale: string
    }
    cache: {
        config: string
        events: string
        routes: string
        views: string
    }
    drivers: {
        broadcasting: string
        cache: string
        database: string
        logs: string
        mail: string
        queue: string
        session: string
    }
    storage: {
        public_storage_linked: boolean
    }
    spatie_permissions?: {
        version: string
        features: string
    }
    error?: string
}

interface Process {
    name: string
    type: string
    status: string
    message?: string
    error?: string
}

interface LogFile {
    name: string
    path: string
    size: number
    size_formatted: string
    modified: number
    modified_formatted: string
    is_today?: boolean
}

interface LogsInfo {
    log_files: LogFile[]
    total_files: number
    today_log?: string
    error?: string
}

interface LogEntry {
    timestamp: string
    environment: string
    level: string
    type: string
    message: string
    raw: string
}

interface HealthData {
    system: SystemInfo
    database: DatabaseInfo
    storage: StorageInfo
    cache: CacheInfo
    performance: PerformanceMetrics
    laravel: LaravelAbout
    processes: Process[]
    logs: LogsInfo
}

interface Props {
    health: HealthData
}

function StatusBadge({ status }: { status: string }) {
    if (status === 'healthy' || status === 'Connected' || status === 'working' || status === 'CACHED' || status === 'ON' || status === 'running' || status === 'configured' || status === 'installed') {
        return <Badge variant="default" className="bg-green-500"><CheckCircle2 className="w-3 h-3 mr-1" />Gesund</Badge>
    }
    if (status === 'warning' || status === 'slow' || status === 'ENABLED' || status === 'unknown') {
        return <Badge variant="default" className="bg-yellow-500"><AlertCircle className="w-3 h-3 mr-1" />Warnung</Badge>
    }
    if (status === 'NOT CACHED' || status === 'NOT LINKED' || status === 'OFF' || status === 'not_configured') {
        return <Badge variant="outline"><AlertCircle className="w-3 h-3 mr-1" />Nicht aktiv</Badge>
    }
    return <Badge variant="destructive"><XCircle className="w-3 h-3 mr-1" />Fehler</Badge>
}

export default function SystemHealth({ health }: Props) {
    const { props } = usePage<{ flash?: { success?: string; error?: string } }>()
    const [executing, setExecuting] = useState<string | null>(null)
    const [message, setMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null)
    const [selectedLogFile, setSelectedLogFile] = useState<string>('')
    const [logType, setLogType] = useState<string>('all')
    const [logEntries, setLogEntries] = useState<LogEntry[]>([])
    const [loadingLogs, setLoadingLogs] = useState(false)

    // Handle flash messages
    useEffect(() => {
        if (props.flash?.success) {
            setMessage({ type: 'success', text: props.flash.success })
            setTimeout(() => setMessage(null), 5000)
        }
        if (props.flash?.error) {
            setMessage({ type: 'error', text: props.flash.error })
            setTimeout(() => setMessage(null), 5000)
        }
    }, [props.flash])

    // Auto-select today's log file on mount
    useEffect(() => {
        if (!selectedLogFile && health.logs.today_log) {
            // Check if today's log file exists in the list
            const todayFile = health.logs.log_files.find(f => f.name === health.logs.today_log || f.is_today)
            if (todayFile) {
                setSelectedLogFile(todayFile.name)
            } else if (health.logs.log_files.length > 0) {
                // Fallback to newest log file
                setSelectedLogFile(health.logs.log_files[0].name)
            }
        }
    }, [health.logs])

    // Load logs when file or type changes
    useEffect(() => {
        if (selectedLogFile) {
            loadLogs(selectedLogFile, logType)
        }
    }, [selectedLogFile, logType])

    const handleRunCommand = (command: string) => {
        setExecuting(command)
        setMessage(null)

        router.post(
            route('system-health.run-command'),
            { command },
            {
                onSuccess: () => {
                    setExecuting(null)
                    setTimeout(() => {
                        router.reload({ only: ['health'] })
                    }, 500)
                },
                onError: (errors: any) => {
                    setMessage({ type: 'error', text: errors.message || 'Error executing command' })
                    setExecuting(null)
                },
            }
        )
    }

    const loadLogs = async (file: string, type: string) => {
        setLoadingLogs(true)
        try {
            const response = await fetch(`${route('system-health.logs')}?file=${encodeURIComponent(file)}&type=${type}&lines=200`)
            const data = await response.json()
            if (data.entries) {
                setLogEntries(data.entries)
            } else {
                setLogEntries([])
            }
        } catch (error) {
            console.error('Error loading logs:', error)
            setLogEntries([])
        } finally {
            setLoadingLogs(false)
        }
    }

    const getLogTypeColor = (type: string) => {
        switch (type) {
            case 'error':
                return 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20'
            case 'warning':
                return 'text-yellow-600 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/20'
            case 'info':
                return 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20'
            case 'debug':
                return 'text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/20'
            default:
                return 'text-gray-600 dark:text-gray-400'
        }
    }

    const artisanCommands = [
        { command: 'cache:clear', label: 'Cache leeren', icon: Trash2, description: 'Löscht alle Cache-Daten' },
        { command: 'config:clear', label: 'Config leeren', icon: Settings, description: 'Löscht Config-Cache' },
        { command: 'route:clear', label: 'Routes leeren', icon: Route, description: 'Löscht Route-Cache' },
        { command: 'view:clear', label: 'Views leeren', icon: Eye, description: 'Löscht View-Cache' },
        { command: 'optimize:clear', label: 'Optimierung leeren', icon: RefreshCw, description: 'Löscht alle Optimierungs-Caches' },
        { command: 'optimize', label: 'Optimieren', icon: Wrench, description: 'Optimiert die Anwendung' },
        { command: 'queue:clear', label: 'Queue leeren', icon: Trash2, description: 'Löscht alle Queue-Jobs' },
    ]

    return (
        <AppLayout breadcrumbs={[{ title: "Dashboard", href: "/dashboard" }, { title: "System Gesundheit" }]}>
            <Head title="System Gesundheit" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-1xl font-bold tracking-tight">System Gesundheit</h1>
                    <p className="text-muted-foreground mt-2">
                        Überwachung und Status des Systems
                    </p>
                </div>

                {message && (
                    <div className={`p-4 rounded-md ${message.type === 'success' ? 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200' : 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200'}`}>
                        {message.text}
                    </div>
                )}

                <Tabs defaultValue="overview" className="space-y-4">
                    <TabsList className="grid w-full grid-cols-4">
                        <TabsTrigger value="overview">Übersicht</TabsTrigger>
                        <TabsTrigger value="processes">Prozesse</TabsTrigger>
                        <TabsTrigger value="logs">Logs</TabsTrigger>
                        <TabsTrigger value="commands">Befehle</TabsTrigger>
                    </TabsList>

                    {/* Overview Tab */}
                    <TabsContent value="overview" className="space-y-6">
                        {/* Laravel About Information */}
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <Terminal className="h-5 w-5" />
                                        <CardTitle>Laravel Informationen</CardTitle>
                                    </div>
                                    <StatusBadge status={health.laravel.environment.environment === 'production' ? 'healthy' : 'warning'} />
                                </div>
                                <CardDescription>Umgebungs- und Konfigurationsinformationen</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-6">
                                    {/* Environment */}
                                    <div>
                                        <h3 className="text-sm font-semibold mb-3">Umgebung</h3>
                                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                            <div>
                                                <p className="text-sm font-medium text-muted-foreground">Anwendungsname</p>
                                                <p className="text-lg font-semibold">{health.laravel.environment.application_name}</p>
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-muted-foreground">Laravel Version</p>
                                                <p className="text-lg font-semibold">{health.laravel.environment.laravel_version}</p>
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-muted-foreground">PHP Version</p>
                                                <p className="text-lg font-semibold">{health.laravel.environment.php_version}</p>
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-muted-foreground">Composer Version</p>
                                                <p className="text-lg font-semibold">{health.laravel.environment.composer_version}</p>
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-muted-foreground">Umgebung</p>
                                                <Badge variant={health.laravel.environment.environment === 'production' ? 'default' : 'outline'}>
                                                    {health.laravel.environment.environment}
                                                </Badge>
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-muted-foreground">Debug Modus</p>
                                                <Badge variant={health.laravel.environment.debug_mode === 'ENABLED' ? 'destructive' : 'default'}>
                                                    {health.laravel.environment.debug_mode}
                                                </Badge>
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-muted-foreground">URL</p>
                                                <p className="text-lg font-semibold">{health.laravel.environment.url}</p>
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-muted-foreground">Wartungsmodus</p>
                                                <Badge variant={health.laravel.environment.maintenance_mode === 'ON' ? 'destructive' : 'default'}>
                                                    {health.laravel.environment.maintenance_mode}
                                                </Badge>
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-muted-foreground">Zeitzone</p>
                                                <p className="text-lg font-semibold">{health.laravel.environment.timezone}</p>
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-muted-foreground">Sprache</p>
                                                <p className="text-lg font-semibold">{health.laravel.environment.locale}</p>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Cache Status */}
                                    <div>
                                        <h3 className="text-sm font-semibold mb-3">Cache Status</h3>
                                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                                            <div>
                                                <p className="text-sm font-medium text-muted-foreground">Config</p>
                                                <StatusBadge status={health.laravel.cache.config} />
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-muted-foreground">Events</p>
                                                <StatusBadge status={health.laravel.cache.events} />
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-muted-foreground">Routes</p>
                                                <StatusBadge status={health.laravel.cache.routes} />
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-muted-foreground">Views</p>
                                                <StatusBadge status={health.laravel.cache.views} />
                                            </div>
                                        </div>
                                    </div>

                                    {/* Drivers */}
                                    <div>
                                        <h3 className="text-sm font-semibold mb-3">Treiber</h3>
                                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                                            <div>
                                                <p className="text-sm font-medium text-muted-foreground">Broadcasting</p>
                                                <p className="text-lg font-semibold">{health.laravel.drivers.broadcasting}</p>
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-muted-foreground">Cache</p>
                                                <p className="text-lg font-semibold">{health.laravel.drivers.cache}</p>
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-muted-foreground">Datenbank</p>
                                                <p className="text-lg font-semibold">{health.laravel.drivers.database}</p>
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-muted-foreground">Logs</p>
                                                <p className="text-lg font-semibold">{health.laravel.drivers.logs}</p>
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-muted-foreground">Mail</p>
                                                <p className="text-lg font-semibold">{health.laravel.drivers.mail}</p>
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-muted-foreground">Queue</p>
                                                <p className="text-lg font-semibold">{health.laravel.drivers.queue}</p>
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-muted-foreground">Session</p>
                                                <p className="text-lg font-semibold">{health.laravel.drivers.session}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* System Information */}
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <Server className="h-5 w-5" />
                                        <CardTitle>System-Informationen</CardTitle>
                                    </div>
                                    <StatusBadge status={health.system.environment === 'production' ? 'healthy' : 'warning'} />
                                </div>
                                <CardDescription>PHP, Laravel und Server-Konfiguration</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                    <div>
                                        <p className="text-sm font-medium text-muted-foreground">PHP Version</p>
                                        <p className="text-lg font-semibold">{health.system.php_version}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-muted-foreground">Laravel Version</p>
                                        <p className="text-lg font-semibold">{health.system.laravel_version}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-muted-foreground">Umgebung</p>
                                        <p className="text-lg font-semibold">{health.system.environment}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-muted-foreground">Debug Modus</p>
                                        <Badge variant={health.system.debug_mode ? "destructive" : "default"}>
                                            {health.system.debug_mode ? "Aktiv" : "Inaktiv"}
                                        </Badge>
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-muted-foreground">Zeitzone</p>
                                        <p className="text-lg font-semibold">{health.system.timezone}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-muted-foreground">Memory Limit</p>
                                        <p className="text-lg font-semibold">{health.system.memory_limit}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Database Information */}
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <Database className="h-5 w-5" />
                                        <CardTitle>Datenbank</CardTitle>
                                    </div>
                                    <StatusBadge status={health.database.status} />
                                </div>
                                <CardDescription>Datenbank-Verbindung und Status</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                                    <div>
                                        <p className="text-sm font-medium text-muted-foreground">Treiber</p>
                                        <p className="text-lg font-semibold">{health.database.driver}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-muted-foreground">Version</p>
                                        <p className="text-lg font-semibold">{health.database.version}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-muted-foreground">Größe</p>
                                        <p className="text-lg font-semibold">
                                            {typeof health.database.size_mb === 'number' 
                                                ? `${health.database.size_mb} MB` 
                                                : health.database.size_mb}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-muted-foreground">Verbindung</p>
                                        <p className="text-lg font-semibold">{health.database.connection}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Storage Information */}
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <HardDrive className="h-5 w-5" />
                                        <CardTitle>Speicher</CardTitle>
                                    </div>
                                    <StatusBadge status={health.storage.status} />
                                </div>
                                <CardDescription>Festplatten-Speicher und Nutzung</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <div>
                                    <div className="flex items-center justify-between mb-2">
                                        <p className="text-sm font-medium">Lokaler Speicher</p>
                                        <p className="text-sm text-muted-foreground">
                                            {health.storage.local.used_gb} GB / {health.storage.local.total_gb} GB
                                        </p>
                                    </div>
                                    <Progress value={health.storage.local.usage_percent} className="h-2" />
                                    <p className="text-xs text-muted-foreground mt-1">
                                        {health.storage.local.free_gb} GB frei ({health.storage.local.usage_percent.toFixed(1)}% genutzt)
                                    </p>
                                </div>
                                <div>
                                    <div className="flex items-center justify-between mb-2">
                                        <p className="text-sm font-medium">Öffentlicher Speicher</p>
                                        <p className="text-sm text-muted-foreground">
                                            {health.storage.public.used_gb} GB / {health.storage.public.total_gb} GB
                                        </p>
                                    </div>
                                    <Progress value={health.storage.public.usage_percent} className="h-2" />
                                    <p className="text-xs text-muted-foreground mt-1">
                                        {health.storage.public.free_gb} GB frei ({health.storage.public.usage_percent.toFixed(1)}% genutzt)
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Cache Information */}
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <Zap className="h-5 w-5" />
                                        <CardTitle>Cache</CardTitle>
                                    </div>
                                    <StatusBadge status={health.cache.working ? 'healthy' : 'error'} />
                                </div>
                                <CardDescription>Cache-System Status</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <p className="text-sm font-medium text-muted-foreground">Treiber</p>
                                        <p className="text-lg font-semibold">{health.cache.driver}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-muted-foreground">Status</p>
                                        <StatusBadge status={health.cache.working ? 'working' : 'error'} />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Performance Metrics */}
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <Activity className="h-5 w-5" />
                                        <CardTitle>Performance</CardTitle>
                                    </div>
                                    <StatusBadge status={health.performance.status} />
                                </div>
                                <CardDescription>System-Performance-Metriken</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="grid gap-4 md:grid-cols-3">
                                    <div>
                                        <p className="text-sm font-medium text-muted-foreground">Datenbank-Abfrage</p>
                                        <p className="text-lg font-semibold">{health.performance.database_query_ms} ms</p>
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-muted-foreground">Cache-Operation</p>
                                        <p className="text-lg font-semibold">{health.performance.cache_operation_ms} ms</p>
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-muted-foreground">Gesamte Seitenladezeit</p>
                                        <p className="text-lg font-semibold">{health.performance.total_page_load_ms} ms</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Processes Tab */}
                    <TabsContent value="processes" className="space-y-6">
                        <Card>
                            <CardHeader>
                                <div className="flex items-center gap-2">
                                    <PlayCircle className="h-5 w-5" />
                                    <CardTitle>Hintergrundprozesse</CardTitle>
                                </div>
                                <CardDescription>Laufende Prozesse und Services</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    {health.processes.map((process, index) => (
                                        <Card key={index}>
                                            <CardContent className="pt-6">
                                                <div className="flex items-start justify-between">
                                                    <div className="flex-1">
                                                        <div className="flex items-center gap-2 mb-2">
                                                            <h4 className="font-semibold">{process.name}</h4>
                                                            <StatusBadge status={process.status} />
                                                        </div>
                                                        <p className="text-sm text-muted-foreground mb-1">
                                                            Typ: <span className="font-medium">{process.type}</span>
                                                        </p>
                                                        {process.message && (
                                                            <p className="text-sm text-muted-foreground">{process.message}</p>
                                                        )}
                                                        {process.error && (
                                                            <p className="text-sm text-red-600 dark:text-red-400 mt-2">{process.error}</p>
                                                        )}
                                                    </div>
                                                </div>
                                            </CardContent>
                                        </Card>
                                    ))}
                                    {health.processes.length === 0 && (
                                        <p className="text-sm text-muted-foreground text-center py-8">
                                            Keine Hintergrundprozesse gefunden
                                        </p>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Logs Tab */}
                    <TabsContent value="logs" className="space-y-6">
                        <Card>
                            <CardHeader>
                                <div className="flex items-center gap-2">
                                    <FileText className="h-5 w-5" />
                                    <CardTitle>Log-Dateien</CardTitle>
                                </div>
                                <CardDescription>Anzeige und Filterung von Log-Dateien</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex gap-4">
                                    <div className="flex-1">
                                        <label className="text-sm font-medium mb-2 block">Log-Datei auswählen</label>
                                        <Select value={selectedLogFile} onValueChange={setSelectedLogFile}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Datei auswählen" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {health.logs.log_files.map((file) => (
                                                    <SelectItem key={file.name} value={file.name}>
                                                        {file.name} {file.is_today && '(Heute)'} ({file.size_formatted}) - {file.modified_formatted}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="w-48">
                                        <label className="text-sm font-medium mb-2 block">Typ filtern</label>
                                        <Select value={logType} onValueChange={setLogType}>
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">Alle</SelectItem>
                                                <SelectItem value="error">Fehler</SelectItem>
                                                <SelectItem value="warning">Warnungen</SelectItem>
                                                <SelectItem value="info">Info</SelectItem>
                                                <SelectItem value="debug">Debug</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>

                                {loadingLogs && (
                                    <div className="flex items-center justify-center py-8">
                                        <RefreshCw className="h-6 w-6 animate-spin text-muted-foreground" />
                                    </div>
                                )}

                                {!loadingLogs && logEntries.length > 0 && (
                                    <div className="border rounded-lg max-h-[600px] overflow-y-auto">
                                        <div className="divide-y">
                                            {logEntries.map((entry, index) => (
                                                <div key={index} className={`p-3 ${getLogTypeColor(entry.type)}`}>
                                                    <div className="flex items-start justify-between mb-1">
                                                        <div className="flex items-center gap-2">
                                                            <Badge variant="outline" className="text-xs">
                                                                {entry.type.toUpperCase()}
                                                            </Badge>
                                                            <span className="text-xs text-muted-foreground">{entry.timestamp}</span>
                                                        </div>
                                                        <span className="text-xs text-muted-foreground">{entry.environment}</span>
                                                    </div>
                                                    <p className="text-sm font-mono whitespace-pre-wrap break-words">{entry.message}</p>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}

                                {!loadingLogs && selectedLogFile && logEntries.length === 0 && (
                                    <div className="text-center py-8 text-muted-foreground">
                                        Keine Log-Einträge gefunden
                                    </div>
                                )}

                                {!selectedLogFile && (
                                    <div className="text-center py-8 text-muted-foreground">
                                        Bitte wählen Sie eine Log-Datei aus
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Commands Tab */}
                    <TabsContent value="commands" className="space-y-6">
                        <Card>
                            <CardHeader>
                                <div className="flex items-center gap-2">
                                    <Terminal className="h-5 w-5" />
                                    <CardTitle>Artisan Befehle</CardTitle>
                                </div>
                                <CardDescription>Wichtige Artisan-Befehle ausführen</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                    {artisanCommands.map((cmd) => (
                                        <div key={cmd.command} className="p-4 border rounded-lg">
                                            <div className="flex items-start justify-between mb-2">
                                                <div className="flex items-center gap-2">
                                                    <cmd.icon className="h-4 w-4 text-muted-foreground" />
                                                    <h4 className="font-semibold">{cmd.label}</h4>
                                                </div>
                                            </div>
                                            <p className="text-sm text-muted-foreground mb-3">{cmd.description}</p>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleRunCommand(cmd.command)}
                                                disabled={executing === cmd.command}
                                                className="w-full"
                                            >
                                                {executing === cmd.command ? (
                                                    <>
                                                        <RefreshCw className="mr-2 h-4 w-4 animate-spin" />
                                                        Wird ausgeführt...
                                                    </>
                                                ) : (
                                                    <>
                                                        <Terminal className="mr-2 h-4 w-4" />
                                                        Ausführen
                                                    </>
                                                )}
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    )
}
