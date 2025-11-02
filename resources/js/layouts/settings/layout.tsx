import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import React, { type PropsWithChildren } from 'react';
import { route } from 'ziggy-js';

type NavItem = {
    title: string;
    href: string;
    icon: null | React.ReactNode;
};

export default function SettingsLayout({ children }: PropsWithChildren) {
    // When server-side rendering, we only render the layout on the client...
    if (typeof window === 'undefined') {
        return null;
    }

    const currentPath = window.location.pathname;

    const sidebarNavItems: NavItem[] = [
        {
            title: 'Profil',
            href: route('profile.edit'),
            icon: null,
        },
        {
            title: 'Passwort',
            href: route('password.edit'),
            icon: null,
        },
        {
            title: 'Darstellung',
            href: route('appearance'),
            icon: null,
        },
    ];

    return (
        <div className="px-4 py-6">
            <Heading title="Einstellungen" description="Verwalten Sie Ihre Profil- und Kontoeinstellungen" />

            <div className="flex flex-col space-y-8 lg:flex-row lg:space-y-0 lg:space-x-12">
                <aside className="w-full max-w-xl lg:w-48">
                    <nav className="flex flex-col space-y-1 space-x-0">
                        {sidebarNavItems.map((item, index) => (
                            <Link key={`${item.href}-${index}`} href={item.href} prefetch>
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    className={cn('w-full justify-start', {
                                        'bg-muted': currentPath === item.href,
                                    })}
                                >
                                    {item.title}
                                </Button>
                            </Link>
                        ))}
                    </nav>
                </aside>

                <Separator className="my-6 md:hidden" />

                <div className="flex-1 md:max-w-2xl">
                    <section className="max-w-xl space-y-12">{children}</section>
                </div>
            </div>
        </div>
    );
}
