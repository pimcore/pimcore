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

namespace Pimcore\Model\DataObject\ImportResolver;

use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Concrete;

class Id
{
    /**
     * @var
     */
    protected $config;

    /**
     * Id constructor.
     */
    public function __construct($config)
    {
        $this->config = $config;

        $this->resolverService = \Pimcore::getContainer()->get($this->config->resolverSettings->service);
        if (!$this->resolverService) {
            throw new \Exception("could not resolve service: " . $this->config->resolverSettings->service);
        }
        $this->params = $config->params;
    }

    /**
     * @param $parentId
     * @param $rowData
     *
     * @return static
     *
     * @throws \Exception
     */
    public function resolve($parentId, $rowData)
    {
        $object = $this->resolverService->resolve($parentId, $rowData);
        if (!$object) {
            throw new \Exception('Could not resolve object');
        }
        return $object;
    }
}
