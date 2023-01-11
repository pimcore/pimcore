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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Tests\Support\Helper;

use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Fieldcollection\Definition;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Tests\Support\Helper\ClassManager;
use Pimcore\Tests\Support\Helper\DataType\TestDataHelper;
use Pimcore\Tests\Support\Helper\Model;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EcommerceModel extends Model
{
    protected function createClass(string $name, ClassDefinition\Layout $layout, string $filename, bool $inheritanceAllowed = false, ?string $id = null): ClassDefinition
    {
        //add index selection fields
        $rootPanel = $layout->getChildren();
        $mainPanel = $rootPanel[0]->getChildren()[0];

        $mainPanel->addChild($this->createDataChild('indexFieldSelection', 'indexFieldSelection', false));
        $mainPanel->addChild($this->createDataChild('indexFieldSelectionCombo', 'indexFieldSelectionCombo', false));
        $mainPanel->addChild($this->createDataChild('indexFieldSelectionField', 'indexFieldSelectionField', false));

        return parent::createClass($name, $layout, $filename, $inheritanceAllowed, $id);
    }

    public function createDataChild(string $type, ?string $name = null, bool $mandatory = false, int $index = 0, bool $visibleInGridView = true, bool $visibleInSearchResult = true): Data
    {
        if (!$name) {
            $name = $type;
        }

        if (strpos($type, 'indexField') === 0) {
            $classname = 'Pimcore\\Bundle\\EcommerceFrameworkBundle\\CoreExtensions\\ClassDefinition\\' . ucfirst($type);
        } else {
            $classname = 'Pimcore\\Model\\DataObject\\ClassDefinition\Data\\' . ucfirst($type);
        }
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
}