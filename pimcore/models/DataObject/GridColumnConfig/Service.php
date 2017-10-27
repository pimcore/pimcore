<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\GridColumnConfig;

use Pimcore\Model\DataObject\GridColumnConfig\Operator\PHPCode;

class Service
{
    /**
     * @param $outputDataConfig
     *
     * @return ConfigElementInterface[]
     */
    public function buildOutputDataConfig($outputDataConfig, $context = null)
    {
        $config = [];
        $config = $this->doBuildConfig($outputDataConfig, $config, $context);

        return $config;
    }

    /**
     * @param $jsonConfig
     * @param $config
     * @param null $context
     *
     * @return array
     */
    private function doBuildConfig($jsonConfig, $config, $context = null)
    {
        if (!empty($jsonConfig)) {
            foreach ($jsonConfig as $configElement) {
                if ($configElement->type == 'value') {
                    $name = 'Pimcore\\Model\\DataObject\\GridColumnConfig\\Value\\' . ucfirst($configElement->class);

                    if (class_exists($name)) {
                        $config[] = new $name($configElement, $context);
                    }
                } elseif ($configElement->type == 'operator') {
                    $name = 'Pimcore\\Model\\DataObject\\GridColumnConfig\\Operator\\' . ucfirst($configElement->class);

                    if (!empty($configElement->childs)) {
                        $configElement->childs = $this->doBuildConfig($configElement->childs, [], $context);
                    }

                    if (class_exists($name)) {
                        $operatorInstance = new $name($configElement, $context);
                        if ($operatorInstance instanceof PHPCode) {
                            $operatorInstance = $operatorInstance->getRealInstance();
                        }
                        if ($operatorInstance) {
                            $config[] = $operatorInstance;
                        }
                    }
                }
            }
        }

        return $config;
    }
}
