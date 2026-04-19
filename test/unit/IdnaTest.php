<?php

declare(strict_types=1);

namespace Horde\Idna\Test;

use Horde\Idna\Backend\BackendInterface;
use Horde\Idna\Backend\Intl;
use Horde\Idna\Backend\Punycode;
use Horde\Idna\Idna;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Idna::class)]
class IdnaTest extends TestCase
{
    public function testCreateReturnsIdnaInstance(): void
    {
        $idna = Idna::create();
        $this->assertInstanceOf(Idna::class, $idna);
    }

    public function testConstructorAcceptsBackendInterface(): void
    {
        $backend = $this->createStub(BackendInterface::class);
        $idna = new Idna($backend);
        $this->assertInstanceOf(Idna::class, $idna);
    }

    public function testEncodeEmptyStringReturnsEmptyString(): void
    {
        $backend = $this->createMock(BackendInterface::class);
        $backend->expects($this->never())->method('encode');

        $idna = new Idna($backend);
        $this->assertSame('', $idna->encode(''));
    }

    public function testDecodeEmptyStringReturnsEmptyString(): void
    {
        $backend = $this->createMock(BackendInterface::class);
        $backend->expects($this->never())->method('decode');

        $idna = new Idna($backend);
        $this->assertSame('', $idna->decode(''));
    }

    public function testEncodeDelegatesToBackend(): void
    {
        $backend = $this->createMock(BackendInterface::class);
        $backend->expects($this->once())
            ->method('encode')
            ->with('münchen.de')
            ->willReturn('xn--mnchen-3ya.de');

        $idna = new Idna($backend);
        $this->assertSame('xn--mnchen-3ya.de', $idna->encode('münchen.de'));
    }

    public function testDecodeDelegatesToBackend(): void
    {
        $backend = $this->createMock(BackendInterface::class);
        $backend->expects($this->once())
            ->method('decode')
            ->with('xn--mnchen-3ya.de')
            ->willReturn('münchen.de');

        $idna = new Idna($backend);
        $this->assertSame('münchen.de', $idna->decode('xn--mnchen-3ya.de'));
    }

    public function testCreateWithIntlExtension(): void
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('Requires intl extension');
        }

        $idna = Idna::create();
        $this->assertSame('xn--mnchen-3ya.de', $idna->encode('münchen.de'));
    }

    public function testCreateWithExplicitIntlBackend(): void
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('Requires intl extension');
        }

        $idna = new Idna(new Intl());
        $this->assertSame('xn--mnchen-3ya.de', $idna->encode('münchen.de'));
        $this->assertSame('münchen.de', $idna->decode('xn--mnchen-3ya.de'));
    }

    public function testCreateWithExplicitPunycodeBackend(): void
    {
        $idna = new Idna(new Punycode());
        $this->assertSame('xn--mnchen-3ya.de', $idna->encode('münchen.de'));
        $this->assertSame('münchen.de', $idna->decode('xn--mnchen-3ya.de'));
    }

    public function testEncodeAsciiPassthrough(): void
    {
        $idna = Idna::create();
        $this->assertSame('attglobal.net', $idna->encode('attglobal.net'));
    }

    public function testDecodeAsciiPassthrough(): void
    {
        $idna = Idna::create();
        $this->assertSame('attglobal.net', $idna->decode('attglobal.net'));
    }
}
