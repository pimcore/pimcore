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

namespace Pimcore\Model\DataObject\GridColumnConfig\Value;

use Carbon\Carbon;

class Date extends DefaultValue
{
    protected $format;

    public function __construct($config, $context = null)
    {
        parent::__construct($config, $context);

        $this->format = ($config->format ? $config->format : null);
    }

    public function getLabeledValue($element)
    {
        $labeledValue = parent::getLabeledValue($element);
        $theValue = $labeledValue->value;

        if ($this->format && $theValue) {
            $timestamp = null;

            if ($theValue instanceof Carbon) {
                $timestamp = $theValue->getTimestamp();

                $formattedValue = date($this->format, $timestamp);

                $labeledValue->value = $formattedValue;
            }
        } elseif ($theValue instanceof Carbon) {
            if ($this instanceof DateTime) {
                $theValue = $theValue->toDateTimeString();
            } else {
                $theValue = $theValue->toDateString();
            }
            $labeledValue->value = $theValue;
        }

        return $labeledValue;
    }
}
