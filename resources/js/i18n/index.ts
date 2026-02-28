import i18n from 'i18next'
import { initReactI18next } from 'react-i18next'
import LanguageDetector from 'i18next-browser-languagedetector'

import de from './locales/de'
import en from './locales/en'
import sq from './locales/sq'

i18n
    .use(LanguageDetector)
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
            // Look for language in localStorage first, then browser preference
            order: ['localStorage', 'navigator'],
            lookupLocalStorage: 'andobill_lang',
            caches: ['localStorage'],
        },
        interpolation: {
            escapeValue: false,
        },
    })

export default i18n
export const SUPPORTED_LANGUAGES = ['de', 'en', 'sq'] as const
export type SupportedLanguage = (typeof SUPPORTED_LANGUAGES)[number]
