<?php

declare(strict_types=1);

namespace Gadget\Process\Tests;

use Gadget\Exception\Exception;
use Gadget\Process\ProcessException;
use PHPUnit\Framework\TestCase;

final class ProcessExceptionTest extends TestCase
{
    public function testIsAGadgetException(): void
    {
        self::assertInstanceOf(Exception::class, new ProcessException('failed'));
    }


    public function testFormatsMessageFromFormatAndArguments(): void
    {
        $exception = new ProcessException(['Invalid exit code: %s', [3]]);

        self::assertSame('Invalid exit code: 3', $exception->getMessage());
    }
}
