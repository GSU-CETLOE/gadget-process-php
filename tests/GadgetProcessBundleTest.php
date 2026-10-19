<?php

declare(strict_types=1);

namespace Gadget\Process\Tests;

use Gadget\Process\DependencyInjection\GadgetProcessExtension;
use Gadget\Process\GadgetProcessBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class GadgetProcessBundleTest extends TestCase
{
    public function testIsASymfonyBundle(): void
    {
        self::assertInstanceOf(Bundle::class, new GadgetProcessBundle());
    }


    public function testExposesTheGadgetProcessExtension(): void
    {
        $extension = (new GadgetProcessBundle())->getContainerExtension();

        self::assertInstanceOf(GadgetProcessExtension::class, $extension);
        self::assertSame('gadget_process', $extension->getAlias());
    }
}
