import i18n from 'i18next'
import { initReactI18next } from 'react-i18next'
import LanguageDetector from 'i18next-browser-languagedetector'

import de from './locales/de'
import en from './locales/en'
import sq from './locales/sq'

// Public pages (landing, login) always show German; only the backend app is translated
export const APP_LANG_KEY = 'andobill_app_lang'

const pathnameDetector = {
    name: 'pathname',
    lookup() {
        if (typeof window === 'undefined') return undefined
        const path = window.location.pathname
        if (path === '/' || path === '/login' || path.startsWith('/verify-email') || path === '/confirm-password') {
            return 'de'
        }
        return undefined
    },
}

const languageDetector = new LanguageDetector()
languageDetector.addDetector(pathnameDetector)

i18n
    .use(languageDetector)
    .use(initReactI18next)
    .init({
        resources: {
            de: { translation: de },
            en: { translation: en },
            sq: { translation: sq },
        },
        fallbackLng: 'de',
        defaultNS: 'translation',
        detection: {
            order: ['pathname', 'localStorage', 'navigator'],
            lookupLocalStorage: APP_LANG_KEY,
            caches: [], // Only persist when user changes language in app (see LanguageSwitcher)
        },
        interpolation: {
            escapeValue: false,
        },
    })

export default i18n
export const SUPPORTED_LANGUAGES = ['de', 'en', 'sq'] as const
export type SupportedLanguage = (typeof SUPPORTED_LANGUAGES)[number]
