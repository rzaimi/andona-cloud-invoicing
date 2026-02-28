"use client"

import { useTranslation } from 'react-i18next'
import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { SUPPORTED_LANGUAGES, type SupportedLanguage } from '@/i18n'

const LANGUAGE_CONFIG: Record<SupportedLanguage, { label: string; flag: string }> = {
    de: { label: 'Deutsch', flag: '🇩🇪' },
    en: { label: 'English', flag: '🇬🇧' },
    sq: { label: 'Shqip',   flag: '🇦🇱' },
}

export default function LanguageSwitcher() {
    const { i18n } = useTranslation()
    const currentLang = (i18n.language?.slice(0, 2) ?? 'de') as SupportedLanguage
    const current = LANGUAGE_CONFIG[currentLang] ?? LANGUAGE_CONFIG.de

    const handleChange = (lang: SupportedLanguage) => {
        i18n.changeLanguage(lang)
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="sm"
                    className="h-8 px-2 text-sm font-medium gap-1"
                    title={current.label}
                >
                    <span className="text-base leading-none">{current.flag}</span>
                    <span className="hidden sm:inline text-xs">{currentLang.toUpperCase()}</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-36">
                {SUPPORTED_LANGUAGES.map((lang) => {
                    const cfg = LANGUAGE_CONFIG[lang]
                    return (
                        <DropdownMenuItem
                            key={lang}
                            onClick={() => handleChange(lang)}
                            className={currentLang === lang ? 'bg-accent font-semibold' : ''}
                        >
                            <span className="mr-2 text-base">{cfg.flag}</span>
                            {cfg.label}
                        </DropdownMenuItem>
                    )
                })}
            </DropdownMenuContent>
        </DropdownMenu>
    )
}
