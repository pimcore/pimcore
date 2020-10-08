<?php

namespace Pimcore\Bundle\GeneratorBundle\Manipulator;

use Pimcore\Bundle\GeneratorBundle\Generator\Generator;
use Pimcore\Bundle\GeneratorBundle\Model\BaseBundle as Bundle;
use Symfony\Component\Yaml\Yaml;

/**
 * @deprecated
 * Changes the PHP code of a YAML services configuration file.
 *
 * The following class is copied from \Sensio\Bundle\GeneratorBundle\Manipulator\ConfigurationManipulator
 */
class ConfigurationManipulator extends Manipulator
{
    private $file;

    /**
     * @param string $file The YAML configuration file path
     */
    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * Adds a configuration resource at the top of the existing ones.
     *
     * @param Bundle $bundle
     *
     * @throws \RuntimeException If this process fails for any reason
     */
    public function addResource(Bundle $bundle)
    {
        // if the config.yml file doesn't exist, don't even try.
        if (!file_exists($this->file)) {
            throw new \RuntimeException(sprintf('The target config file %s does not exist', $this->file));
        }

        $code = $this->getImportCode($bundle);

        $currentContents = file_get_contents($this->file);
        // Don't add same bundle twice
        if (false !== strpos($currentContents, $code)) {
            throw new \RuntimeException(sprintf('The %s configuration file from %s is already imported', $bundle->getServicesConfigurationFilename(), $bundle->getName()));
        }

        // find the "imports" line and add this at the end of that list
        $lastImportedPath = $this->findLastImportedPath($currentContents);
        if (!$lastImportedPath) {
            throw new \RuntimeException(sprintf('Could not find the imports key in %s', $this->file));
        }

        // find imports:
        $importsPosition = strpos($currentContents, 'imports:');
        // find the last import
        $lastImportPosition = strpos($currentContents, $lastImportedPath, $importsPosition);
        // find the line break after the last import
        $targetLinebreakPosition = strpos($currentContents, "\n", $lastImportPosition);

        $newContents = substr($currentContents, 0, $targetLinebreakPosition)."\n".$code.substr($currentContents, $targetLinebreakPosition);

        if (false === Generator::dump($this->file, $newContents)) {
            throw new \RuntimeException(sprintf('Could not write file %s ', $this->file));
        }
    }

    public function getImportCode(Bundle $bundle)
    {
        return sprintf(<<<EOF
    - { resource: "@%s/Resources/config/%s" }
EOF
        ,
            $bundle->getName(),
            $bundle->getServicesConfigurationFilename()
        );
    }

    /**
     * Finds the last imported resource path in the YAML file.
     *
     * @param string $yamlContents
     *
     * @return bool|string
     */
    private function findLastImportedPath($yamlContents)
    {
        $data = Yaml::parse($yamlContents);
        if (!isset($data['imports'])) {
            return false;
        }

        // find the last imports entry
        $lastImport = end($data['imports']);
        if (!isset($lastImport['resource'])) {
            return false;
        }

        return $lastImport['resource'];
    }
}
