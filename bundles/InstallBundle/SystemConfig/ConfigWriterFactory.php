<?php


namespace Pimcore\Bundle\InstallBundle\SystemConfig;


class ConfigWriterFactory
{
    /**
     * @return ConfigWriter
     */
    public function create()
    {
        return new ConfigWriter();
    }
}
