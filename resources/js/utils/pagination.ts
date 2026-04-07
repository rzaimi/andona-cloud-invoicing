/**
 * Normalize pagination link labels for the UI.
 * Laravel uses __('pagination.previous'|'pagination.next'); if a locale file is
 * missing, the raw key is returned. Do not substring-replace "previous"/"next"
 * inside those keys (e.g. pagination.previous → pagination.Zurück).
 */
export function translatePaginationLabel(label: string): string {
    if (!label) return label;

    const trimmed = label.trim();
    const keyMap: Record<string, string> = {
        'pagination.previous': '&laquo; Zurück',
        'pagination.next': 'Weiter &raquo;',
    };
    const mapped = keyMap[trimmed] ?? keyMap[trimmed.toLowerCase()];
    if (mapped) return mapped;

    // English strings from Laravel's default lang (when locale is en)
    return (
        label
            .replace(/&laquo;\s*previous/gi, '&laquo; Zurück')
            .replace(/previous\s*&laquo;/gi, 'Zurück &laquo;')
            .replace(/next\s*&raquo;/gi, 'Weiter &raquo;')
            .replace(/&raquo;\s*next/gi, '&raquo; Weiter')
            .replace(/\bprevious\b/gi, 'Zurück')
            .replace(/\bnext\b/gi, 'Weiter')
    );
}

