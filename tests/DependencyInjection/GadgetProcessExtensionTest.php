<?php

declare(strict_types=1);

namespace Gadget\Process\Tests\DependencyInjection;

use Gadget\Process\DependencyInjection\GadgetProcessExtension;
use Gadget\Process\ProcessShell;
use Gadget\Process\ProcessShellArgs;
use Gadget\Process\ProcessShellEnv;
use Gadget\Process\ProcessShellInput;
use Gadget\Process\ProcessShellOutput;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class GadgetProcessExtensionTest extends TestCase
{
    /**
     * @return \Generator<string, array{class-string}>
     */
    public static function providesServiceClasses(): \Generator
    {
        yield ProcessShell::class => [ProcessShell::class];
        yield ProcessShellArgs::class => [ProcessShellArgs::class];
        yield ProcessShellEnv::class => [ProcessShellEnv::class];
        yield ProcessShellInput::class => [ProcessShellInput::class];
        yield ProcessShellOutput::class => [ProcessShellOutput::class];
    }


    /**
     * @param class-string $class
     */
    #[DataProvider('providesServiceClasses')]
    public function testRegistersEverySourceClassAsAnAutowiredService(string $class): void
    {
        $container = new ContainerBuilder();

        (new GadgetProcessExtension())->load([], $container);

        self::assertTrue($container->hasDefinition($class), "$class should be registered");
        $definition = $container->getDefinition($class);
        self::assertTrue($definition->isAutowired(), "$class should be autowired");
        self::assertTrue($definition->isAutoconfigured(), "$class should be autoconfigured");
    }


    public function testCompiledContainerBuildsAProcessShell(): void
    {
        $container = new ContainerBuilder();
        (new GadgetProcessExtension())->load([], $container);
        $container->getDefinition(ProcessShell::class)->setPublic(true);

        $container->compile();

        self::assertInstanceOf(ProcessShell::class, $container->get(ProcessShell::class));
    }
}
