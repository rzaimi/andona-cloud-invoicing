/**
 * Translate pagination labels from English to German
 */
export function translatePaginationLabel(label: string): string {
    if (!label) return label;
    
    // Handle HTML entities - translate while preserving them
    let translated = label
        .replace(/&laquo;\s*previous/gi, '&laquo; Zurück')
        .replace(/previous\s*&laquo;/gi, 'Zurück &laquo;')
        .replace(/next\s*&raquo;/gi, 'Weiter &raquo;')
        .replace(/&raquo;\s*next/gi, '&raquo; Weiter')
        .replace(/previous/gi, 'Zurück')
        .replace(/next/gi, 'Weiter');
    
    // Numbers and other labels stay as-is
    return translated;
}

