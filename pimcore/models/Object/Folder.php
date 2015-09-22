<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Object;

use Pimcore\Model;

class Folder extends AbstractObject {

    /**
     * @var string
     */
    public $o_type = "folder";

    /**
     * @param array $values
     * @return Folder
     */
    public static function create($values) {
        $object = new static();
        $object->setValues($values);

        $object->save();

        return $object;
    }

    /**
     * @return void
     */
    public function update() {

        parent::update();
        $this->getResource()->update();
    }

    /**
     * @throws \Exception
     */
    public function delete() {

        if ($this->getId() == 1) {
            throw new \Exception("root-node cannot be deleted");
        }

        parent::delete();
    }
}
