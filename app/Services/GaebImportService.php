<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use InvalidArgumentException;

class GaebImportService
{
    /**
     * Read a GAEB DA XML file (phase 81 and the same bill-of-quantities shape
     * in later phases) and return only the billable positions.
     *
     * Preamble texts, remarks and category headings are not positions.
     *
     * @return array{
     *     project: string,
     *     label: string,
     *     boq: string,
     *     positions: list<array{
     *         position: string,
     *         group: string,
     *         name: string,
     *         description: string,
     *         quantity: float,
     *         unit: string,
     *         unit_price: float
     *     }>
     * }
     */
    public function parse(string $xml): array
    {
        $xml = trim($xml);
        if ($xml === '' || ! str_contains($xml, '<GAEB')) {
            throw new InvalidArgumentException('Die Datei ist kein GAEB-Dokument.');
        }

        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument;
        $loaded = $dom->loadXML($xml, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded || $dom->doctype !== null) {
            throw new InvalidArgumentException('Die Datei ist kein gültiges GAEB-XML.');
        }

        $root = $dom->documentElement;
        if (! $root instanceof DOMElement || $root->localName !== 'GAEB') {
            throw new InvalidArgumentException('Die Datei ist kein GAEB-Dokument.');
        }

        $positions = [];
        foreach ($this->children($root, 'Award') as $award) {
            foreach ($this->children($award, 'BoQ') as $boq) {
                foreach ($this->children($boq, 'BoQBody') as $body) {
                    $this->walk($body, [], '', $positions);
                }
            }
        }

        if ($positions === []) {
            throw new InvalidArgumentException('Die Datei enthält keine Positionen.');
        }

        if (count($positions) > 2000) {
            throw new InvalidArgumentException('Die Datei enthält zu viele Positionen (maximal 2.000).');
        }

        return [
            'project' => $this->text($this->first($root, 'PrjInfo'), 'NamePrj'),
            'label' => $this->text($this->first($root, 'PrjInfo'), 'LblPrj'),
            'boq' => $this->text($this->first($root, 'BoQInfo'), 'LblBoQ'),
            'positions' => $positions,
        ];
    }

    /**
     * @param  list<string>  $prefix
     * @param  list<array<string, mixed>>  $positions
     */
    private function walk(DOMElement $body, array $prefix, string $group, array &$positions): void
    {
        foreach ($body->childNodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            if ($node->localName === 'BoQCtgy') {
                $part = trim($node->getAttribute('RNoPart'));
                $nextPrefix = $part !== '' ? [...$prefix, $part] : $prefix;
                $label = $this->plain($this->first($node, 'LblTx'));
                $nextGroup = trim(($part !== '' ? $part.' ' : '').$label);
                foreach ($this->children($node, 'BoQBody') as $childBody) {
                    $this->walk($childBody, $nextPrefix, $nextGroup !== '' ? $nextGroup : $group, $positions);
                }
            } elseif ($node->localName === 'Itemlist') {
                foreach ($this->children($node, 'Item') as $item) {
                    $positions[] = $this->position($item, $prefix, $group);
                }
            }
        }
    }

    /**
     * @param  list<string>  $prefix
     * @return array{
     *     position: string,
     *     group: string,
     *     name: string,
     *     description: string,
     *     quantity: float,
     *     unit: string,
     *     unit_price: float
     * }
     */
    private function position(DOMElement $item, array $prefix, string $group): array
    {
        $part = trim($item->getAttribute('RNoPart'));
        $number = implode('.', $part !== '' ? [...$prefix, $part] : $prefix);
        $name = $this->outline($item);
        $detail = $this->detail($item);
        if ($name === '') {
            $name = $detail !== '' ? strtok($detail, "\n") : ($number !== '' ? 'Position '.$number : 'Position');
        }

        $quantity = $this->decimal($this->text($item, 'Qty'));
        if ($quantity <= 0) {
            $quantity = 1.0;
        }

        $unit = $this->normalizeUnit($this->text($item, 'QU'));
        if ($this->text($item, 'LumpSumItem') === 'Yes' && $unit === 'Stk.') {
            $unit = 'Pauschal';
        }

        return [
            'position' => $number,
            'group' => $group,
            'name' => $name,
            'description' => $this->description($number, $name, $detail),
            'quantity' => $quantity,
            'unit' => $unit,
            'unit_price' => $this->decimal($this->text($item, 'UP')),
        ];
    }

    private function outline(DOMElement $item): string
    {
        $parts = [];
        foreach ($this->descendants($item, 'TextOutlTxt') as $node) {
            $text = $this->plain($node);
            if ($text !== '') {
                $parts[] = $text;
            }
        }

        return trim(implode(' ', $parts));
    }

    /**
     * Long text (Langtext). GAEB files hard-wrap each column line in its own
     * paragraph, so those wraps are joined back into sentences. A blank
     * paragraph, or a specification line such as "Abmessungen:", starts a new block.
     */
    private function detail(DOMElement $item): string
    {
        $detail = $this->first($item, 'DetailTxt');
        if (! $detail instanceof DOMElement) {
            return '';
        }

        return $this->formatDetail($this->blockLines($detail));
    }

