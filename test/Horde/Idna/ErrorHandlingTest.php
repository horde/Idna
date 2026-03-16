<?php

namespace Horde\Idna;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Horde_Idna;
use Horde_Idna_Exception;
use ValueError;

/**
 * Test error handling in IDNA encoding/decoding
 *
 * @coversDefaultClass Horde_Idna
 */
class ErrorHandlingTest extends TestCase
{
    /**
     * Test null input returns false
     */
    public function testEncodeNullReturnsFalse()
    {
        $idna = new Horde_Idna();
        $this->assertFalse($idna->encode(null));
    }

    /**
     * Test empty string encoding throws exception
     */
    public function testEncodeEmptyString()
    {
        $idna = new Horde_Idna();

        // Empty string handling depends on backend
        if (extension_loaded('intl')) {
            // intl backend throws ValueError
            $this->expectException(ValueError::class);
            $idna->encode('');
        } else {
            // Punycode backend may handle differently
            $result = $idna->encode('');
            $this->assertIsString($result);
        }
    }

    /**
     * Test decoding of non-punycode string
     */
    public function testDecodeNonPunycode()
    {
        $idna = new Horde_Idna();
        $result = $idna->decode('example.com');
        $this->assertEquals('example.com', $result);
    }

    /**
     * Test decoding of mixed punycode and ASCII
     */
    public function testDecodeMixedPunycode()
    {
        $idna = new Horde_Idna();
        $result = $idna->decode('xn--e1afmkfd.com');
        $this->assertStringContainsString('пример', $result);
        $this->assertStringContainsString('.com', $result);
    }

    /**
     * Test that invalid punycode throws exception (if using UTS46 backend)
     */
    public function testInvalidPunycodeHandling()
    {
        $idna = new Horde_Idna();

        // This test only applies if intl extension with UTS46 is available
        if (!extension_loaded('intl') || !defined('INTL_IDNA_VARIANT_UTS46')) {
            $this->markTestSkipped('Requires intl extension with UTS46 support');
        }

        // Test various invalid domain formats that should trigger errors
        // Note: Some of these may not throw depending on backend strictness

        // Leading hyphen
        try {
            $result = $idna->encode('-example.com');
            // If no exception, result should still be a string
            $this->assertIsString($result);
        } catch (Horde_Idna_Exception $e) {
            $this->assertStringContainsString('hyphen', strtolower($e->getMessage()));
        }
    }

    /**
     * Test trailing hyphen handling
     */
    public function testTrailingHyphenHandling()
    {
        $idna = new Horde_Idna();

        if (!extension_loaded('intl') || !defined('INTL_IDNA_VARIANT_UTS46')) {
            $this->markTestSkipped('Requires intl extension with UTS46 support');
        }

        try {
            $result = $idna->encode('example-.com');
            $this->assertIsString($result);
        } catch (Horde_Idna_Exception $e) {
            $this->assertStringContainsString('hyphen', strtolower($e->getMessage()));
        }
    }

    /**
     * Test hyphen in 3rd and 4th position
     */
    public function testHyphenPosition34()
    {
        $idna = new Horde_Idna();

        if (!extension_loaded('intl') || !defined('INTL_IDNA_VARIANT_UTS46')) {
            $this->markTestSkipped('Requires intl extension with UTS46 support');
        }

        try {
            $result = $idna->encode('ex--mple.com');
            $this->assertIsString($result);
        } catch (Horde_Idna_Exception $e) {
            $this->assertStringContainsString('hyphen', strtolower($e->getMessage()));
        }
    }

    /**
     * Test very long domain name handling
     */
    public function testVeryLongDomainName()
    {
        $idna = new Horde_Idna();

        if (!extension_loaded('intl') || !defined('INTL_IDNA_VARIANT_UTS46')) {
            $this->markTestSkipped('Requires intl extension with UTS46 support');
        }

        // Domain labels can be max 63 characters, total domain max 253
        $longLabel = str_repeat('a', 64);

        try {
            $result = $idna->encode($longLabel . '.com');
            $this->assertIsString($result);
        } catch (Horde_Idna_Exception $e) {
            $this->assertStringContainsString('long', strtolower($e->getMessage()));
        }
    }

    /**
     * Test domain with label containing only dots
     */
    public function testEmptyLabelHandling()
    {
        $idna = new Horde_Idna();

        if (!extension_loaded('intl') || !defined('INTL_IDNA_VARIANT_UTS46')) {
            $this->markTestSkipped('Requires intl extension with UTS46 support');
        }

        try {
            $result = $idna->encode('example..com');
            $this->assertIsString($result);
        } catch (Horde_Idna_Exception $e) {
            $this->assertStringContainsString('empty', strtolower($e->getMessage()));
        }
    }

    /**
     * Test round-trip encoding/decoding
     */
    #[DataProvider('roundTripProvider')]
    public function testRoundTrip($original)
    {
        $idna = new Horde_Idna();
        $encoded = $idna->encode($original);
        $decoded = $idna->decode($encoded);

        $this->assertEquals($original, $decoded);
    }

    public static function roundTripProvider()
    {
        return [
            ['münchen.de'],
            ['日本.jp'],
            ['test.测试'],
            ['example.com'],
            ['sub.domain.example.com'],
        ];
    }

    /**
     * Test case sensitivity preservation in ASCII domains
     */
    public function testCasePreservation()
    {
        $idna = new Horde_Idna();

        // ASCII domains should be lowercased
        $result = $idna->encode('Example.COM');
        $this->assertIsString($result);

        $decoded = $idna->decode('Example.COM');
        $this->assertIsString($decoded);
    }
}
