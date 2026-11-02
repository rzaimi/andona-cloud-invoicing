<?php

namespace Tests\Unit\Services;

use App\Services\GaebImportService;
use InvalidArgumentException;
use Tests\TestCase;

class GaebImportServiceTest extends TestCase
{
    public function test_it_imports_only_billable_positions(): void
    {
        $parsed = (new GaebImportService)->parse(file_get_contents(base_path('tests/fixtures/gaeb-sample.x81')));

        $this->assertSame('26-02', $parsed['project']);
        $this->assertSame('Musterstraße 1', $parsed['label']);
        $this->assertSame('Fliesenarbeiten', $parsed['boq']);
        $this->assertCount(4, $parsed['positions']);

        $this->assertSame([
            'position' => '01.10',
            'group' => '01 Vorarbeiten',
            'name' => 'Abbruch von Bestandsfliesen',
            'description' => "01.10 Abbruch von Bestandsfliesen\n\nAbbruch und Entsorgung des Bestandsfliesenbelages",
            'quantity' => 29.5,
            'unit' => 'm²',
            'unit_price' => 0.0,
        ], $parsed['positions'][0]);

        $this->assertSame('01.20', $parsed['positions'][1]['position']);
        $this->assertSame('Stk.', $parsed['positions'][1]['unit']);
        $this->assertSame(4.0, $parsed['positions'][1]['quantity']);

        $this->assertSame('02.10', $parsed['positions'][2]['position']);
        $this->assertSame('m²', $parsed['positions'][2]['unit']);
        $this->assertSame(42.5, $parsed['positions'][2]['unit_price']);

        $this->assertSame('02.20', $parsed['positions'][3]['position']);
        $this->assertSame('Pauschal', $parsed['positions'][3]['unit']);
        $this->assertSame('Silikonfugen Sanitärobjekte', $parsed['positions'][3]['name']);

        $descriptions = array_column($parsed['positions'], 'description');
        $this->assertNotContains('Dieser Vorbemerkungstext ist keine Position.', $descriptions);
        $this->assertNotContains('Hinweis Untergrund', array_column($parsed['positions'], 'name'));
    }

    public function test_it_keeps_the_long_text_under_the_short_title(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<GAEB xmlns="http://www.gaeb.de/GAEB_DA_XML/DA81/3.2">
  <Award><BoQ><BoQBody>
    <BoQCtgy RNoPart="02"><LblTx><span>Fliesenarbeiten</span></LblTx><BoQBody><Itemlist>
      <Item ID="id95" RNoPart="27">
        <Qty>1.000</Qty><QU>St</QU>
        <Description><CompleteText>
          <DetailTxt><Text>
            <p><span>Liefern und Einbauen eines Bodeneinsetzrahmens (Dünnbettaufsatz) aus Edelstahl V2A</span></p>
            <p><span>einschl. der Eindichtarbeiten</span></p>
            <p><span> </span></p>
            <p><span>Abmessungen: ca. 20 x 20 cm</span></p>
          </Text></DetailTxt>
          <OutlineText><OutlTxt><TextOutlTxt><p><span>Einsetzrahmen Bodeneinlauf V2A</span></p></TextOutlTxt></OutlTxt></OutlineText>
        </CompleteText></Description>
      </Item>
    </Itemlist></BoQBody></BoQCtgy>
  </BoQBody></BoQ></Award>
</GAEB>
XML;

        $position = (new GaebImportService)->parse($xml)['positions'][0];

        $this->assertSame('Einsetzrahmen Bodeneinlauf V2A', $position['name']);
        $this->assertSame(
            "02.27 Einsetzrahmen Bodeneinlauf V2A\n\nLiefern und Einbauen eines Bodeneinsetzrahmens (Dünnbettaufsatz) aus Edelstahl V2A einschl. der Eindichtarbeiten\n\nAbmessungen: ca. 20 x 20 cm",
            $position['description']
        );
    }

    public function test_it_rejects_a_file_without_positions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Die Datei enthält keine Positionen.');

        (new GaebImportService)->parse('<?xml version="1.0"?><GAEB xmlns="http://www.gaeb.de/GAEB_DA_XML/DA81/3.2"><Award><BoQ><BoQBody></BoQBody></BoQ></Award></GAEB>');
    }
}
