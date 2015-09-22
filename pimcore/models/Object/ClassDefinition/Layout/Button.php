<?php 
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Object\ClassDefinition\Layout;

use Pimcore\Model;

class Button extends Model\Object\ClassDefinition\Layout {

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "button";

    /**
     * @var
     */
    public $handler;

    /**
     * @var
     */
    public $text;

    /**
     * @return mixed
     */
    public function getText() {
        return $this->text;
    }

    /**
     * @param $text
     * @return $this
     */
    public function setText($text) {
        $this->text = $text;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHandler() {
        return $this->handler;
    }

    /**
     * @param $handler
     * @return $this
     */
    public function setHandler($handler) {
        $this->handler = $handler;
        return $this;
    }
}
