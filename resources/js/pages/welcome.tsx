import { Head, Link } from '@inertiajs/react';
import { FileText, Users, BarChart3, Mail, Clock, Shield, CheckCircle2, ArrowRight, Zap, TrendingUp, Building2 } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface WelcomeProps {
    canLogin: boolean;
}

export default function Welcome({ canLogin }: WelcomeProps) {
    return (
        <>
            <Head title="Professionelle Rechnungsverwaltung" />
            <div className="min-h-screen bg-blue-600">
                {/* Decorative Background Elements */}
                <div className="fixed inset-0 overflow-hidden pointer-events-none">
                    <div className="absolute top-20 left-20 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
                    <div className="absolute bottom-20 right-20 h-96 w-96 rounded-full bg-white/10 blur-3xl"></div>
                    <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 h-[600px] w-[600px] rounded-full bg-white/5 blur-3xl"></div>
                </div>

                {/* Navigation */}
                <nav className="fixed top-0 left-0 right-0 z-50 border-b border-white/20 bg-white/10 backdrop-blur-xl">
                    <div className="container mx-auto px-4">
                        <div className="flex h-16 items-center justify-between">
                            <div className="flex items-center space-x-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm shadow-lg">
                                    <FileText className="h-6 w-6 text-white" />
                                </div>
                                <span className="text-2xl font-bold text-white">
                                    AndoBill
                                </span>
                            </div>
                            {canLogin && (
                                <Link href="/login">
                                    <Button size="lg" className="bg-white/20 backdrop-blur-sm hover:bg-white/30 text-white border border-white/30 shadow-lg">
                                        Anmelden
                                        <ArrowRight className="ml-2 h-4 w-4" />
                                    </Button>
                                </Link>
                            )}
                        </div>
                    </div>
                </nav>

                {/* Hero Section */}
                <section className="relative overflow-hidden pt-32 pb-20">
                    <div className="container relative mx-auto px-4">
                        <div className="mx-auto max-w-4xl text-center text-white">
                            <div className="mb-6 inline-flex items-center rounded-full border border-white/30 bg-white/10 px-6 py-2.5 text-base font-medium backdrop-blur-sm shadow-lg">
                                <Shield className="mr-2 h-5 w-5" />
                                Sichere und zuverlässige Rechnungsverwaltung
                            </div>
                            
                            <h1 className="mb-6 text-5xl font-extrabold leading-tight tracking-tight sm:text-6xl lg:text-7xl">
                                Professionelle
                                <span className="block">
                                    Rechnungsverwaltung
                                </span>
                            </h1>
                            
                            <p className="mb-10 text-xl leading-relaxed text-white/90">
                                Erstellen, versenden und verwalten Sie Ihre Rechnungen effizient und sicher. 
                                Mit AndoBill haben Sie alles unter Kontrolle.
                            </p>

                            {canLogin && (
                                <div className="flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
                                    <Link href="/login">
                                        <Button size="lg" className="h-14 px-8 text-lg bg-white/20 backdrop-blur-sm hover:bg-white/30 text-white border border-white/30 shadow-xl">
                                            Jetzt starten
                                            <ArrowRight className="ml-2 h-5 w-5" />
                                        </Button>
                                    </Link>
                                </div>
                            )}

                            <div className="mt-16 grid gap-6 sm:grid-cols-3">
                                <div className="group relative overflow-hidden rounded-2xl border border-white/20 bg-white/10 p-6 shadow-xl backdrop-blur-sm transition-all hover:bg-white/20">
                                    <div className="mb-2 inline-flex rounded-lg bg-white/20 p-2.5 shadow-lg">
                                        <TrendingUp className="h-6 w-6" />
                                    </div>
                                    <div className="text-3xl font-bold">99.9%</div>
                                    <div className="mt-2 text-sm text-white/80">Verfügbarkeit</div>
                                </div>
                                <div className="group relative overflow-hidden rounded-2xl border border-white/20 bg-white/10 p-6 shadow-xl backdrop-blur-sm transition-all hover:bg-white/20">
                                    <div className="mb-2 inline-flex rounded-lg bg-white/20 p-2.5 shadow-lg">
                                        <Shield className="h-6 w-6" />
                                    </div>
                                    <div className="text-3xl font-bold">DSGVO</div>
                                    <div className="mt-2 text-sm text-white/80">Konform</div>
                                </div>
                                <div className="group relative overflow-hidden rounded-2xl border border-white/20 bg-white/10 p-6 shadow-xl backdrop-blur-sm transition-all hover:bg-white/20">
                                    <div className="mb-2 inline-flex rounded-lg bg-white/20 p-2.5 shadow-lg">
                                        <Clock className="h-6 w-6" />
                                    </div>
                                    <div className="text-3xl font-bold">24/7</div>
                                    <div className="mt-2 text-sm text-white/80">Verfügbar</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Features Section */}
                <section className="relative py-20">
                    <div className="container mx-auto px-4">
                        <div className="mb-16 text-center text-white">
                            <h2 className="mb-4 text-4xl font-bold">
                                Alles was Sie brauchen
                            </h2>
                            <p className="text-xl text-white/90">
                                Umfassende Funktionen für Ihre Rechnungsverwaltung
                            </p>
                        </div>

                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            {/* Feature 1 */}
                            <div className="group relative overflow-hidden rounded-2xl border border-white/20 bg-white/10 p-8 shadow-xl backdrop-blur-sm transition-all hover:bg-white/20">
                                <div className="relative">
                                    <div className="mb-4 inline-flex h-14 w-14 items-center justify-center rounded-xl bg-white/20 shadow-lg">
                                        <FileText className="h-7 w-7 text-white" />
                                    </div>
                                    <h3 className="mb-3 text-xl font-semibold text-white">
                                        Rechnungen & Angebote
                                    </h3>
                                    <p className="text-white/80 text-base">
                                        Professionelle Dokumente in Sekunden erstellen
                                    </p>
                                </div>
                            </div>

                            {/* Feature 2 */}
                            <div className="group relative overflow-hidden rounded-2xl border border-white/20 bg-white/10 p-8 shadow-xl backdrop-blur-sm transition-all hover:bg-white/20">
                                <div className="relative">
                                    <div className="mb-4 inline-flex h-14 w-14 items-center justify-center rounded-xl bg-white/20 shadow-lg">
                                        <Users className="h-7 w-7 text-white" />
                                    </div>
                                    <h3 className="mb-3 text-xl font-semibold text-white">
                                        Kundenverwaltung
                                    </h3>
                                    <p className="text-white/80 text-base">
                                        Zentrale Verwaltung mit Historie und schnellem Zugriff
                                    </p>
                                </div>
                            </div>

                            {/* Feature 3 */}
                            <div className="group relative overflow-hidden rounded-2xl border border-white/20 bg-white/10 p-8 shadow-xl backdrop-blur-sm transition-all hover:bg-white/20">
                                <div className="relative">
                                    <div className="mb-4 inline-flex h-14 w-14 items-center justify-center rounded-xl bg-white/20 shadow-lg">
                                        <Mail className="h-7 w-7 text-white" />
                                    </div>
                                    <h3 className="mb-3 text-xl font-semibold text-white">
                                        E-Mail Versand
                                    </h3>
                                    <p className="text-white/80 text-base">
                                        Direkter Versand per E-Mail mit professionellen Vorlagen
                                    </p>
                                </div>
                            </div>

                            {/* Feature 4 */}
                            <div className="group relative overflow-hidden rounded-2xl border border-white/20 bg-white/10 p-8 shadow-xl backdrop-blur-sm transition-all hover:bg-white/20">
                                <div className="relative">
                                    <div className="mb-4 inline-flex h-14 w-14 items-center justify-center rounded-xl bg-white/20 shadow-lg">
                                        <ArrowRight className="h-7 w-7 text-white" />
                                    </div>
                                    <h3 className="mb-3 text-xl font-semibold text-white">
                                        Automatisches Mahnwesen
                                    </h3>
                                    <p className="text-white/80 text-base">
                                        Rechtssicherer Mahnprozess nach deutschem Standard
                                    </p>
                                </div>
                            </div>

                            {/* Feature 5 */}
                            <div className="group relative overflow-hidden rounded-2xl border border-white/20 bg-white/10 p-8 shadow-xl backdrop-blur-sm transition-all hover:bg-white/20">
                                <div className="relative">
                                    <div className="mb-4 inline-flex h-14 w-14 items-center justify-center rounded-xl bg-white/20 shadow-lg">
                                        <BarChart3 className="h-7 w-7 text-white" />
                                    </div>
                                    <h3 className="mb-3 text-xl font-semibold text-white">
                                        Auswertungen & Reports
                                    </h3>
                                    <p className="text-white/80 text-base">
                                        Umfassende Statistiken zu Umsätzen und offenen Posten
                                    </p>
                                </div>
                            </div>

                            {/* Feature 6 */}
                            <div className="group relative overflow-hidden rounded-2xl border border-white/20 bg-white/10 p-8 shadow-xl backdrop-blur-sm transition-all hover:bg-white/20">
                                <div className="relative">
                                    <div className="mb-4 inline-flex h-14 w-14 items-center justify-center rounded-xl bg-white/20 shadow-lg">
                                        <Zap className="h-7 w-7 text-white" />
                                    </div>
                                    <h3 className="mb-3 text-xl font-semibold text-white">
                                        Schnell & Effizient
                                    </h3>
                                    <p className="text-white/80 text-base">
                                        Moderne Benutzeroberfläche für maximale Produktivität
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Call to Action Section */}
                <section className="relative py-20">
                    <div className="container mx-auto px-4">
                        <div className="mx-auto max-w-4xl text-center">
                            <div className="relative overflow-hidden rounded-3xl border border-white/30 bg-white/10 p-12 shadow-2xl backdrop-blur-md">
                                <div className="relative text-white">
                                    <div className="mb-6 inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-white/20 shadow-lg">
                                        <Building2 className="h-8 w-8" />
                                    </div>
                                    <h2 className="mb-4 text-4xl font-bold">Starten Sie noch heute</h2>
                                    <p className="mb-8 text-xl text-white/90">
                                        Verbessern Sie Ihre Rechnungsverwaltung mit AndoBill und konzentrieren Sie sich auf Ihr Kerngeschäft.
                                    </p>
                                    {canLogin && (
                                        <Link href="/login">
                                            <Button size="lg" className="h-14 px-8 text-lg bg-white text-blue-600 hover:bg-gray-100 shadow-xl">
                                                Zur Anmeldung
                                                <ArrowRight className="ml-2 h-5 w-5" />
                                            </Button>
                                        </Link>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Footer */}
                <footer className="relative border-t border-white/20 bg-white/5 py-12 backdrop-blur-xl">
                    <div className="container mx-auto px-4">
                        <div className="flex flex-col items-center justify-between gap-6 text-white md:flex-row">
                            <div className="flex items-center space-x-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 shadow-lg">
                                    <FileText className="h-6 w-6" />
                                </div>
                                <span className="text-xl font-bold">
                                    AndoBill
                                </span>
                            </div>
                            <p className="text-sm text-white/70">
                                © {new Date().getFullYear()} AndoBill. Alle Rechte vorbehalten.
                            </p>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
