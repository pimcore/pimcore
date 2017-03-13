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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\ClassDefinition\Layout;

use Pimcore\Model;

class Fieldcontainer extends Model\Object\ClassDefinition\Layout
{
    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "fieldcontainer";

    /**
     * Width of input field labels
     * @var int
     */
    public $labelWidth = 100;

    /**
     * @var string
     */
    public $layout = "hbox";

    /**
     * @var string
     */
    public $fieldLabel;

    /**
     * @param $labelWidth
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

    /**
     * @param $layout
     * @return $this
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;

        return $this;
    }

    /**
     * @return string
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * @param $fieldLabel
     * @return $this
     */
    public function setFieldLabel($fieldLabel)
    {
        $this->fieldLabel = $fieldLabel;

        return $this;
    }

    /**
     * @return string
     */
    public function getFieldLabel()
    {
        return $this->fieldLabel;
    }
}
