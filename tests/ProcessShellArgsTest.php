<?php

declare(strict_types=1);

namespace Gadget\Process\Tests;

use Gadget\Process\ProcessShellArgs;
use PHPUnit\Framework\TestCase;

final class ProcessShellArgsTest extends TestCase
{
    public function testDefaultsToNoArguments(): void
    {
        $args = new ProcessShellArgs();

        self::assertSame([], $args->getArgs());
        self::assertSame('', (string) $args);
    }


    public function testReturnsConstructorArguments(): void
    {
        $args = new ProcessShellArgs(['ls', '-la']);

        self::assertSame(['ls', '-la'], $args->getArgs());
    }


    public function testSetArgsReplacesTheList(): void
    {
        $args = new ProcessShellArgs(['ls']);
        $args->setArgs(['echo', 'hello']);

        self::assertSame(['echo', 'hello'], $args->getArgs());
    }


    public function testStringFormJoinsArgumentsWithSpaces(): void
    {
        $args = new ProcessShellArgs(['echo', 'hello', 'world']);

        self::assertSame('echo hello world', (string) $args);
    }
}
