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

namespace Pimcore\Model\DataObject\ImportColumnConfig\Value;

use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\ImportColumnConfig\AbstractConfigElement;
use Pimcore\Model\DataObject\ImportColumnConfig\ValueInterface;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData;
use Pimcore\Model\DataObject\Objectbrick\Definition;

class DefaultValue extends AbstractConfigElement implements ValueInterface
{
    /**
     * @var string
     */
    private $mode;

    /**
     * @var bool
     */
    private $doNotOverwrite;

    /**
     * @var bool
     */
    private $skipEmptyValues;

    public function __construct(\stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->mode            = $config->mode;
        $this->doNotOverwrite  = (bool)$config->doNotOverwrite;
        $this->skipEmptyValues = (bool)$config->skipEmptyValues;
    }

    public function process($element, &$target, array &$rowData, $colIndex, array &$context = [])
    {
        /** @var ClassDefinition|Definition $container */
        $container = null;

        /** @var string $realAttribute */
        $realAttribute = null;

        if ($target instanceof Concrete) {
            $realAttribute = $this->attribute;
            $container = $target->getClass();
        } elseif ($target instanceof AbstractData) {
            $keyParts = explode('~', $this->attribute);
            $brickType = $keyParts[0];
            $realAttribute = $keyParts[1];
            $container = Definition::getByKey($brickType);
        }

        if (null === $container) {
            throw new \RuntimeException('Container could not be resolved');
        }

        $fd = $container->getFieldDefinition($realAttribute);

        if (!$fd) {
            /** @var ClassDefinition\Data\Localizedfields $lfDef */
            $lfDef = $container->getFieldDefinition('localizedfields');

            if ($lfDef) {
                $fd = $lfDef->getFieldDefinition($realAttribute);
            }
        }

        if (!$fd) {
            return;
        }

        $data = $rowData[$colIndex];

        if ($this->skipEmptyValues && !$data) {
            return;
        }

        if ($this->mode != 'direct') {
            $data = $fd->getFromCsvImport($data);
        }

        $setter = 'set' . ucfirst($realAttribute);
        if ($this->doNotOverwrite) {
            $getter = 'get' . ucfirst($realAttribute);
            $currentValue = $target->$getter();
            if ($currentValue) {
                return;
            }
        }

        $target->$setter($data);
    }
}
