/**
 * Resolve a number-format template (e.g. "RE-{YYYY}-{####}") to a concrete
 * string using today's date and a sample counter value.
 *
 * Tokens: {YYYY} {YY} {MM} {DD} {####} (any run of # pads the counter).
 *
 * Keep this in sync with `App\Services\NumberFormatService::resolve` on the
 * backend — client preview and server-generated number must match.
 */
export function previewNumberFormat(format: string | null | undefined, counter: number | string = 1): string {
    if (!format) return ""

    const sample = typeof counter === "string" ? parseInt(counter, 10) || 1 : counter
    const now    = new Date()
    const yyyy   = String(now.getFullYear())
    const yy     = yyyy.slice(-2)
    const mm     = String(now.getMonth() + 1).padStart(2, "0")
    const dd     = String(now.getDate()).padStart(2, "0")

    return format
        .replace(/\{YYYY\}/g, yyyy)
        .replace(/\{YY\}/g, yy)
        .replace(/\{MM\}/g, mm)
        .replace(/\{DD\}/g, dd)
        .replace(/\{(#+)\}/g, (_: string, hashes: string) =>
            String(sample).padStart(hashes.length, "0")
        )
}

/**
 * Token reference for UI — same tokens the backend understands.
 */
export const NUMBER_FORMAT_TOKENS: ReadonlyArray<readonly [string, string]> = [
    ["{YYYY}", "4-stelliges Jahr (2025)"],
    ["{YY}",   "2-stelliges Jahr (25)"],
    ["{MM}",   "Monat (01–12)"],
    ["{DD}",   "Tag (01–31)"],
    ["{####}", "Laufende Nr. (Anzahl # = Stellenanzahl)"],
] as const
