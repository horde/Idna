<?php

declare(strict_types=1);

namespace Horde\Idna\Test;

use Horde_Idna;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Horde_Idna::class)]
class LegacyIdnaTest extends TestCase
{
    #[DataProvider('domainNamesProvider')]
    public function testEncode(string $decoded, string $encoded): void
    {
        $this->assertSame($encoded, Horde_Idna::encode($decoded));
    }

    #[DataProvider('domainNamesProvider')]
    public function testDecode(string $decoded, string $encoded): void
    {
        $this->assertSame($decoded, Horde_Idna::decode($encoded));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function domainNamesProvider(): array
    {
        return [
            'arabic (test)' => [
                'مثال.إختبار',
                'xn--mgbh0fb.xn--kgbechtv',
            ],
            'farsi (test)' => [
                'مثال.آزمایشی',
                'xn--mgbh0fb.xn--hgbk6aj7f53bba',
            ],
            'chinese simplified' => [
                '例子.测试',
                'xn--fsqu00a.xn--0zwm56d',
            ],
            'chinese traditional' => [
                '例子.測試',
                'xn--fsqu00a.xn--g6w251d',
            ],
            'russian' => [
                'пример.испытание',
                'xn--e1afmkfd.xn--80akhbyknj4f',
            ],
            'hindi' => [
                'उदाहरण.परीक्षा',
                'xn--p1b6ci4b4b3a.xn--11b5bs3a9aj6g',
            ],
            'greek' => [
                'παράδειγμα.δοκιμή',
                'xn--hxajbheg2az3al.xn--jxalpdlp',
            ],
            'korean' => [
                '실례.테스트',
                'xn--9n2bp8q.xn--9t4b11yi5a',
            ],
            'hebrew' => [
                'בײַשפּיל.טעסט',
                'xn--fdbk5d8ap9b8a8d.xn--deba0ad',
            ],
            'japanese katakana' => [
                '例え.テスト',
                'xn--r8jz45g.xn--zckzah',
            ],
            'tamil' => [
                'உதாரணம்.பரிட்சை',
                'xn--zkc6cc5bi7f6e.xn--hlcj6aya9esc7a',
            ],
            'german umlaut' => [
                'derhausüberwacher.de',
                'xn--derhausberwacher-pzb.de',
            ],
            'portuguese' => [
                'renangonçalves.com',
                'xn--renangonalves-pgb.com',
            ],
            'russian short' => [
                'рф.ru',
                'xn--p1ai.ru',
            ],
            'greek tld' => [
                'δοκιμή.gr',
                'xn--jxalpdlp.gr',
            ],
            'bengali' => [
                'ফাহাদ্১৯.বাংলা',
                'xn--65bj6btb5gwimc.xn--54b7fta0cc',
            ],
            'old italic' => [
                '𐌀𐌖𐌋𐌄𐌑𐌉·𐌌𐌄𐌕𐌄𐌋𐌉𐌑.gr',
                'xn--uba5533kmaba1adkfh6ch2cg.gr',
            ],
            'mixed ascii and idn' => [
                'guangdong.广东',
                'guangdong.xn--xhq521b',
            ],
            'polish' => [
                'gwóźdź.pl',
                'xn--gwd-hna98db.pl',
            ],
        ];
    }
}
