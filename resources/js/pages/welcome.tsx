import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';
import {
    FileText,
    ReceiptText,
    Users,
    Mail,
    CreditCard,
    BarChart3,
    Calendar,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

interface WelcomeProps {
    canLogin: boolean;
}

export default function Welcome({ canLogin }: WelcomeProps) {
    const { t } = useTranslation();
    const [impressumOpen, setImpressumOpen] = useState(false);
    const [datenschutzOpen, setDatenschutzOpen] = useState(false);
    const [demoDialogOpen, setDemoDialogOpen] = useState(false);

    const demoForm = useForm({
        name: '',
        email: '',
        company: '',
        phone: '',
        message: '',
    });

    const features = [
        {
            icon: ReceiptText,
            title: t('welcome.feat1Title'),
            tagline: t('welcome.feat1Tagline'),
            description: t('welcome.feat1Desc'),
            bullets: [t('welcome.feat1B1'), t('welcome.feat1B2'), t('welcome.feat1B3')],
        },
        {
            icon: Users,
            title: t('welcome.feat2Title'),
            tagline: t('welcome.feat2Tagline'),
            description: t('welcome.feat2Desc'),
            bullets: [t('welcome.feat2B1'), t('welcome.feat2B2'), t('welcome.feat2B3')],
        },
        {
            icon: Mail,
            title: t('welcome.feat3Title'),
            tagline: t('welcome.feat3Tagline'),
            description: t('welcome.feat3Desc'),
            bullets: [t('welcome.feat3B1'), t('welcome.feat3B2'), t('welcome.feat3B3')],
        },
        {
            icon: CreditCard,
            title: t('welcome.feat4Title'),
            tagline: t('welcome.feat4Tagline'),
            description: t('welcome.feat4Desc'),
            bullets: [t('welcome.feat4B1'), t('welcome.feat4B2'), t('welcome.feat4B3')],
        },
        {
            icon: BarChart3,
            title: t('welcome.feat5Title'),
            tagline: t('welcome.feat5Tagline'),
            description: t('welcome.feat5Desc'),
            bullets: [t('welcome.feat5B1'), t('welcome.feat5B2'), t('welcome.feat5B3')],
        },
        {
            icon: Calendar,
            title: t('welcome.feat6Title'),
            tagline: t('welcome.feat6Tagline'),
            description: t('welcome.feat6Desc'),
            bullets: [t('welcome.feat6B1'), t('welcome.feat6B2'), t('welcome.feat6B3')],
        },
    ];

    return (
        <>
            <Head title="AndoBill – Rechnungs- und Verwaltungssoftware" />
            <div
                className="min-h-screen text-white relative overflow-hidden"
                style={{ background: '#0B4194' }}
            >
                {/* Decorative Elements */}
                <div className="absolute inset-0">
                    <div className="absolute top-20 left-20 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
                    <div className="absolute bottom-20 right-20 h-96 w-96 rounded-full bg-white/10 blur-3xl"></div>
                    <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 h-[600px] w-[600px] rounded-full bg-white/5 blur-3xl"></div>
                </div>
                {/* Header */}
                <header className="sticky top-0 z-10 backdrop-blur-md border-b border-white/12 relative" style={{ background: 'rgba(11,65,148,.85)' }}>
                    <div className="container mx-auto px-4 sm:px-6 lg:px-8" style={{ maxWidth: '1120px', width: 'min(1120px, 92vw)' }}>
                        <nav className="flex items-center justify-between py-4 gap-4">
                            <Link href="#top" className="flex items-center gap-3 font-bold tracking-wide">
                                <div
                                    className="w-10 h-10 rounded-xl flex items-center justify-center"
                                    style={{ background: '#0B4194', boxShadow: '0 10px 24px rgba(11,65,148,.35)' }}
                                >
                                    <FileText className="h-5 w-5 text-white" />
                                </div>
                                <span>AndoBill</span>
                            </Link>

                            <div className="hidden md:flex items-center gap-4 text-sm text-white/80">
                                <a href="#leistungen" className="hover:text-white transition-colors">{t('welcome.navFeatures')}</a>
                                <a href="#zielgruppe" className="hover:text-white transition-colors">{t('welcome.navForWhom')}</a>
                                <a href="#vorteile" className="hover:text-white transition-colors">{t('welcome.navAdvantages')}</a>
                                <a href="#starten" className="hover:text-white transition-colors">{t('welcome.navStart')}</a>
                            </div>

                            {canLogin && (
                                <div className="flex items-center gap-2.5">
                                    <a
                                        href="#leistungen"
                                        className="px-4 py-3 rounded-xl border border-white/12 text-sm font-semibold transition-all hover:translate-y-[-1px] hover:bg-white/10 hover:border-white/18"
                                        style={{ background: 'rgba(255,255,255,.06)' }}
                                    >
                                        {t('welcome.learnMore')}
                                    </a>
                                    <Link
                                        href="/login"
                                        className="px-4 py-3 rounded-xl border border-white/12 text-sm font-semibold transition-all hover:translate-y-[-1px] hover:bg-white/10 hover:border-white/18"
                                        style={{ background: '#0B4194', boxShadow: '0 16px 40px rgba(11,65,148,.32)' }}
                                    >
                                        {t('welcome.startNow')}
                                    </Link>
                                </div>
                            )}
                        </nav>
                    </div>
                </header>

                <main id="top" className="relative z-10">
                    {/* Hero Section */}
                    <section className="py-16 sm:py-20">
                        <div className="container mx-auto px-4 sm:px-6 lg:px-8" style={{ maxWidth: '1120px', width: 'min(1120px, 92vw)' }}>
                            <div className="grid grid-cols-1 lg:grid-cols-[1.1fr_0.9fr] gap-8 items-center">
                                <div>
                                    <div
                                        className="inline-flex items-center gap-2.5 text-xs text-white/80 border border-white/12 rounded-full px-3 py-2 mb-3.5"
                                        style={{ background: 'rgba(255,255,255,.05)' }}
                                    >
                                        <span className="w-2.5 h-2.5 rounded-full" style={{ background: '#0B4194', boxShadow: '0 0 0 4px rgba(11,65,148,.15)' }} />
                                        <span>{t('welcome.tagline')}</span>
                                    </div>

                                    <h1 className="text-4xl sm:text-5xl lg:text-6xl font-bold leading-tight tracking-tight mb-2.5" style={{ letterSpacing: '-0.6px' }}>
                                        {t('welcome.headline')}
                                    </h1>

                                    <p className="text-base sm:text-lg text-white/80 mb-5 max-w-[58ch] leading-relaxed"
                                        dangerouslySetInnerHTML={{ __html: t('welcome.description') }}
                                    />

                                    <div className="flex flex-wrap gap-3 mt-5">
                                        {canLogin && (
                                            <Link
                                                href="/login"
                                                className="px-4 py-3 rounded-xl text-sm font-semibold transition-all hover:brightness-105 text-white"
                                                style={{ background: '#0B4194', boxShadow: '0 16px 40px rgba(11,65,148,.32)' }}
                                            >
                                                {t('welcome.startWithAndoBill')}
                                            </Link>
                                        )}
                                        <a
                                            href="#leistungen"
                                            className="px-4 py-3 rounded-xl border border-white/12 text-sm font-semibold transition-all hover:translate-y-[-1px] hover:bg-white/10 hover:border-white/18"
                                            style={{ background: 'rgba(255,255,255,.06)' }}
                                        >
                                            {t('welcome.viewFeatures')}
                                        </a>
                                    </div>

                                    <div className="flex flex-wrap gap-4 mt-4 text-xs text-white/80">
                                        <div className="flex items-center gap-2.5 px-3 py-2.5 border border-white/12 rounded-xl" style={{ background: 'rgba(255,255,255,.04)' }}>
                                            <ReceiptText className="w-4.5 h-4.5 text-white" />
                                            <span>{t('welcome.professionalDocs')}</span>
                                        </div>
                                        <div className="flex items-center gap-2.5 px-3 py-2.5 border border-white/12 rounded-xl" style={{ background: 'rgba(255,255,255,.04)' }}>
                                            <CreditCard className="w-4.5 h-4.5 text-white" />
                                            <span>{t('welcome.paymentOverview')}</span>
                                        </div>
                                        <div className="flex items-center gap-2.5 px-3 py-2.5 border border-white/12 rounded-xl" style={{ background: 'rgba(255,255,255,.04)' }}>
                                            <BarChart3 className="w-4.5 h-4.5 text-white" />
                                            <span>{t('welcome.reportsTagline')}</span>
                                        </div>
                                    </div>
                                </div>

                                {/* Hero Preview Card */}
                                <aside
                                    className="border border-white/12 rounded-[18px] p-4.5"
                                    style={{ background: 'linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.02))', boxShadow: '0 18px 60px rgba(0,0,0,.35)' }}
                                >
                                    <h3 className="text-base font-semibold mb-2">{t('welcome.previewTitle')}</h3>
                                    <p className="text-sm text-white/80 mb-3.5">{t('welcome.previewSubtitle')}</p>

                                    <div className="border border-dashed border-white/22 rounded-2xl p-3.5" style={{ background: 'rgba(11,18,32,.35)' }}>
                                        <div className="flex items-center justify-between py-2.5 border-b border-white/8 gap-3">
                                            <span className="text-xs px-2.5 py-1.5 rounded-full border border-white/12 text-white/80" style={{ background: 'rgba(255,255,255,.04)' }}>
                                                Rechnung #2025-0148
                                            </span>
                                            <span className="font-bold tracking-wide">1.240,00 €</span>
                                        </div>
                                        <div className="flex items-center justify-between py-2.5 border-b border-white/8 gap-3">
                                            <span className="text-xs px-2.5 py-1.5 rounded-full border border-white/12 text-white/80" style={{ background: 'rgba(255,255,255,.04)' }}>
                                                {t('welcome.previewStatus')}
                                            </span>
                                            <span className="text-xs px-2.5 py-1.5 rounded-full border border-white/12 text-white/80" style={{ background: 'rgba(255,255,255,.04)' }}>
                                                {t('welcome.previewPartial')}
                                            </span>
                                        </div>
                                        <div className="flex items-center justify-between py-2.5 border-b border-white/8 gap-3">
                                            <span className="text-xs px-2.5 py-1.5 rounded-full border border-white/12 text-white/80" style={{ background: 'rgba(255,255,255,.04)' }}>
                                                {t('welcome.previewOpen')}
                                            </span>
                                            <span className="font-bold tracking-wide">340,00 €</span>
                                        </div>
                                        <div className="flex items-center justify-between py-2.5 gap-3">
                                            <span className="text-xs px-2.5 py-1.5 rounded-full border border-white/12 text-white/80" style={{ background: 'rgba(255,255,255,.04)' }}>
                                                {t('welcome.previewNextDue')}
                                            </span>
                                            <span className="text-xs px-2.5 py-1.5 rounded-full border border-white/12 text-white/80" style={{ background: 'rgba(255,255,255,.04)' }}>
                                                03.01.2026
                                            </span>
                                        </div>
                                    </div>
                                </aside>
                            </div>
                        </div>
                    </section>

                    {/* Why Section */}
                    <section className="py-11">
                        <div className="container mx-auto px-4 sm:px-6 lg:px-8" style={{ maxWidth: '1120px', width: 'min(1120px, 92vw)' }}>
                            <div className="mb-4.5">
                                <h2 className="text-2xl font-bold mb-0" style={{ letterSpacing: '-0.2px' }}>{t('welcome.whyTitle')}</h2>
                                <p className="text-white/80 text-[15px] max-w-[70ch] mt-0">{t('welcome.whyDesc')}</p>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="border border-white/12 rounded-[18px] p-4.5" style={{ background: 'rgba(255,255,255,.04)' }}>
                                    <h3 className="text-base font-semibold mb-2">{t('welcome.focusTitle')}</h3>
                                    <ul className="list-disc list-inside text-white/80 text-sm space-y-2 ml-0 pl-4">
                                        <li>{t('welcome.focusBullet1')}</li>
                                        <li>{t('welcome.focusBullet2')}</li>
                                        <li>{t('welcome.focusBullet3')}</li>
                                    </ul>
                                </div>
                                <div className="border border-white/12 rounded-[18px] p-4.5" style={{ background: 'rgba(255,255,255,.04)' }}>
                                    <h3 className="text-base font-semibold mb-2">{t('welcome.scaleTitle')}</h3>
                                    <ul className="list-disc list-inside text-white/80 text-sm space-y-2 ml-0 pl-4">
                                        <li>{t('welcome.scaleBullet1')}</li>
                                        <li>{t('welcome.scaleBullet2')}</li>
                                        <li>{t('welcome.scaleBullet3')}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* Features Section */}
                    <section id="leistungen" className="py-11">
                        <div className="container mx-auto px-4 sm:px-6 lg:px-8" style={{ maxWidth: '1120px', width: 'min(1120px, 92vw)' }}>
                            <div className="mb-4.5">
                                <h2 className="text-2xl font-bold mb-0" style={{ letterSpacing: '-0.2px' }}>{t('welcome.featuresTitle')}</h2>
                                <p className="text-white/80 text-[15px] max-w-[70ch] mt-0">{t('welcome.featuresDesc')}</p>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                {features.map((feature, idx) => {
                                    const IconComponent = feature.icon;
                                    return (
                                        <article
                                            key={idx}
                                            className="border border-white/12 rounded-[18px] p-4.5 transition-all hover:translate-y-[-2px] hover:bg-white/6 hover:border-white/18"
                                            style={{ background: 'rgba(255,255,255,.04)' }}
                                        >
                                            <div className="flex gap-3 items-start mb-2">
                                                <div
                                                    className="w-10 h-10 rounded-xl flex items-center justify-center border"
                                                    style={{ background: 'rgba(11,65,148,.18)', borderColor: 'rgba(11,65,148,.25)' }}
                                                >
                                                    <IconComponent className="h-5 w-5 text-white" />
                                                </div>
                                                <div>
                                                    <h3 className="text-base font-semibold mb-0">{feature.title}</h3>
                                                    <p className="text-xs text-white/70 mt-0.5 mb-0">„{feature.tagline}"</p>
                                                </div>
                                            </div>
                                            <p className="text-sm text-white/80 mt-2.5 mb-0">{feature.description}</p>
                                            <ul className="list-disc list-inside text-sm text-white/80 mt-3 mb-0 pl-4.5 space-y-1.5">
                                                {feature.bullets.map((bullet, i) => (
                                                    <li key={i}>{bullet}</li>
                                                ))}
                                            </ul>
                                        </article>
                                    );
                                })}
                            </div>
                        </div>
                    </section>

                    {/* Audience Section */}
                    <section id="zielgruppe" className="py-11">
                        <div className="container mx-auto px-4 sm:px-6 lg:px-8" style={{ maxWidth: '1120px', width: 'min(1120px, 92vw)' }}>
                            <div className="mb-4.5">
                                <h2 className="text-2xl font-bold mb-0" style={{ letterSpacing: '-0.2px' }}>{t('welcome.audienceTitle')}</h2>
                                <p className="text-white/80 text-[15px] max-w-[70ch] mt-0">{t('welcome.audienceDesc')}</p>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="border border-white/12 rounded-[18px] p-4.5" style={{ background: 'rgba(255,255,255,.04)' }}>
                                    <h3 className="text-base font-semibold mb-2">{t('welcome.audienceGroupTitle')}</h3>
                                    <ul className="list-disc list-inside text-sm text-white/80 space-y-2 ml-0 pl-4">
                                        <li>{t('welcome.audienceB1')}</li>
                                        <li>{t('welcome.audienceB2')}</li>
                                        <li>{t('welcome.audienceB3')}</li>
                                        <li>{t('welcome.audienceB4')}</li>
                                    </ul>
                                </div>
                                <div id="vorteile" className="border border-white/12 rounded-[18px] p-4.5" style={{ background: 'rgba(255,255,255,.04)' }}>
                                    <h3 className="text-base font-semibold mb-2">{t('welcome.advantagesTitle')}</h3>
                                    <ul className="list-disc list-inside text-sm text-white/80 space-y-2 ml-0 pl-4">
                                        <li>{t('welcome.advantagesB1')}</li>
                                        <li>{t('welcome.advantagesB2')}</li>
                                        <li>{t('welcome.advantagesB3')}</li>
                                        <li>{t('welcome.advantagesB4')}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* CTA Section */}
                    <section id="starten" className="py-11 pb-16">
                        <div className="container mx-auto px-4 sm:px-6 lg:px-8" style={{ maxWidth: '1120px', width: 'min(1120px, 92vw)' }}>
                            <div
                                className="border rounded-3xl p-5.5 flex flex-wrap items-center justify-between gap-4"
                                style={{ borderColor: 'rgba(255,255,255,.16)', background: 'rgba(11,65,148,.18)', boxShadow: '0 18px 60px rgba(0,0,0,.35)' }}
                            >
                                <div className="max-w-[70ch]">
                                    <h2 className="text-2xl font-bold mb-1.5">{t('welcome.ctaTitle')}</h2>
                                    <p className="text-white/80 mb-0">{t('welcome.ctaDesc')}</p>
                                </div>
                                <div className="flex flex-wrap gap-2.5">
                                    {canLogin && (
                                        <button
                                            onClick={() => setDemoDialogOpen(true)}
                                            className="px-4 py-3 rounded-xl text-sm font-semibold transition-all hover:brightness-105"
                                            style={{ background: 'linear-gradient(135deg, #2f7df6, #00c2ff)', boxShadow: '0 16px 40px rgba(47,125,246,.32)', color: '#071225' }}
                                        >
                                            {t('welcome.requestDemo')}
                                        </button>
                                    )}
                                    <a
                                        href="#leistungen"
                                        className="px-4 py-3 rounded-xl border border-white/12 text-sm font-semibold transition-all hover:translate-y-[-1px] hover:bg-white/10 hover:border-white/18"
                                        style={{ background: 'rgba(255,255,255,.06)' }}
                                    >
                                        {t('welcome.viewFeatures')}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </section>
                </main>

                {/* Footer */}
                <footer className="border-t border-white/12 py-4.5 text-sm text-white/80 relative z-10">
                    <div className="container mx-auto px-4 sm:px-6 lg:px-8" style={{ maxWidth: '1120px', width: 'min(1120px, 92vw)' }}>
                        <div className="flex flex-wrap justify-between items-center gap-2.5">
                            <div>
                                <strong>AndoBill</strong> – {t('welcome.footerTagline')}
                            </div>
                            <div className="flex items-center gap-4">
                                <span>© {new Date().getFullYear()} AndoBill. {t('welcome.allRightsReserved')}</span>
                                <button onClick={() => setImpressumOpen(true)} className="text-white/80 hover:text-white transition-colors cursor-pointer">
                                    {t('welcome.impressum')}
                                </button>
                                <span className="text-white/30">|</span>
                                <button onClick={() => setDatenschutzOpen(true)} className="text-white/80 hover:text-white transition-colors cursor-pointer">
                                    {t('welcome.datenschutz')}
                                </button>
                            </div>
                        </div>
                    </div>
                </footer>

                {/* Impressum Modal */}
                <Dialog open={impressumOpen} onOpenChange={setImpressumOpen}>
                    <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle>Impressum</DialogTitle>
                            <DialogDescription>Angaben gemäß § 5 TMG</DialogDescription>
                        </DialogHeader>
                        <div className="space-y-6 text-sm text-gray-700">
                            <div>
                                <h3 className="font-semibold text-gray-900 mb-2">Verantwortlich für den Inhalt nach § 10 Absatz 3 MDStV:</h3>
                                <p className="mb-4">
                                    <strong>Andona GmbH</strong><br />
                                    Bahnhofstraße 16<br />
                                    63571 Gelnhausen<br />
                                    Deutschland
                                </p>
                                <p className="mb-2">
                                    E-Mail: <a href="mailto:info@andona.de" className="text-blue-600 hover:underline">info@andona.de</a><br />
                                    Telefon: <a href="tel:+4960515383658" className="text-blue-600 hover:underline">+49 (0) 6051 – 53 83 658</a><br />
                                    Fax: +49 (0) 6051 – 53 83 659
                                </p>
                            </div>
                            <div>
                                <h3 className="font-semibold text-gray-900 mb-2">Rechtliche Angaben:</h3>
                                <p className="mb-2">
                                    USt-IdNr.: DE369264419<br />
                                    St. Nr.: 019 228 35202<br />
                                    Finanzamt: Gelnhausen<br />
                                    Amtsgericht: Hanau, HRB 100017<br />
                                    Geschäftsführer: Lirim Ziberi
                                </p>
                            </div>
                            <div>
                                <h3 className="font-semibold text-gray-900 mb-2">Entwicklung & Implementierung:</h3>
                                <p className="mb-2">
                                    <strong>Andona Cloud</strong><br />
                                    Digitale Webagentur<br />
                                    Gohlstraße 1<br />
                                    70597 Stuttgart<br />
                                    Deutschland
                                </p>
                                <p className="mb-2">
                                    Telefon: <a href="tel:+4960515383658" className="text-blue-600 hover:underline">+49 (0) 6051 – 53 83 658</a><br />
                                    E-Mail: <a href="mailto:info@andona-cloud.de" className="text-blue-600 hover:underline">info@andona-cloud.de</a><br />
                                    Website:{' '}<a href="https://andona-cloud.de" target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:underline">https://andona-cloud.de</a>
                                </p>
                            </div>
                            <div>
                                <h3 className="font-semibold text-gray-900 mb-2">Haftungsausschluss:</h3>
                                <div className="space-y-3">
                                    <div>
                                        <h4 className="font-medium text-gray-900 mb-1">Haftung für Inhalte</h4>
                                        <p>Die Inhalte unserer Seiten wurden mit größter Sorgfalt erstellt. Für die Richtigkeit, Vollständigkeit und Aktualität der Inhalte können wir jedoch keine Gewähr übernehmen.</p>
                                    </div>
                                    <div>
                                        <h4 className="font-medium text-gray-900 mb-1">Haftung für Links</h4>
                                        <p>Unser Angebot enthält Links zu externen Webseiten Dritter, auf deren Inhalte wir keinen Einfluss haben.</p>
                                    </div>
                                    <div>
                                        <h4 className="font-medium text-gray-900 mb-1">Urheberrecht</h4>
                                        <p>Die durch die Seitenbetreiber erstellten Inhalte und Werke auf diesen Seiten unterliegen dem deutschen Urheberrecht.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </DialogContent>
                </Dialog>

                {/* Datenschutz Modal */}
                <Dialog open={datenschutzOpen} onOpenChange={setDatenschutzOpen}>
                    <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle>Datenschutzerklärung</DialogTitle>
                            <DialogDescription>Informationen gemäß Art. 13 DSGVO</DialogDescription>
                        </DialogHeader>
                        <div className="space-y-6 text-sm text-gray-700">
                            <div>
                                <h3 className="font-semibold text-gray-900 mb-2">1. Datenschutz auf einen Blick</h3>
                                <h4 className="font-medium text-gray-900 mb-1">Allgemeine Hinweise</h4>
                                <p>Die folgenden Hinweise geben einen einfachen Überblick darüber, was mit Ihren personenbezogenen Daten passiert, wenn Sie diese Website besuchen.</p>
                            </div>
                            <div>
                                <h3 className="font-semibold text-gray-900 mb-2">2. Verantwortliche Stelle</h3>
                                <p className="mb-2">
                                    <strong>Andona GmbH</strong><br />
                                    Bahnhofstraße 16, 63571 Gelnhausen<br />
                                    Telefon: <a href="tel:+4960515383658" className="text-blue-600 hover:underline">+49 (0) 6051 – 53 83 658</a><br />
                                    E-Mail: <a href="mailto:info@andona.de" className="text-blue-600 hover:underline">info@andona.de</a>
                                </p>
                            </div>
                            <div>
                                <h3 className="font-semibold text-gray-900 mb-2">3. Datenerfassung auf dieser Website</h3>
                                <h4 className="font-medium text-gray-900 mb-1">Cookies</h4>
                                <p>Die Internetseiten verwenden teilweise so genannte Cookies. Cookies dienen dazu, unser Angebot nutzerfreundlicher, effektiver und sicherer zu machen.</p>
                                <h4 className="font-medium text-gray-900 mb-1 mt-2">Server-Log-Dateien</h4>
                                <p>Der Provider der Seiten erhebt und speichert automatisch Informationen in so genannten Server-Log-Dateien.</p>
                            </div>
                            <div>
                                <h3 className="font-semibold text-gray-900 mb-2">4. Ihre Rechte</h3>
                                <p className="mb-2">Sie haben jederzeit das Recht:</p>
                                <ul className="list-disc list-inside space-y-1 ml-2">
                                    <li>Auskunft über Ihre bei uns gespeicherten personenbezogenen Daten zu erhalten</li>
                                    <li>Berichtigung unrichtiger Daten zu verlangen</li>
                                    <li>Löschung Ihrer bei uns gespeicherten Daten zu verlangen</li>
                                    <li>Einschränkung der Datenverarbeitung zu verlangen</li>
                                    <li>Widerspruch gegen die Verarbeitung Ihrer Daten einzulegen</li>
                                    <li>Datenübertragbarkeit zu verlangen</li>
                                </ul>
                            </div>
                            <div>
                                <h3 className="font-semibold text-gray-900 mb-2">5. Technische Umsetzung</h3>
                                <p>
                                    Diese Anwendung wurde entwickelt von <strong>Andona Cloud</strong>.{' '}
                                    <a href="https://andona-cloud.de" target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:underline">https://andona-cloud.de</a>
                                </p>
                            </div>
                        </div>
                    </DialogContent>
                </Dialog>

                {/* Demo Request Dialog */}
                <Dialog open={demoDialogOpen} onOpenChange={setDemoDialogOpen}>
                    <DialogContent className="sm:max-w-[500px] bg-[#0f1a2e] border-white/12">
                        <DialogHeader>
                            <DialogTitle className="text-white">{t('welcome.demoTitle')}</DialogTitle>
                            <DialogDescription className="text-white/80">{t('welcome.demoDesc')}</DialogDescription>
                        </DialogHeader>
                        <form
                            onSubmit={(e) => {
                                e.preventDefault();
                                demoForm.post(route('contact.demo'), {
                                    preserveScroll: true,
                                    onSuccess: () => {
                                        setDemoDialogOpen(false);
                                        demoForm.reset();
                                    },
                                });
                            }}
                            className="space-y-4"
                        >
                            <div className="space-y-2">
                                <Label htmlFor="demo-name" className="text-white">{t('welcome.demoName')} *</Label>
                                <Input
                                    id="demo-name"
                                    value={demoForm.data.name}
                                    onChange={(e) => demoForm.setData('name', e.target.value)}
                                    placeholder={t('welcome.demoNamePlaceholder')}
                                    required
                                    className="bg-white/10 border-white/20 text-white placeholder:text-white/50"
                                />
                                {demoForm.errors.name && <p className="text-sm text-red-400">{demoForm.errors.name}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="demo-email" className="text-white">{t('welcome.demoEmail')} *</Label>
                                <Input
                                    id="demo-email"
                                    type="email"
                                    value={demoForm.data.email}
                                    onChange={(e) => demoForm.setData('email', e.target.value)}
                                    placeholder={t('welcome.demoEmailPlaceholder')}
                                    required
                                    className="bg-white/10 border-white/20 text-white placeholder:text-white/50"
                                />
                                {demoForm.errors.email && <p className="text-sm text-red-400">{demoForm.errors.email}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="demo-company" className="text-white">{t('welcome.demoCompany')} *</Label>
                                <Input
                                    id="demo-company"
                                    value={demoForm.data.company}
                                    onChange={(e) => demoForm.setData('company', e.target.value)}
                                    placeholder={t('welcome.demoCompanyPlaceholder')}
                                    required
                                    className="bg-white/10 border-white/20 text-white placeholder:text-white/50"
                                />
                                {demoForm.errors.company && <p className="text-sm text-red-400">{demoForm.errors.company}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="demo-phone" className="text-white">{t('welcome.demoPhone')}</Label>
                                <Input
                                    id="demo-phone"
                                    type="tel"
                                    value={demoForm.data.phone}
                                    onChange={(e) => demoForm.setData('phone', e.target.value)}
                                    placeholder={t('welcome.demoPhonePlaceholder')}
                                    className="bg-white/10 border-white/20 text-white placeholder:text-white/50"
                                />
                                {demoForm.errors.phone && <p className="text-sm text-red-400">{demoForm.errors.phone}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="demo-message" className="text-white">{t('welcome.demoMessage')}</Label>
                                <Textarea
                                    id="demo-message"
                                    value={demoForm.data.message}
                                    onChange={(e) => demoForm.setData('message', e.target.value)}
                                    placeholder={t('welcome.demoMessagePlaceholder')}
                                    rows={4}
                                    className="bg-white/10 border-white/20 text-white placeholder:text-white/50"
                                />
                                {demoForm.errors.message && <p className="text-sm text-red-400">{demoForm.errors.message}</p>}
                            </div>

                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => { setDemoDialogOpen(false); demoForm.reset(); }}
                                    className="border-white/20 text-white hover:bg-white/10"
                                >
                                    {t('common.cancel')}
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={demoForm.processing}
                                    className="text-white hover:opacity-90"
                                    style={{ background: '#0B4194' }}
                                >
                                    {demoForm.processing ? t('welcome.demoSending') : t('welcome.demoSubmit')}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </>
    );
}
