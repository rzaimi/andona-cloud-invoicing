<?php

namespace Tests\Unit\Views;

use Tests\TestCase;

class LetterheadCellsTest extends TestCase
{
    public function test_text_sits_opposite_a_right_logo(): void
    {
        $html = $this->render('top-right', "BNK BAU\nTel. 06051 123456");

        $this->assertLessThan(strpos($html, 'logo.png'), strpos($html, 'BNK BAU'));
        $this->assertStringContainsString('Tel. 06051 123456', $html);
        $this->assertStringContainsString('text-align:left', $html);
    }

    public function test_text_sits_opposite_a_left_logo(): void
    {
        $html = $this->render('top-left', 'Andona GmbH');

        $this->assertGreaterThan(strpos($html, 'logo.png'), strpos($html, 'Andona GmbH'));
        $this->assertStringContainsString('text-align:right', $html);
    }

    public function test_a_centered_logo_hides_the_text(): void
    {
        $html = $this->render('top-center', 'Andona GmbH');

        $this->assertStringNotContainsString('Andona GmbH', $html);
        $this->assertStringContainsString('logo.png', $html);
    }

    public function test_empty_text_leaves_the_side_blank(): void
    {
        $html = $this->render('top-right', '   ');

        $this->assertStringNotContainsString('<br>', $html);
        $this->assertStringContainsString('logo.png', $html);
    }

    public function test_only_four_lines_are_printed_and_markup_is_escaped(): void
    {
        $html = $this->render('top-right', "Eins\n<script>\nDrei\nVier\nFünf");

        $this->assertStringContainsString('Vier', $html);
        $this->assertStringNotContainsString('Fünf', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    private function render(string $position, string $text): string
    {
        return view('pdf.partials.letterhead-cells', [
            'logoCell' => '<img src="logo.png" alt="Logo">',
            'logoPos' => $position,
            'ls' => ['branding' => ['letterhead_text' => $text]],
            'letterheadColor' => '#64748b',
            'fs' => 10,
        ])->render();
    }
}
