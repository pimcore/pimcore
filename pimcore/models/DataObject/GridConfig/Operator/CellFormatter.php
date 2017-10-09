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

namespace Pimcore\Model\DataObject\GridConfig\Operator;

class CellFormatter extends AbstractOperator {


    private $maxLength;

    public function __construct($config, $context = null) {
        parent::__construct($config, $context);

        $this->label = $config->cssClass;
        $this->maxLength = $config->maxLength;
    }

    public function getLabeledValue($object) {
        $childs = $this->getChilds();
        if($childs[0]) {
            $result = $childs[0]->getLabeledValue($object);
            if ($this->getMaxLength() && isset($result->value) && strlen($result->value) > $this->getMaxLength()) {
                $result->value = substr($result->value, 0, $this->getMaxLength() - 3) . "...";
            }
            return $result;
        }
        return null;
    }

    /**
     * @return mixed
     */
    public function getMaxLength()
    {
        return $this->maxLength;
    }

    /**
     * @param mixed $maxLength
     */
    public function setMaxLength($maxLength)
    {
        $this->maxLength = $maxLength;
    }



}
