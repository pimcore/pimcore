<?php

namespace Pimcore\Tests\Unit\Bundles\InstallBundle;

use Monolog\Logger;
use Pimcore\Bundle\InstallBundle\Installer;
use Pimcore\Tests\Test\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class InstallerTest extends TestCase
{
    /**
     * @test
     */
    public function the_truth(): void
    {
        $installer = new Installer(new Logger('test'), new EventDispatcher());
        $this->assertNotNull($installer);
    }
}
