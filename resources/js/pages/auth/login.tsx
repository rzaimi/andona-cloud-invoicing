import { Head, Link, useForm } from '@inertiajs/react';
import { FileText, LoaderCircle, ArrowRight } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type LoginForm = {
    email: string;
    password: string;
    remember: boolean;
};

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
}

export default function Login({ status, canResetPassword }: LoginProps) {
    const { data, setData, post, processing, errors, reset } = useForm<Required<LoginForm>>({
        email: '',
        password: '',
        remember: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <>
            <Head title="Anmelden" />
            
            <div className="flex min-h-screen">
                {/* Left Side - 70% Image/Branding */}
                <div className="hidden lg:flex lg:w-[70%] relative overflow-hidden bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-700">
                    {/* Decorative Elements */}
                    <div className="absolute inset-0">
                        <div className="absolute top-20 left-20 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
                        <div className="absolute bottom-20 right-20 h-96 w-96 rounded-full bg-white/10 blur-3xl"></div>
                        <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 h-[600px] w-[600px] rounded-full bg-white/5 blur-3xl"></div>
                    </div>

                    {/* Content */}
                    <div className="relative z-10 flex flex-col justify-between p-12 text-white w-full">
                        {/* Logo */}
                        <Link href="/" className="flex items-center space-x-3">
                            <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm shadow-lg">
                                <FileText className="h-7 w-7 text-white" />
                            </div>
                            <span className="text-2xl font-bold">AndoBill</span>
                        </Link>

                        {/* Center Content */}
                        <div className="max-w-2xl">
                            <h1 className="mb-6 text-5xl font-bold leading-tight">
                                Professionelle Rechnungsverwaltung
                            </h1>
                            <p className="text-xl text-white/90 leading-relaxed">
                                Erstellen, versenden und verwalten Sie Ihre Rechnungen effizient und sicher. 
                                Mit AndoBill haben Sie alles unter Kontrolle.
                            </p>

                            {/* Features */}
                            <div className="mt-12 grid gap-6 sm:grid-cols-2">
                                <div className="rounded-xl border border-white/20 bg-white/10 p-6 backdrop-blur-sm">
                                    <div className="mb-2 flex h-10 w-10 items-center justify-center rounded-lg bg-white/20">
                                        <FileText className="h-5 w-5" />
                                    </div>
                                    <h3 className="mb-2 font-semibold">Rechnungen & Angebote</h3>
                                    <p className="text-sm text-white/80">Professionelle Dokumente in Sekunden erstellen</p>
                                </div>
                                <div className="rounded-xl border border-white/20 bg-white/10 p-6 backdrop-blur-sm">
                                    <div className="mb-2 flex h-10 w-10 items-center justify-center rounded-lg bg-white/20">
                                        <ArrowRight className="h-5 w-5" />
                                    </div>
                                    <h3 className="mb-2 font-semibold">Automatisches Mahnwesen</h3>
                                    <p className="text-sm text-white/80">Rechtssicherer Mahnprozess nach deutschem Standard</p>
                                </div>
                            </div>
                        </div>

                        {/* Footer */}
                        <div className="text-sm text-white/70">
                            © {new Date().getFullYear()} AndoBill. Alle Rechte vorbehalten.
                        </div>
                    </div>
                </div>

                {/* Right Side - 30% Login Form */}
                <div className="flex w-full lg:w-[30%] items-center justify-center p-8 bg-white dark:bg-gray-950">
                    <div className="w-full max-w-md space-y-8">
                        {/* Mobile Logo */}
                        <div className="lg:hidden flex items-center justify-center space-x-3 mb-8">
                            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 shadow-lg">
                                <FileText className="h-6 w-6 text-white" />
                            </div>
                            <span className="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-2xl font-bold text-transparent">
                                AndoBill
                            </span>
                        </div>

                        {/* Header */}
                        <div className="text-center">
                            <h2 className="text-4xl font-bold text-gray-900 dark:text-white">
                                Willkommen zurück
                            </h2>
                            <p className="mt-3 text-base text-gray-600 dark:text-gray-400">
                                Melden Sie sich an, um fortzufahren
                            </p>
                        </div>

                        {/* Status Message */}
                        {status && (
                            <div className="rounded-lg bg-green-50 p-4 text-center text-base font-medium text-green-600 dark:bg-green-900/20 dark:text-green-400">
                                {status}
                            </div>
                        )}

                        {/* Login Form */}
                        <form onSubmit={submit} className="space-y-6">
                            <div className="space-y-5">
                                {/* Email */}
                                <div className="space-y-2.5">
                                    <Label htmlFor="email" className="text-base font-medium text-gray-700 dark:text-gray-300">
                                        E-Mail-Adresse
                                    </Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        required
                                        autoFocus
                                        tabIndex={1}
                                        autoComplete="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        placeholder="name@beispiel.de"
                                        className="h-12 text-base"
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                {/* Password */}
                                <div className="space-y-2.5">
                                    <Label htmlFor="password" className="text-base font-medium text-gray-700 dark:text-gray-300">
                                        Passwort
                                    </Label>
                                    <Input
                                        id="password"
                                        type="password"
                                        required
                                        tabIndex={2}
                                        autoComplete="current-password"
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                        placeholder="••••••••"
                                        className="h-12 text-base"
                                    />
                                    <InputError message={errors.password} />
                                </div>

                                {/* Remember Me */}
                                <div className="flex items-center space-x-2.5">
                                    <Checkbox
                                        id="remember"
                                        name="remember"
                                        checked={data.remember}
                                        onClick={() => setData('remember', !data.remember)}
                                        tabIndex={3}
                                    />
                                    <Label 
                                        htmlFor="remember" 
                                        className="text-base font-normal text-gray-600 dark:text-gray-400 cursor-pointer"
                                    >
                                        Angemeldet bleiben
                                    </Label>
                                </div>
                            </div>

                            {/* Submit Button */}
                            <Button 
                                type="submit" 
                                className="w-full h-12 text-base font-medium bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 shadow-lg" 
                                tabIndex={4} 
                                disabled={processing}
                            >
                                {processing ? (
                                    <>
                                        <LoaderCircle className="mr-2 h-5 w-5 animate-spin" />
                                        Anmelden...
                                    </>
                                ) : (
                                    'Anmelden'
                                )}
                            </Button>
                        </form>

                        {/* Footer */}
                        <div className="text-center text-base text-gray-600 dark:text-gray-400">
                            <p>
                                Sicherer Login mit modernster Verschlüsselung
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
