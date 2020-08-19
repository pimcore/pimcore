<?php

namespace Pimcore\Tests\Unit\Bundles\InstallBundle\SystemConfig;

use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\InstallBundle\SystemConfig\ConfigWriter;
use Pimcore\Bundle\InstallBundle\SystemConfig\PartialConfigWriter;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

class PartialConfigWriterTest extends TestCase
{
    /**
     * @var ConfigWriter|ObjectProphecy
     */
    private $configWriter;

    /**
     * @var PartialConfigWriter
     */
    private $partialConfigWriter;

    protected function setUp()
    {
        parent::setUp();

        $this->configWriter = $this->prophesize(ConfigWriter::class);

        $this->partialConfigWriter = new PartialConfigWriter(
            $this->configWriter->reveal()
        );
    }

    /**
     * Ensure that database config is not written when setSkipDatabaseConfig to true.
     *
     * @test
     */
    public function writer_skips_database_connection_setup_if_configured(): void
    {
        // Opt out of writing database config.
        $this->partialConfigWriter = new PartialConfigWriter(
            $this->configWriter->reveal(),
            ['writeDbConfig']
        );
        $this->partialConfigWriter->createConfigFiles([]);

        // Ensure that database config file has not been written.
        $this->configWriter->writeDbConfig(Argument::any())->shouldNotHaveBeenCalled();

        // Make sure other config types have been written.
        $this->configWriter->writeSystemConfig()->shouldHaveBeenCalled();
        $this->configWriter->writeDebugModeConfig()->shouldHaveBeenCalled();
        $this->configWriter->generateParametersFile()->shouldHaveBeenCalled();
    }

    /**
     * Ensure that writer creates database config files by default.
     *
     * @test
     */
    public function writer_writes_database_config_by_default(): void
    {
        $dbConfig = [];
        $this->partialConfigWriter->createConfigFiles($dbConfig);

        // Ensure that database config file has been written.
        $this->configWriter->writeDbConfig($dbConfig)->shouldHaveBeenCalled();

        // Make sure other config types have been written also.
        $this->configWriter->writeSystemConfig()->shouldHaveBeenCalled();
        $this->configWriter->writeDebugModeConfig()->shouldHaveBeenCalled();
        $this->configWriter->generateParametersFile()->shouldHaveBeenCalled();
    }
}
