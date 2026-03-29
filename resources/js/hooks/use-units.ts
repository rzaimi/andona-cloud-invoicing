import { usePage } from "@inertiajs/react"

/**
 * Returns the unit list for the current company.
 * Reads from company settings (shared via Inertia), so each tenant
 * can have their own customised list. Falls back to the canonical
 * default when no setting is available.
 */
const FALLBACK_UNITS = [
    "Stk.", "Std.", "Tag", "Woche", "Monat", "Jahr",
    "m", "m²", "m³", "kg", "g", "t", "l", "ml",
    "Paket", "Set", "Palette", "Rolle", "VE",
    "Pauschal", "RP",
]

export function useUnits(): string[] {
    const { props } = usePage<any>()
    const units = props.auth?.user?.company?.settings?.default_units
    return Array.isArray(units) && units.length > 0 ? units : FALLBACK_UNITS
}
