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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\View\Helper;

class EditmodeTooltip extends \Zend_View_Helper_Abstract
{

    /**
     * @var int
     */
    protected static $editmodeTooltipsIncrementer = 0;

    /**
     * @return array
     */
    protected function getDefaultEditmodeTooltipOptions()
    {
        return ["autoHide" => true,
                     "title" => null,
                     "icon" => "/pimcore/static/img/icon/information.png"
        ];
    }

    /**
     * @param $id
     * @return string
     */
    protected function getTooltipIdentifier($id)
    {
        return "editmode_tooltip_" . $id;
    }

    /**
     * Displays a information icon
     *
     * @param $html
     * @param null | string $title
     * @param array $options
     * @return string
     */
    public function editmodeTooltip($html, $title = null, $options = [])
    {
        if ($html) {
            $options = array_merge($this->getDefaultEditmodeTooltipOptions(), $options);
            self::$editmodeTooltipsIncrementer++;
            $options["target"] = $this->getTooltipIdentifier(self::$editmodeTooltipsIncrementer);
            $options["html"] = $html;

            if (!is_null($title)) {
                $options["title"] = $title;
            }

            $s = "<img id='" . $options["target"] . "' src='" . $options["icon"] . "' alt='' class='pimcore_editmode_tooltip' />";
            $s .= "<script type='text/javascript'>new Ext.ToolTip(" . \Zend_Json::encode($options) .");</script>";

            return $s;
        }
    }
}
