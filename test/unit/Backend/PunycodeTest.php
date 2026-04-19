<?php

declare(strict_types=1);

namespace Horde\Idna\Test\Backend;

use Horde\Idna\Backend\BackendInterface;
use Horde\Idna\Backend\Punycode;
use Horde\Util\HordeString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Punycode::class)]
class PunycodeTest extends TestCase
{
    private Punycode $backend;

    protected function setUp(): void
    {
        $this->backend = new Punycode();
    }

    public function testImplementsBackendInterface(): void
    {
        $this->assertInstanceOf(BackendInterface::class, $this->backend);
    }

    #[DataProvider('domainNamesProvider')]
    public function testEncode(string $unicode, string $ascii): void
    {
        $this->assertSame($ascii, $this->backend->encode($unicode));
    }

    #[DataProvider('domainNamesProvider')]
    public function testDecode(string $unicode, string $ascii): void
    {
        $this->assertSame($unicode, $this->backend->decode($ascii));
    }

    #[DataProvider('domainNamesProvider')]
    public function testRoundTrip(string $unicode, string $ascii): void
    {
        $encoded = $this->backend->encode($unicode);
        $this->assertSame($unicode, $this->backend->decode($encoded));
    }

    public function testEncodeAsciiPassthrough(): void
    {
        $this->assertSame('example.com', $this->backend->encode('example.com'));
    }

    public function testDecodeAsciiPassthrough(): void
    {
        $this->assertSame('example.com', $this->backend->decode('example.com'));
    }

    public function testEncodeSubdomains(): void
    {
        $this->assertSame(
            'sub.xn--mnchen-3ya.de',
            $this->backend->encode('sub.münchen.de'),
        );
    }

    public function testDecodeSubdomains(): void
    {
        $this->assertSame(
            'sub.münchen.de',
            $this->backend->decode('sub.xn--mnchen-3ya.de'),
        );
    }

    public function testEncodeSingleLabel(): void
    {
        $this->assertSame('xn--mnchen-3ya', $this->backend->encode('münchen'));
    }

    public function testDecodeSingleLabel(): void
    {
        $this->assertSame('münchen', $this->backend->decode('xn--mnchen-3ya'));
    }

    public function testDecodeIsCaseInsensitive(): void
    {
        $result = $this->backend->decode('XN--MNCHEN-3YA.de');
        $this->assertSame('münchen.de', $result);
    }

    public static function domainNamesProvider(): array
    {
        return [
            'german umlaut' => ['münchen.de', 'xn--mnchen-3ya.de'],
            'japanese' => ['日本.jp', 'xn--wgv71a.jp'],
            'chinese simplified' => ['例子.测试', 'xn--fsqu00a.xn--0zwm56d'],
            'chinese traditional' => ['例子.測試', 'xn--fsqu00a.xn--g6w251d'],
            'russian' => ['пример.испытание', 'xn--e1afmkfd.xn--80akhbyknj4f'],
            'arabic' => ['مثال.إختبار', 'xn--mgbh0fb.xn--kgbechtv'],
            'korean' => ['실례.테스트', 'xn--9n2bp8q.xn--9t4b11yi5a'],
            'greek' => ['παράδειγμα.δοκιμή', 'xn--hxajbheg2az3al.xn--jxalpdlp'],
            'mixed ascii and idn' => ['guangdong.广东', 'guangdong.xn--xhq521b'],
            'polish' => ['gwóźdź.pl', 'xn--gwd-hna98db.pl'],
            'hindi' => ['उदाहरण.परीक्षा', 'xn--p1b6ci4b4b3a.xn--11b5bs3a9aj6g'],
            'german hausueberwacher' => ['derhausüberwacher.de', 'xn--derhausberwacher-pzb.de'],
            'portuguese' => ['renangonçalves.com', 'xn--renangonalves-pgb.com'],
            'russian short' => ['рф.ru', 'xn--p1ai.ru'],
            'bengali' => ['ফাহাদ্১৯.বাংলা', 'xn--65bj6btb5gwimc.xn--54b7fta0cc'],
            'old italic' => ['𐌀𐌖𐌋𐌄𐌑𐌉·𐌌𐌄𐌕𐌄𐌋𐌉𐌑.gr', 'xn--uba5533kmaba1adkfh6ch2cg.gr'],
            'hebrew' => ['בײַשפּיל.טעסט', 'xn--fdbk5d8ap9b8a8d.xn--deba0ad'],
            'japanese katakana' => ['例え.テスト', 'xn--r8jz45g.xn--zckzah'],
            'tamil' => ['உதாரணம்.பரிட்சை', 'xn--zkc6cc5bi7f6e.xn--hlcj6aya9esc7a'],
            'farsi' => ['مثال.آزمایشی', 'xn--mgbh0fb.xn--hgbk6aj7f53bba'],
        ];
    }
}
