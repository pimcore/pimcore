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

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\Tool;

class Personamultiselect extends Model\DataObject\ClassDefinition\Data\Multiselect
{
    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'personamultiselect';

    public function configureOptions()
    {
        $list = new Tool\Targeting\Persona\Listing();
        $list->setOrder('asc');
        $list->setOrderKey('name');
        $personas = $list->load();

        $options = [];
        foreach ($personas as $persona) {
            $options[] = [
                'value' => $persona->getId(),
                'key' => $persona->getName()
            ];
        }

        $this->setOptions($options);
    }

    /**
     * @param $data
     *
     * @return static
     */
    public static function __set_state($data)
    {
        $obj = parent::__set_state($data);
        $options = $obj->getOptions();
        if (\Pimcore::inAdmin() || empty($options)) {
            $obj->configureOptions();
        }

        return $obj;
    }
}
