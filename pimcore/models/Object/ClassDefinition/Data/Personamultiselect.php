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

namespace Pimcore\Model\Object\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\Tool;

class Personamultiselect extends Model\Object\ClassDefinition\Data\Multiselect {

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "personamultiselect";


    /**
     *
     */
    public function configureOptions() {

        $list = new Tool\Targeting\Persona\Listing();
        $list->setOrder("asc");
        $list->setOrderKey("name");
        $personas = $list->load();

        $options = array();
        foreach ($personas as $persona) {
            $options[] = array(
                "value" => $persona->getId(),
                "key" => $persona->getName()
            );
        }

        $this->setOptions($options);
    }

    /**
     *
     */
    public function __wakeup() {
        $options = $this->getOptions();
        if(\Pimcore::inAdmin() || empty($options)) {
            $this->configureOptions();
        }
    }
}
