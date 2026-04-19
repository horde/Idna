<?php

declare(strict_types=1);

namespace Horde\Idna\Test;

use Horde\Exception\HordeException;
use Horde\Idna\Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(Exception::class)]
class ExceptionTest extends TestCase
{
    public function testExtendsHordeException(): void
    {
        $exception = new Exception('test');
        $this->assertInstanceOf(HordeException::class, $exception);
    }

    public function testExtendsBaseException(): void
    {
        $exception = new Exception('test');
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testMessageIsPreserved(): void
    {
        $exception = new Exception('Domain name is too long');
        $this->assertSame('Domain name is too long', $exception->getMessage());
    }

    public function testCodeIsPreserved(): void
    {
        $exception = new Exception('error', 42);
        $this->assertSame(42, $exception->getCode());
    }

    public function testPreviousExceptionIsPreserved(): void
    {
        $previous = new RuntimeException('original');
        $exception = new Exception('wrapped', 0, $previous);
        $this->assertSame($previous, $exception->getPrevious());
    }
}
