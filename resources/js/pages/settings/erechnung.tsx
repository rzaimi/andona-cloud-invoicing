import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { Shield, FileText, Check } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

interface Settings {
    erechnung_enabled: boolean;
    xrechnung_enabled: boolean;
    zugferd_enabled: boolean;
    zugferd_profile: string;
    business_process_id: string | null;
    electronic_address_scheme: string | null;
    electronic_address: string | null;
}

interface Props {
    settings: Settings;
}

export default function ERechnungSettings({ settings }: Props) {
    const { data, setData, post, processing, errors } = useForm<Settings>({
        erechnung_enabled: settings.erechnung_enabled || false,
        xrechnung_enabled: settings.xrechnung_enabled !== false,
        zugferd_enabled: settings.zugferd_enabled !== false,
        zugferd_profile: settings.zugferd_profile || 'EN16931',
        business_process_id: settings.business_process_id || '',
        electronic_address_scheme: settings.electronic_address_scheme || 'EM',
        electronic_address: settings.electronic_address || '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('settings.erechnung.update'));
    };

    return (
        <AppLayout>
            <Head title="E-Rechnung Einstellungen" />

            <div className="mx-auto max-w-4xl space-y-6 p-6">
                <div>
                    <h1 className="text-3xl font-bold">E-Rechnung Einstellungen</h1>
                    <p className="mt-2 text-muted-foreground">
                        Konfigurieren Sie die elektronische Rechnungsstellung nach EU-Standard EN 16931
                    </p>
                </div>

                <Alert>
                    <Shield className="h-4 w-4" />
                    <AlertTitle>Wichtig: Gesetzliche Anforderungen ab 2025</AlertTitle>
                    <AlertDescription>
                        Ab dem 1. Januar 2025 müssen Unternehmen in Deutschland E-Rechnungen empfangen können. 
                        Ab 2027/2028 müssen auch ausgestellte Rechnungen im E-Rechnung-Format übermittelt werden.
                    </AlertDescription>
                </Alert>

                <form onSubmit={submit} className="space-y-6">
                    {/* Main Settings */}
                    <Card>
                        <CardHeader>
                            <CardTitle>E-Rechnung aktivieren</CardTitle>
                            <CardDescription>
                                Aktivieren Sie die elektronische Rechnungsstellung für Ihr Unternehmen
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div className="flex items-center justify-between">
                                <div className="space-y-0.5">
                                    <Label htmlFor="erechnung_enabled">E-Rechnung Funktionen aktivieren</Label>
                                    <p className="text-sm text-muted-foreground">
                                        Download-Buttons für XRechnung und ZUGFeRD werden angezeigt
                                    </p>
                                </div>
                                <Switch
                                    id="erechnung_enabled"
                                    checked={data.erechnung_enabled}
                                    onCheckedChange={(checked) => setData('erechnung_enabled', checked)}
                                />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Format Settings */}
                    {data.erechnung_enabled && (
                        <Card>
                            <CardHeader>
                                <CardTitle>E-Rechnung Formate</CardTitle>
                                <CardDescription>
                                    Wählen Sie die unterstützten E-Rechnung Formate
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <div className="flex items-center justify-between">
                                    <div className="space-y-0.5">
                                        <Label htmlFor="xrechnung_enabled" className="flex items-center gap-2">
                                            <FileText className="h-4 w-4" />
                                            XRechnung (XML)
                                        </Label>
                                        <p className="text-sm text-muted-foreground">
                                            Reine XML-Datei nach XRechnung-Standard (empfohlen für B2G)
                                        </p>
                                    </div>
                                    <Switch
                                        id="xrechnung_enabled"
                                        checked={data.xrechnung_enabled}
                                        onCheckedChange={(checked) => setData('xrechnung_enabled', checked)}
                                    />
                                </div>

                                <div className="flex items-center justify-between">
                                    <div className="space-y-0.5">
                                        <Label htmlFor="zugferd_enabled" className="flex items-center gap-2">
                                            <FileText className="h-4 w-4" />
                                            ZUGFeRD (PDF + XML)
                                        </Label>
                                        <p className="text-sm text-muted-foreground">
                                            PDF/A mit eingebettetem XML (empfohlen für B2B - menschenlesbar + maschinenlesbar)
                                        </p>
                                    </div>
                                    <Switch
                                        id="zugferd_enabled"
                                        checked={data.zugferd_enabled}
                                        onCheckedChange={(checked) => setData('zugferd_enabled', checked)}
                                    />
                                </div>

                                {data.zugferd_enabled && (
                                    <div className="space-y-2">
                                        <Label htmlFor="zugferd_profile">ZUGFeRD Profil</Label>
                                        <Select
                                            value={data.zugferd_profile}
                                            onValueChange={(value) => setData('zugferd_profile', value)}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Profil wählen" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="MINIMUM">MINIMUM - Minimale Informationen</SelectItem>
                                                <SelectItem value="BASIC">BASIC - Grundlegende Informationen</SelectItem>
                                                <SelectItem value="EN16931">EN 16931 - EU-Standard (empfohlen)</SelectItem>
                                                <SelectItem value="EXTENDED">EXTENDED - Erweiterte Informationen</SelectItem>
                                                <SelectItem value="XRECHNUNG">XRechnung - Deutsche B2G Variante</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p className="text-sm text-muted-foreground">
                                            EN 16931 ist der empfohlene Standard für die meisten Anwendungsfälle
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    )}

                    {/* Advanced Settings */}
                    {data.erechnung_enabled && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Erweiterte Einstellungen</CardTitle>
                                <CardDescription>
                                    Optionale Felder für spezielle Anforderungen
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="business_process_id">Business Process ID (optional)</Label>
                                    <Input
                                        id="business_process_id"
                                        value={data.business_process_id || ''}
                                        onChange={(e) => setData('business_process_id', e.target.value)}
                                        placeholder="z.B. urn:fdc:peppol.eu:2017:poacc:billing:01:1.0"
                                    />
                                    <p className="text-sm text-muted-foreground">
                                        Erforderlich für bestimmte B2G-Prozesse
                                    </p>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="electronic_address_scheme">Elektronische Adresse Schema</Label>
                                        <Select
                                            value={data.electronic_address_scheme || 'EM'}
                                            onValueChange={(value) => setData('electronic_address_scheme', value)}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Schema wählen" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="EM">EM - E-Mail</SelectItem>
                                                <SelectItem value="0088">0088 - GLN (Global Location Number)</SelectItem>
                                                <SelectItem value="0060">0060 - DUNS Number</SelectItem>
                                                <SelectItem value="9930">9930 - Leitweg-ID</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="electronic_address">Elektronische Adresse</Label>
                                        <Input
                                            id="electronic_address"
                                            value={data.electronic_address || ''}
                                            onChange={(e) => setData('electronic_address', e.target.value)}
                                            placeholder="z.B. rechnung@firma.de"
                                        />
                                    </div>
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    Die elektronische Adresse wird in der E-Rechnung hinterlegt
                                </p>
                            </CardContent>
                        </Card>
                    )}

                    {/* Info Box */}
                    {data.erechnung_enabled && (
                        <Card className="border-green-200 bg-green-50 dark:border-green-900 dark:bg-green-950">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-green-900 dark:text-green-100">
                                    <Check className="h-5 w-5" />
                                    E-Rechnung aktiviert
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="text-sm text-green-800 dark:text-green-200">
                                <ul className="list-disc space-y-1 pl-4">
                                    {data.xrechnung_enabled && (
                                        <li>XRechnung (XML) Download steht auf der Rechnungsseite zur Verfügung</li>
                                    )}
                                    {data.zugferd_enabled && (
                                        <li>ZUGFeRD (PDF+XML) Download steht auf der Rechnungsseite zur Verfügung</li>
                                    )}
                                    <li>Alle Rechnungen erfüllen den EU-Standard EN 16931</li>
                                    <li>Ihre Rechnungen sind rechtssicher für B2B und B2G</li>
                                </ul>
                            </CardContent>
                        </Card>
                    )}

                    <div className="flex justify-end gap-4">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Wird gespeichert...' : 'Einstellungen speichern'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}

