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

namespace Pimcore\Model\DataObject\ImportColumnConfig\Operator;

use Pimcore\Model\DataObject\ImportColumnConfig\AbstractConfigElement;
use Pimcore\Model\FactoryInterface;

class ObjectBrickSetter extends AbstractOperator
{
    /**
     * @var FactoryInterface
     */
    private $modelFactory;

    /**
     * @var string
     */
    private $attr;

    /**
     * @var string
     */
    private $brickType;

    /**
     * @var string
     */
    private $mode;

    public function __construct(FactoryInterface $modelFactory, \stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->modelFactory = $modelFactory;

        $this->attr      = $config->attr;
        $this->brickType = $config->brickType;
        $this->mode      = $config->mode;
    }

    public function process($element, &$target, array &$rowData, $colIndex, array &$context = [])
    {
        $brickContainerGetter = 'get' . ucfirst($this->attr);
        $brickContainer       = $target->$brickContainerGetter();

        $brickGetter = 'get' . ucfirst($this->brickType);
        $brick       = $brickContainer->$brickGetter();

        $colData = $rowData[$colIndex];

        if (!$brick) {
            if ($this->mode == 'ifNotEmpty' && $colData || $this->mode == 'always') {
                $brickClass = 'Pimcore\\Model\\DataObject\\Objectbrick\\Data\\' . ucfirst($this->brickType);
                $brick      = $this->modelFactory->build($brickClass, [$element]);
            }
        }

        if (!$brick && $colData) {
            throw new \Exception('brick does not exist');
        }

        $childs = $this->getChilds();

        if (!$childs) {
            return;
        } else {
            /** @var $child AbstractConfigElement */
            foreach ($childs as $child) {
                $child->process($element, $brick, $rowData, $colIndex, $context);
            }
        }

        $bricksetter = 'set' . ucfirst($this->brickType);
        $brickContainer->$bricksetter($brick);

        $brickContainerSetter = 'set' . ucfirst($this->attr);
        $target->$brickContainerSetter($brickContainer);
    }
}
