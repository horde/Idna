<?php

declare(strict_types=1);

namespace Horde\Idna\Test\Backend;

use Horde\Idna\Backend\BackendInterface;
use Horde\Idna\Backend\Intl;
use Horde\Idna\Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[CoversClass(Intl::class)]
#[RequiresPhpExtension('intl')]
class IntlTest extends TestCase
{
    private Intl $backend;

    protected function setUp(): void
    {
        $this->backend = new Intl();
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

    public function testEncodeEmptyLabelThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('empty');
        $this->backend->encode('example..com');
    }

    public function testEncodeTooLongLabelThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('too long');
        $this->backend->encode(str_repeat('a', 64) . '.com');
    }

    public function testEncodeLeadingHyphenThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('hyphen');
        $this->backend->encode('-example.com');
    }

    public function testEncodeTrailingHyphenThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('hyphen');
        $this->backend->encode('example-.com');
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
        ];
    }
}
