<?php

declare(strict_types=1);

namespace Gadget\Process\Tests;

use Gadget\Process\ProcessShellInput;
use PHPUnit\Framework\TestCase;

final class ProcessShellInputTest extends TestCase
{
    public function testDefaultsToNoInput(): void
    {
        self::assertNull((new ProcessShellInput())->getInput());
    }


    public function testReturnsConstructorInput(): void
    {
        self::assertSame('hello', (new ProcessShellInput('hello'))->getInput());
    }


    public function testAcceptsAStreamResource(): void
    {
        $stream = fopen('php://memory', 'r+');
        self::assertNotFalse($stream);

        $input = new ProcessShellInput($stream);

        self::assertSame($stream, $input->getInput());
        fclose($stream);
    }


    public function testAcceptsAnIterator(): void
    {
        $iterator = new \ArrayIterator(['a', 'b']);

        self::assertSame($iterator, (new ProcessShellInput($iterator))->getInput());
    }


    public function testSetInputReplacesAndChains(): void
    {
        $input = new ProcessShellInput('first');

        self::assertSame($input, $input->setInput('second'));
        self::assertSame('second', $input->getInput());
    }
}
