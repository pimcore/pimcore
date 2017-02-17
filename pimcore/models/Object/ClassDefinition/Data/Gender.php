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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\ClassDefinition\Data;

use Pimcore\Model;

class Gender extends Model\Object\ClassDefinition\Data\Select
{

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "gender";

    /**
     * Gender constructor.
     */
    public function __construct()
    {
        $options = [
            ["key" => "male", "value" => "male"],
            ["key" => "female", "value" => "female"],
            ["key" => "", "value" => "unknown"],
        ];

        $this->setOptions($options);
    }
}
