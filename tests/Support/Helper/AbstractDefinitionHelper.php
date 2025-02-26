<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Tests\Support\Helper;

use Codeception\Module;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Tests\Support\Util\TestHelper;
use ReflectionClass;

abstract class AbstractDefinitionHelper extends Module
{
    protected array $config = [
        'initialize_definitions' => true,
        'cleanup' => true,
    ];

    protected function getClassManager(): Module|ClassManager
    {
        return $this->getModule('\\' . ClassManager::class);
    }

    public function _beforeSuite(array $settings = []): void
    {
        if ($this->config['initialize_definitions']) {
            if (TestHelper::supportsDbTests()) {
                $this->initializeDefinitions();
            } else {
                $this->debug(sprintf(
                    '[%s] Not initializing model definitions as DB is not connected',
                    strtoupper((new ReflectionClass($this))->getShortName())
                ));
            }
        }
    }

    public function _afterSuite(): void
    {
        if ($this->config['cleanup']) {
            TestHelper::cleanUp();
        }
    }

    public function createDataChild(string $type, ?string $name = null, bool $mandatory = false, bool $index = false, bool $visibleInGridView = true, bool $visibleInSearchResult = true): Data
    {
        if (!$name) {
            $name = $type;
        }

        $classname = 'Pimcore\\Model\\DataObject\\ClassDefinition\Data\\' . ucfirst($type);
        /** @var Data $child */
        $child = new $classname();
        $child->setName($name);
        $child->setTitle($name);
        $child->setMandatory($mandatory);
        $child->setIndex($index);
        $child->setVisibleGridView($visibleInGridView);
        $child->setVisibleSearch($visibleInSearchResult);

        return $child;
    }

    abstract public function initializeDefinitions(): void;
}
