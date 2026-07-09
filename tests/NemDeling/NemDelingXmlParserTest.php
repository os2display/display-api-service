<?php

declare(strict_types=1);

namespace App\Tests\NemDeling;

use App\NemDeling\Xml\NemDelingXmlParser;
use PHPUnit\Framework\TestCase;

final class NemDelingXmlParserTest extends TestCase
{
    private NemDelingXmlParser $parser;

    protected function setUp(): void
    {
        $this->parser = new NemDelingXmlParser();
    }

    public function testParsesEventPayload(): void
    {
        $xml = <<<'XML'
<result is_array="true">
    <item>
        <startdate is_array="true">
            <item>21.10.2022</item>
        </startdate>
        <enddate is_array="true">
            <item>21.10.2022</item>
        </enddate>
        <time is_array="true">
            <item>19:00 til 20:30</item>
        </time>
        <Nid>140</Nid>
        <billede is_array="true">
            <item>
                <img src="https://example.com/image.jpg" alt="Example" height="1080" width="1920" title="" />
            </item>
        </billede>
        <title>Example event</title>
        <field_teaser>Teaser text</field_teaser>
        <screen is_array="true">
            <item>copenhagen_test</item>
        </screen>
        <host>Example host</host>
        <color>kk_blaa</color>
    </item>
</result>
XML;

        $parsed = $this->parser->parse($xml);

        self::assertArrayHasKey('result', $parsed);
        self::assertCount(1, $parsed['result']['item']);
        self::assertSame('Example event', $parsed['result']['item'][0]['title']);
        self::assertSame('140', $parsed['result']['item'][0]['Nid']);
        self::assertSame(
            'https://example.com/image.jpg',
            $parsed['result']['item'][0]['billede']['item'][0]['img'][0]['$']['src']
        );
    }
}
