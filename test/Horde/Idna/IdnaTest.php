<?php

namespace Horde\Idna;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Horde_Idna;

/**
 * @coversNothing
 */
class IdnaTest extends TestCase
{
    #[DataProvider('domainNamesProvider')]
    public function testEncode($decoded, $encoded)
    {
        $idna = new Horde_Idna();

        $this->assertEquals(
            $encoded,
            $idna->encode($decoded)
        );
    }

    #[DataProvider('domainNamesProvider')]
    public function testDecode($decoded, $encoded)
    {
        $idna = new Horde_Idna();

        $this->assertEquals(
            $decoded,
            $idna->decode($encoded)
        );
    }

    public static function domainNamesProvider()
    {
        return [
            // http://en.wikipedia.org/wiki/.test_(international_domain_name)#Test_TLDs
            [
                'مثال.إختبار',
                'xn--mgbh0fb.xn--kgbechtv',
            ],
            [
                'مثال.آزمایشی',
                'xn--mgbh0fb.xn--hgbk6aj7f53bba',
            ],
            [
                '例子.测试',
                'xn--fsqu00a.xn--0zwm56d',
            ],
            [
                '例子.測試',
                'xn--fsqu00a.xn--g6w251d',
            ],
            [
                'пример.испытание',
                'xn--e1afmkfd.xn--80akhbyknj4f',
            ],
            [
                'उदाहरण.परीक्षा',
                'xn--p1b6ci4b4b3a.xn--11b5bs3a9aj6g',
            ],
            [
                'παράδειγμα.δοκιμή',
                'xn--hxajbheg2az3al.xn--jxalpdlp',
            ],
            [
                '실례.테스트',
                'xn--9n2bp8q.xn--9t4b11yi5a',
            ],
            [
                'בײַשפּיל.טעסט',
                'xn--fdbk5d8ap9b8a8d.xn--deba0ad',
            ],
            [
                '例え.テスト',
                'xn--r8jz45g.xn--zckzah',
            ],
            [
                'உதாரணம்.பரிட்சை',
                'xn--zkc6cc5bi7f6e.xn--hlcj6aya9esc7a',
            ],
            [
                'derhausüberwacher.de',
                'xn--derhausberwacher-pzb.de',
            ],
            [
                'renangonçalves.com',
                'xn--renangonalves-pgb.com',
            ],
            [
                'рф.ru',
                'xn--p1ai.ru',
            ],
            [
                'δοκιμή.gr',
                'xn--jxalpdlp.gr',
            ],
            [
                'ফাহাদ্১৯.বাংলা',
                'xn--65bj6btb5gwimc.xn--54b7fta0cc',
            ],
            [
                '𐌀𐌖𐌋𐌄𐌑𐌉·𐌌𐌄𐌕𐌄𐌋𐌉𐌑.gr',
                'xn--uba5533kmaba1adkfh6ch2cg.gr',
            ],
            [
                'guangdong.广东',
                'guangdong.xn--xhq521b',
            ],
            [
                'gwóźdź.pl',
                'xn--gwd-hna98db.pl',
            ],
        ];
    }
}
