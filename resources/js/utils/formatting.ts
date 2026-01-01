/**
 * Formatting utilities that use company settings
 */

interface CompanySettings {
    currency?: string
    date_format?: string
    decimal_separator?: string
    thousands_separator?: string
}

/**
 * Format currency amount using company settings
 */
export function formatCurrency(
    amount: number | null | undefined,
    settings?: CompanySettings,
    currency?: string
): string {
    const numAmount = Number(amount) || 0
    if (isNaN(numAmount)) {
        return formatCurrency(0, settings, currency)
    }

    const currencyCode = currency || settings?.currency || 'EUR'
    const decimalSeparator = settings?.decimal_separator || ','
    const thousandsSeparator = settings?.thousands_separator || '.'

    // Use Intl.NumberFormat for proper currency formatting, then replace separators
    const formatter = new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: currencyCode,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })

    let formatted = formatter.format(numAmount)

    // Replace separators if they differ from default
    if (decimalSeparator !== ',' || thousandsSeparator !== '.') {
        // Extract the number part (without currency symbol)
        const numberPart = formatted.replace(/[^\d,.-]/g, '')
        const parts = numberPart.split(',')
        
        if (parts.length === 2) {
            const integerPart = parts[0].replace(/\./g, thousandsSeparator)
            const decimalPart = parts[1]
            formatted = `${integerPart}${decimalSeparator}${decimalPart} ${currencyCode}`
        }
    }

    return formatted
}

/**
 * Format number (without currency symbol) using company settings
 */
export function formatNumber(
    amount: number | null | undefined,
    settings?: CompanySettings,
    decimals: number = 2
): string {
    const numAmount = Number(amount) || 0
    if (isNaN(numAmount)) {
        return formatNumber(0, settings, decimals)
    }

    const decimalSeparator = settings?.decimal_separator || ','
    const thousandsSeparator = settings?.thousands_separator || '.'

    // Use Intl.NumberFormat for proper number formatting, then replace separators
    const formatter = new Intl.NumberFormat('de-DE', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    })

    let formatted = formatter.format(numAmount)

    // Replace separators if they differ from default
    if (decimalSeparator !== ',' || thousandsSeparator !== '.') {
        const parts = formatted.split(',')
        if (parts.length === 2) {
            const integerPart = parts[0].replace(/\./g, thousandsSeparator)
            const decimalPart = parts[1]
            formatted = `${integerPart}${decimalSeparator}${decimalPart}`
        }
    }

    return formatted
}

/**
 * Format date using company settings
 */
export function formatDate(
    date: string | Date | null | undefined,
    settings?: CompanySettings
): string {
    if (!date) {
        return ''
    }

    const dateObj = typeof date === 'string' ? new Date(date) : date
    if (isNaN(dateObj.getTime())) {
        return ''
    }

    const dateFormat = settings?.date_format || 'd.m.Y'

    // Convert PHP date format to JavaScript
    const formatMap: Record<string, string> = {
        'd.m.Y': 'dd.MM.yyyy',
        'Y-m-d': 'yyyy-MM-dd',
        'd/m/Y': 'dd/MM/yyyy',
        'm/d/Y': 'MM/dd/yyyy',
    }

    const jsFormat = formatMap[dateFormat] || 'dd.MM.yyyy'

    // Use Intl.DateTimeFormat for formatting
    const formatter = new Intl.DateTimeFormat('de-DE', {
        day: '2-digit',
        month: jsFormat.includes('MM') ? '2-digit' : 'numeric',
        year: 'numeric',
    })

    return formatter.format(dateObj)
}

/**
 * Get currency code from settings
 */
export function getCurrency(settings?: CompanySettings): string {
    return settings?.currency || 'EUR'
}

/**
 * Get date format from settings
 */
export function getDateFormat(settings?: CompanySettings): string {
    return settings?.date_format || 'd.m.Y'
}

