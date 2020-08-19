<?php


namespace Pimcore\Bundle\InstallBundle\SystemConfig;


class PartialConfigWriter
{
    /**
     * @var ConfigWriter
     */
    private $configWriter;

    /**
     * @var array
     */
    private $skip;

    /**
     * Allows configuring which of the config files should be written.
     *
     * @param ConfigWriter $configWriter
     * @param array $skip
     */
    public function __construct(ConfigWriter $configWriter, array $skip = [])
    {
        $this->configWriter = $configWriter;
        $this->skip = $skip;
    }

    public function createConfigFiles(array $config)
    {
        if (!$this->skip('writeDbConfig')) {
            $this->configWriter->writeDbConfig($config);
        }

        $this->configWriter->writeSystemConfig();
        $this->configWriter->writeDebugModeConfig();
        $this->configWriter->generateParametersFile();
    }

    /**
     * @return True if config type should be skipped, False - otherwise.
     */
    private function skip(string $configType)
    {
        return in_array($configType, $this->skip, true);
    }
}
