<?php

declare(strict_types=1);

namespace Gadget\Process\Tests;

use Gadget\Process\ProcessShellOutput;
use PHPUnit\Framework\TestCase;

final class ProcessShellOutputTest extends TestCase
{
    public function testInvokingWithoutACallbackThrows(): void
    {
        $this->expectException(\RuntimeException::class);

        (new ProcessShellOutput())('out', 'message');
    }


    public function testGetOutputWithoutACallbackThrows(): void
    {
        $this->expectException(\RuntimeException::class);

        (new ProcessShellOutput())->getOutput();
    }


    public function testPassesTypeAndMessageToTheCallback(): void
    {
        $received = [];
        $output = new ProcessShellOutput(
            function (string $type, string $message) use (&$received): string {
                $received[] = [$type, $message];
                return 'handled';
            }
        );

        self::assertSame('handled', $output('out', 'hello'));
        self::assertSame([['out', 'hello']], $received);
    }


    public function testTrimsMessagesByDefault(): void
    {
        $output = new ProcessShellOutput(fn(string $type, string $message): string => $message);

        self::assertTrue($output->getTrimOutput());
        self::assertSame('hello', $output('out', "  hello\n"));
    }


    public function testCanKeepSurroundingWhitespace(): void
    {
        $output = new ProcessShellOutput(fn(string $type, string $message): string => $message, false);

        self::assertFalse($output->getTrimOutput());
        self::assertSame("  hello\n", $output('out', "  hello\n"));
    }


    public function testSettersAreChainable(): void
    {
        $output = new ProcessShellOutput();
        $callback = fn(string $type, string $message): string => $type;

        self::assertSame($output, $output->setOutput($callback));
        self::assertSame($output, $output->setTrimOutput(false));
        self::assertSame($callback, $output->getOutput());
        self::assertFalse($output->getTrimOutput());
    }


    public function testFromArrayFansOutToEveryOutput(): void
    {
        $first = [];
        $second = [];
        $combined = ProcessShellOutput::fromArray([
            new ProcessShellOutput(function (string $type, string $message) use (&$first): string {
                $first[] = $message;
                return 'first';
            }),
            new ProcessShellOutput(function (string $type, string $message) use (&$second): string {
                $second[] = $message;
                return 'second';
            }, false),
        ]);

        $result = $combined('out', " hi \n");

        self::assertSame(['first', 'second'], $result);
        self::assertSame(['hi'], $first);
        self::assertSame([" hi \n"], $second);
    }
}
