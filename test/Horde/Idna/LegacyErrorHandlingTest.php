<?php

declare(strict_types=1);

namespace Horde\Idna\Test;

use Horde_Idna;
use Horde_Idna_Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[CoversClass(Horde_Idna::class)]
class LegacyErrorHandlingTest extends TestCase
{
    public function testEncodeNullReturnsEmptyString(): void
    {
        $this->assertSame('', Horde_Idna::encode(null));
    }

    public function testEncodeEmptyStringReturnsEmptyString(): void
    {
        $this->assertSame('', Horde_Idna::encode(''));
    }

    public function testDecodeAsciiPassthrough(): void
    {
        $this->assertSame('example.com', Horde_Idna::decode('example.com'));
    }

    public function testDecodeMixedPunycode(): void
    {
        $result = Horde_Idna::decode('xn--e1afmkfd.com');
        $this->assertSame('пример.com', $result);
    }

    #[RequiresPhpExtension('intl')]
    public function testEncodeLeadingHyphenThrowsException(): void
    {
        $this->expectException(Horde_Idna_Exception::class);
        Horde_Idna::encode('-example.com');
    }

    #[RequiresPhpExtension('intl')]
    public function testEncodeTrailingHyphenThrowsException(): void
    {
        $this->expectException(Horde_Idna_Exception::class);
        Horde_Idna::encode('example-.com');
    }

    #[RequiresPhpExtension('intl')]
    public function testEncodeHyphenPosition34ThrowsException(): void
    {
        $this->expectException(Horde_Idna_Exception::class);
        Horde_Idna::encode('ex--mple.com');
    }

    #[RequiresPhpExtension('intl')]
    public function testEncodeTooLongLabelThrowsException(): void
    {
        $this->expectException(Horde_Idna_Exception::class);
        Horde_Idna::encode(str_repeat('a', 64) . '.com');
    }

    #[RequiresPhpExtension('intl')]
    public function testEncodeEmptyLabelThrowsException(): void
    {
        $this->expectException(Horde_Idna_Exception::class);
        Horde_Idna::encode('example..com');
    }

    #[DataProvider('roundTripProvider')]
    public function testRoundTrip(string $original): void
    {
        $encoded = Horde_Idna::encode($original);
        $decoded = Horde_Idna::decode($encoded);
        $this->assertSame($original, $decoded);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function roundTripProvider(): array
    {
        return [
            'german' => ['münchen.de'],
            'japanese' => ['日本.jp'],
            'chinese' => ['test.测试'],
            'ascii' => ['example.com'],
            'subdomains' => ['sub.domain.example.com'],
        ];
    }

    public function testEncodeAsciiDomainIsLowercased(): void
    {
        $result = Horde_Idna::encode('Example.COM');
        $this->assertSame('example.com', $result);
    }
}
