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

namespace Pimcore\DataObject\GridColumnConfig\Operator;

class BooleanFormatter extends AbstractOperator
{
    /** @var string */
    private $yesValue;

    /** @var string */
    private $noValue;

    public function __construct(\stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->yesValue = $config->yesValue ?? '';
        $this->noValue = $config->noValue ?? '';
    }

    public function getLabeledValue($element)
    {
        $result = new \stdClass();
        $result->label = $this->label;

        $childs = $this->getChilds();

        $booleanResult = null;

        foreach ($childs as $c) {
            $childResult = $c->getLabeledValue($element);
            $childValues = $childResult->value;
            if ($childValues && !is_array($childValues)) {
                $childValues = [$childValues];
            }

            if (is_array($childValues)) {
                foreach ($childValues as $value) {
                    $value = (bool) $value;
                    $booleanResult = is_null($booleanResult) ? $value : $booleanResult && $value;
                }
            } else {
                $booleanResult = false;
            }
        }

        $booleanResult = $booleanResult ? $this->getYesValue() : $this->getNoValue();
        $result->value = $booleanResult;

        return $result;
    }

    /**
     * @return mixed
     */
    public function getYesValue()
    {
        return $this->yesValue;
    }

    /**
     * @param mixed $yesValue
     */
    public function setYesValue($yesValue)
    {
        $this->yesValue = $yesValue;
    }

    /**
     * @return mixed
     */
    public function getNoValue()
    {
        return $this->noValue;
    }

    /**
     * @param mixed $noValue
     */
    public function setNoValue($noValue)
    {
        $this->noValue = $noValue;
    }
}
