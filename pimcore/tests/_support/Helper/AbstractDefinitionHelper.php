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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Tests\Helper;

use Codeception\Module;
use Pimcore\Model\Object\AbstractObject;
use Pimcore\Tests\Util\TestHelper;

abstract class AbstractDefinitionHelper extends Module
{
    /**
     * @var array
     */
    protected $config = [
        'initialize_definitions' => true,
        'cleanup'                => true
    ];

    /**
     * @return Module|ClassManager
     */
    protected function getClassManager()
    {
        return $this->getModule('\\' . ClassManager::class);
    }

    /**
     * @inheritDoc
     */
    public function _beforeSuite($settings = [])
    {
        if ($this->config['initialize_definitions']) {
            if (TestHelper::supportsDbTests()) {
                $this->initializeDefinitions();
            } else {
                $this->debug(sprintf(
                    '[%s] Not initializing model definitions as DB is not connected',
                        strtoupper((new \ReflectionClass($this))->getShortName())
                ));
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function _afterSuite()
    {
        if ($this->config['cleanup']) {
            TestHelper::cleanUp();
        }
    }

    abstract public function initializeDefinitions();
}