    /**
     * @return list<string>
     */
    private function blockLines(DOMElement $element): array
    {
        $lines = [];
        foreach ($element->childNodes as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }

            if (in_array($child->localName, ['p', 'li', 'ComplBody'], true)) {
                $text = $this->inlineText($child);
                if ($child->localName !== 'p') {
                    $lines[] = '';
                }
                foreach ($text === '' ? [''] : (preg_split('/\n/u', $text) ?: ['']) as $line) {
                    $lines[] = trim($line);
                }
                if ($child->localName !== 'p') {
                    $lines[] = '';
                }
            } else {
                $lines = array_merge($lines, $this->blockLines($child));
            }
        }

        return $lines;
    }

    private function inlineText(DOMElement $element): string
    {
        $parts = [];
        foreach ($element->childNodes as $child) {
            if ($child instanceof \DOMText) {
                $parts[] = $child->textContent;
            } elseif ($child instanceof DOMElement) {
                $parts[] = $child->localName === 'br' ? "\n" : $this->inlineText($child);
            }
        }

        $text = implode('', $parts);
        $text = preg_replace('/[^\S\n]+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    private function description(string $number, string $name, string $detail): string
    {
        $title = trim($number.' '.$name);
        if ($detail === '' || $detail === $name) {
            return $title;
        }

        $paragraphs = array_values(array_filter(
            preg_split("/\n{2,}/u", $detail) ?: [],
            fn (string $paragraph) => trim($paragraph) !== '' && trim($paragraph) !== $name
        ));

        if ($paragraphs === []) {
            return $title;
        }

        return $title."\n\n".implode("\n\n", $paragraphs);
    }

    /**
     * @param  list<string>  $lines
     */
    private function formatDetail(array $lines): string
    {
        $paragraphs = [];
        $buffer = [];

        $flush = function () use (&$buffer, &$paragraphs): void {
            if ($buffer === []) {
                return;
            }
            $paragraphs[] = $this->joinWrapped($buffer);
            $buffer = [];
        };

        foreach ($lines as $line) {
            if ($line === '') {
                $flush();

                continue;
            }
            if ($this->isSpecLine($line)) {
                $flush();
                $paragraphs[] = $line;

                continue;
            }
            $buffer[] = $line;
        }
        $flush();

        return implode("\n\n", $paragraphs);
    }

    /**
     * @param  list<string>  $lines
     */
    private function joinWrapped(array $lines): string
    {
        $text = '';
        foreach ($lines as $line) {
            if ($text === '') {
                $text = $line;

                continue;
            }
            $text .= str_ends_with($text, '-') ? $line : ' '.$line;
        }

        return preg_replace('/[^\S\n]+/u', ' ', $text) ?? $text;
    }

    private function isSpecLine(string $line): bool
    {
        if (preg_match('/^.{1,42}:\s*\S/u', $line) || (preg_match('/:\s*$/u', $line) && mb_strlen($line) <= 40)) {
            return true;
        }

        return (bool) preg_match('/^(Ort|Abmessungen|Untergrund|Flächen|Fabrikat|angeb\.|Format|Farbton|System|Einbauort|Material|Achsmaß|Achsraster|Achstraster|Höhe|Stärke|Rutschhemmung|Fugenstärke|Fugenbreite)\b/iu', $line);
    }

    private function normalizeUnit(string $unit): string
    {
        $key = mb_strtolower(trim($unit));
        $key = str_replace(['²', '³', ' ', '.'], ['2', '3', '', ''], $key);

        return match ($key) {
            'm2', 'qm', 'quadratmeter' => 'm²',
            'm3', 'cbm', 'kubikmeter' => 'm³',
            'm', 'lfm', 'lfdm', 'meter' => 'm',
            'st', 'stk', 'stck', 'stück', 'stueck' => 'Stk.',
            'psch', 'pausch', 'pauschal', 'pauschale' => 'Pauschal',
            'h', 'std', 'stunde', 'stunden' => 'Std.',
            'd', 'tag', 'tage' => 'Tag',
            'wo', 'woche', 'wochen' => 'Woche',
            'mt', 'mon', 'monat', 'monate' => 'Monat',
            'kg' => 'kg',
            't', 'to', 'tonne' => 't',
            'l', 'ltr', 'liter' => 'l',
            '' => 'Stk.',
            default => trim($unit),
        };
    }

    private function decimal(string $value): float
    {
        $value = trim(str_replace(',', '.', $value));
        if ($value === '' || ! is_numeric($value)) {
            return 0.0;
        }

        return (float) $value;
    }

    private function text(?DOMElement $element, string $name): string
    {
        if (! $element instanceof DOMElement) {
            return '';
        }

        return $this->plain($this->first($element, $name));
    }

    private function plain(?DOMElement $element): string
    {
        if (! $element instanceof DOMElement) {
            return '';
        }

        $text = preg_replace('/\s+/u', ' ', $element->textContent) ?? '';

        return trim($text);
    }

    private function first(DOMElement $element, string $name): ?DOMElement
    {
        return $this->descendants($element, $name)[0] ?? null;
    }

    /**
     * Tag-name lookup that still finds elements in GAEB's default namespace.
     *
     * @return list<DOMElement>
     */
    private function descendants(DOMElement $element, string $name): array
    {
        $found = [];
        foreach ($element->getElementsByTagNameNS('*', $name) as $node) {
            if ($node instanceof DOMElement) {
                $found[] = $node;
            }
        }

        return $found;
    }

    /**
     * @return list<DOMElement>
     */
    private function children(DOMElement $element, string $name): array
    {
        $found = [];
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === $name) {
                $found[] = $child;
            }
        }

        return $found;
    }
}
