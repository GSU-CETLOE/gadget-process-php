<?php

declare(strict_types=1);

namespace Gadget\Process\Tests;

use Gadget\Process\ProcessShellEnv;
use PHPUnit\Framework\TestCase;

final class ProcessShellEnvTest extends TestCase
{
    public function testDefaultsToCurrentDirectoryAndGlobalEnvironment(): void
    {
        $env = new ProcessShellEnv();

        self::assertSame('.', $env->getWorkDir());
        self::assertSame(
            self::scalarGlobals(),
            $env->getEnv()
        );
    }


    public function testConstructorValuesOverrideGlobals(): void
    {
        $env = new ProcessShellEnv('/tmp', ['GADGET_TEST' => 'one', 'PATH' => '/nowhere']);

        self::assertSame('/tmp', $env->getWorkDir());
        self::assertSame('one', $env->getEnv()['GADGET_TEST']);
        self::assertSame('/nowhere', $env->getEnv()['PATH']);
    }


    public function testSetEnvWithoutGlobalsKeepsOnlyGivenValues(): void
    {
        $env = (new ProcessShellEnv())->setEnv(['ONLY' => 'me'], false);

        self::assertSame(['ONLY' => 'me'], $env->getEnv());
    }


    public function testSetEnvCastsScalarsAndStringablesToStrings(): void
    {
        $stringable = new class () implements \Stringable {
            public function __toString(): string
            {
                return 'from-stringable';
            }
        };

        $env = (new ProcessShellEnv())->setEnv(
            [
                'INT' => 42,
                'FLOAT' => 1.5,
                'TRUE' => true,
                'OBJECT' => $stringable,
            ],
            false
        );

        self::assertSame(
            [
                'INT' => '42',
                'FLOAT' => '1.5',
                'TRUE' => '1',
                'OBJECT' => 'from-stringable',
            ],
            $env->getEnv()
        );
    }


    public function testSetEnvDropsValuesThatCannotBecomeStrings(): void
    {
        $env = (new ProcessShellEnv())->setEnv(
            [
                'KEEP' => 'yes',
                'NULL' => null,
                'ARRAY' => ['a', 'b'],
                'OBJECT' => new \stdClass(),
            ],
            false
        );

        self::assertSame(['KEEP' => 'yes'], $env->getEnv());
    }


    public function testSettersAreChainable(): void
    {
        $env = new ProcessShellEnv();

        self::assertSame($env, $env->setWorkDir('/var'));
        self::assertSame($env, $env->setEnv([]));
        self::assertSame('/var', $env->getWorkDir());
    }


    /**
     * The string form of every scalar entry in $_ENV and $_SERVER, which is
     * what ProcessShellEnv is expected to start from.
     *
     * @return array<string, string>
     */
    private static function scalarGlobals(): array
    {
        $globals = [];
        foreach (array_merge($_ENV, $_SERVER) as $name => $value) {
            if (is_scalar($value)) {
                $globals[(string) $name] = (string) $value;
            }
        }
        return $globals;
    }
}
