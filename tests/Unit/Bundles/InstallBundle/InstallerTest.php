<?php

namespace Pimcore\Tests\Unit\Bundles\InstallBundle;

use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\InstallBundle\Installer;
use Pimcore\Bundle\InstallBundle\SystemConfig\ConfigWriter;
use Pimcore\Bundle\InstallBundle\SystemConfig\ConfigWriterFactory;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class InstallerTest extends TestCase
{
    /**
     * @var ConfigWriter|ObjectProphecy
     */
    private $configWriter;

    /**
     * @var Installer
     */
    private $installer;

    protected function setUp()
    {
        parent::setUp();

        $logger = $this->prophesize(LoggerInterface::class);
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        $this->configWriter = $this->prophesize(ConfigWriter::class);

        $configWriterFactory = $this->prophesize(ConfigWriterFactory::class);
        $configWriterFactory->create()->willReturn($this->configWriter->reveal());

        $this->installer = new Installer($logger->reveal(), $eventDispatcher->reveal(), $configWriterFactory->reveal());
    }

    /**
     * Ensure that database config is not written when setSkipDatabaseConfig to true.
     *
     * @test
     */
    public function installer_skips_database_connection_setup_if_configured(): void
    {
        // Opt out of writing database config.
        $this->installer->setSkipDatabaseConfig(true);
        $this->installer->createConfigFiles([]);

        // Ensure that database config file has not been written.
        $this->configWriter->writeDbConfig(Argument::any())->shouldNotHaveBeenCalled();

        // Make sure other config types have been written.
        $this->configWriter->writeSystemConfig()->shouldHaveBeenCalled();
        $this->configWriter->writeDebugModeConfig()->shouldHaveBeenCalled();
        $this->configWriter->generateParametersFile()->shouldHaveBeenCalled();
    }

    /**
     * Ensure that installer writes database config files by default.
     *
     * @test
     */
    public function installer_wries_database_config_by_default(): void
    {
        $dbConfig = [];
        $this->installer->createConfigFiles($dbConfig);

        // Ensure that database config file has not been written.
        $this->configWriter->writeDbConfig($dbConfig)->shouldHaveBeenCalled();

        // Make sure other config types have been written.
        $this->configWriter->writeSystemConfig()->shouldHaveBeenCalled();
        $this->configWriter->writeDebugModeConfig()->shouldHaveBeenCalled();
        $this->configWriter->generateParametersFile()->shouldHaveBeenCalled();
    }
}
