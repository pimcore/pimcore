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
use Pimcore\Tool;

class Text extends Model\Object\ClassDefinition\Layout
{
    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'text';

    /**
     * @var string
     */
    public $html = '';

    /**
     * @var string
     */
    public $renderingClass;

    /**
     * @var string
     */
    public $renderingData;

    /**
     * @return string
     */
    public function getHtml()
    {
        return $this->html;
    }

    /**
     * @param $html
     *
     * @return $this
     */
    public function setHtml($html)
    {
        $this->html = $html;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRenderingClass()
    {
        return $this->renderingClass;
    }

    /**
     * @param mixed $renderingClass
     */
    public function setRenderingClass($renderingClass)
    {
        $this->renderingClass = $renderingClass;
    }

    /**
     * @return mixed
     */
    public function getRenderingData()
    {
        return $this->renderingData;
    }

    /**
     * @param mixed $renderingData
     */
    public function setRenderingData($renderingData)
    {
        $this->renderingData = $renderingData;
    }

    /** Override point for Enriching the layout definition before the layout is returned to the admin interface.
     * @param $object Model\Object\Concrete
     * @param array $context additional contextual data
     */
    public function enrichLayoutDefinition($object, $context = [])
    {
        $renderingClass = $this->getRenderingClass();

        if (Tool::classExists($renderingClass)) {
            if (method_exists($renderingClass, 'renderLayoutText')) {
                $context['fieldname'] = $this->getName();

                $result = call_user_func($renderingClass . '::renderLayoutText', $this->renderingData, $object, $context);
                $this->html = $result;
            }
        }
    }
}
