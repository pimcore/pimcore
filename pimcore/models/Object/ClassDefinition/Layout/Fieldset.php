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
 * @package    Object|Class
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\ClassDefinition\Layout;

use Pimcore\Model;

class Fieldset extends Model\Object\ClassDefinition\Layout
{
    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'fieldset';

    /**
     * Width of input field labels
     *
     * @var int
     */
    public $labelWidth = 100;

    /**
     * @param $labelWidth
     *
     * @return $this
     */
    public function setLabelWidth($labelWidth)
    {
        if (!empty($labelWidth)) {
            $this->labelWidth = intval($labelWidth);
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getLabelWidth()
    {
        return $this->labelWidth;
    }
}
