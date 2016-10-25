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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Object\Folder\Dao getDao()
 */
class Folder extends AbstractObject
{

    /**
     * @var string
     */
    public $o_type = "folder";

    /**
     * @param array $values
     * @return Folder
     */
    public static function create($values)
    {
        $object = new static();
        $object->setValues($values);

        $object->save();

        return $object;
    }

    /**
     * @return void
     */
    protected function update()
    {
        parent::update();
        $this->getDao()->update();
    }

    /**
     * @throws \Exception
     */
    public function delete()
    {
        if ($this->getId() == 1) {
            throw new \Exception("root-node cannot be deleted");
        }

        parent::delete();
    }
}
