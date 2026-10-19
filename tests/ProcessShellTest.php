<?php

declare(strict_types=1);

namespace Gadget\Process\Tests;

use Gadget\Process\ProcessException;
use Gadget\Process\ProcessShell;
use Gadget\Process\ProcessShellArgs;
use Gadget\Process\ProcessShellEnv;
use Gadget\Process\ProcessShellInput;
use Gadget\Process\ProcessShellOutput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * Runs real child processes through the local PHP interpreter.
 */
final class ProcessShellTest extends TestCase
{
    /** @var list<array{string, string}> */
    private array $lines = [];

    private ProcessShell $shell;


    protected function setUp(): void
    {
        $this->lines = [];
        $this->shell = new ProcessShell(new ProcessShellEnv(__DIR__));
    }


    public function testOutputTypeConstantsMatchSymfonyProcess(): void
    {
        self::assertSame(Process::OUT, ProcessShell::OUT);
        self::assertSame(Process::ERR, ProcessShell::ERR);
    }


    public function testCreateBuildsAProcessFromArgsAndEnv(): void
    {
        $shell = new ProcessShell(new ProcessShellEnv(__DIR__, ['GADGET_TEST' => 'yes']));

        $process = $shell->create(new ProcessShellArgs(['echo', 'hello']));

        self::assertSame(__DIR__, $process->getWorkingDirectory());
        self::assertSame('yes', $process->getEnv()['GADGET_TEST']);
        self::assertNull($process->getTimeout());
        self::assertStringContainsString('hello', $process->getCommandLine());
        self::assertFalse($process->isStarted());
    }


    public function testExecuteCapturesStandardOutput(): void
    {
        $exitCode = $this->shell->execute(
            self::phpArgs('echo "hello";'),
            new ProcessShellInput(),
            $this->collector()
        );

        self::assertSame(0, $exitCode);
        self::assertSame([[ProcessShell::OUT, 'hello']], $this->lines);
    }


    public function testExecuteCapturesStandardError(): void
    {
        $exitCode = $this->shell->execute(
            self::phpArgs('fwrite(STDERR, "oops");'),
            new ProcessShellInput(),
            $this->collector()
        );

        self::assertSame(0, $exitCode);
        self::assertSame([[ProcessShell::ERR, 'oops']], $this->lines);
    }


    public function testExecuteReturnsTheExitCode(): void
    {
        $exitCode = $this->shell->execute(
            self::phpArgs('exit(3);'),
            new ProcessShellInput(),
            $this->collector()
        );

        self::assertSame(3, $exitCode);
    }


    public function testExecuteFeedsInputToTheProcess(): void
    {
        $this->shell->execute(
            self::phpArgs('echo strtoupper(stream_get_contents(STDIN));'),
            new ProcessShellInput('shout'),
            $this->collector()
        );

        self::assertSame([[ProcessShell::OUT, 'SHOUT']], $this->lines);
    }


    public function testExecuteRunsInTheConfiguredWorkingDirectoryWithTheConfiguredEnv(): void
    {
        $shell = new ProcessShell(new ProcessShellEnv(__DIR__, ['GADGET_TEST' => 'from-env']));

        $shell->execute(
            self::phpArgs('echo getcwd(), "|", getenv("GADGET_TEST");'),
            new ProcessShellInput(),
            $this->collector()
        );

        self::assertSame([[ProcessShell::OUT, __DIR__ . '|from-env']], $this->lines);
    }


    public function testStartReturnsARunningProcess(): void
    {
        $process = $this->shell->start(
            self::phpArgs('echo "started";'),
            new ProcessShellInput(),
            $this->collector()
        );

        self::assertTrue($process->isStarted());
        self::assertSame(0, $process->wait());
        self::assertSame([[ProcessShell::OUT, 'started']], $this->lines);
    }


    public function testExecuteAllReturnsEveryExitCodeWhenNotThrowing(): void
    {
        $exitCodes = $this->shell->executeAll(
            [
                [self::phpArgs('exit(0);'), new ProcessShellInput(), $this->collector()],
                [self::phpArgs('exit(2);'), new ProcessShellInput(), $this->collector()],
                [self::phpArgs('exit(0);'), new ProcessShellInput(), $this->collector()],
            ],
            false
        );

        self::assertSame([0, 2, 0], $exitCodes);
    }


    public function testExecuteAllThrowsOnTheFirstFailureByDefault(): void
    {
        $this->expectException(ProcessException::class);
        $this->expectExceptionMessage('Invalid exit code: 2');

        $this->shell->executeAll([
            [self::phpArgs('echo "first";'), new ProcessShellInput(), $this->collector()],
            [self::phpArgs('exit(2);'), new ProcessShellInput(), $this->collector()],
            [self::phpArgs('echo "never";'), new ProcessShellInput(), $this->collector()],
        ]);
    }


    public function testExecuteAllStopsAfterAFailure(): void
    {
        try {
            $this->shell->executeAll([
                [self::phpArgs('echo "first";'), new ProcessShellInput(), $this->collector()],
                [self::phpArgs('exit(2);'), new ProcessShellInput(), $this->collector()],
                [self::phpArgs('echo "never";'), new ProcessShellInput(), $this->collector()],
            ]);
            self::fail('Expected a ProcessException');
        } catch (ProcessException) {
            self::assertSame([[ProcessShell::OUT, 'first']], $this->lines);
        }
    }


    public function testStartAllRunsEveryProcessAndReturnsExitCodesInStartOrder(): void
    {
        $exitCodes = $this->shell->startAll(
            [
                [self::phpArgs('usleep(50000); exit(0);'), new ProcessShellInput(), $this->collector()],
                [self::phpArgs('exit(1);'), new ProcessShellInput(), $this->collector()],
                [self::phpArgs('exit(2);'), new ProcessShellInput(), $this->collector()],
            ],
            2
        );

        ksort($exitCodes);
        self::assertSame([0, 1, 2], $exitCodes);
    }


    public function testStartAllCapturesOutputFromEveryProcess(): void
    {
        $this->shell->startAll([
            [self::phpArgs('echo "a";'), new ProcessShellInput(), $this->collector()],
            [self::phpArgs('echo "b";'), new ProcessShellInput(), $this->collector()],
        ]);

        $messages = array_map(fn(array $line): string => $line[1], $this->lines);
        sort($messages);
        self::assertSame(['a', 'b'], $messages);
    }


    public function testStartAllWithNothingToRunReturnsNoExitCodes(): void
    {
        self::assertSame([], $this->shell->startAll([]));
    }


    /**
     * Arguments that run a snippet of PHP code in a child process.
     *
     * @param string $code
     * @return ProcessShellArgs
     */
    private static function phpArgs(string $code): ProcessShellArgs
    {
        return new ProcessShellArgs([PHP_BINARY, '-r', $code]);
    }


    /**
     * An output handler that records every line into $this->lines.
     *
     * @return ProcessShellOutput
     */
    private function collector(): ProcessShellOutput
    {
        return new ProcessShellOutput(function (string $type, string $message): void {
            $this->lines[] = [$type, $message];
        });
    }
}
