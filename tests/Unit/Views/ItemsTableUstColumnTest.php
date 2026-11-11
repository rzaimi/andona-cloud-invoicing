<?php

namespace Tests\Unit\Views;

use Tests\TestCase;

class ItemsTableUstColumnTest extends TestCase
{
    public function test_the_tax_column_is_shown_when_the_layout_does_not_say(): void
    {
        $html = $this->render([]);

        $this->assertStringContainsString('UST.', $html);
        $this->assertStringContainsString('19%', $html);
    }

    public function test_the_tax_column_can_be_turned_off(): void
    {
        $html = $this->render(['show_ust_column' => false]);

        $this->assertStringNotContainsString('UST.', $html);
        $this->assertStringNotContainsString('19%', $html);
        $this->assertStringContainsString('Beispielprodukt', $html);
        $this->assertStringContainsString('PREIS', $html);
    }

    public function test_non_standard_vat_keeps_the_column_hidden(): void
    {
        $html = $this->render(['show_ust_column' => true], 'small_business');

        $this->assertStringNotContainsString('UST.', $html);
        $this->assertStringNotContainsString('19%', $html);
    }

    private function render(array $content, string $vatRegime = 'standard'): string
    {
        $item = (object) [
            'description' => 'Beispielprodukt',
            'quantity' => 2,
            'unit' => 'Stk.',
            'tax_rate' => 0.19,
            'unit_price' => 50,
            'total' => 100,
            'discount_amount' => 0,
        ];

        return view('pdf.partials.items-table', [
            'doc' => (object) [
                'vat_regime' => $vatRegime,
                'tax_rate' => 0.19,
                'items' => collect([$item]),
            ],
            'docKind' => 'invoice',
            'layoutSettings' => [
                'content' => $content,
                'fonts' => ['body' => 'DejaVu Sans'],
                'colors' => ['text' => '#1f2937'],
            ],
            'bodyFontSize' => 9,
        ])->render();
    }
}
