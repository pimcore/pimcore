<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\DataObject;

use Pimcore\Model\DataObject;

/**
 * @method \Pimcore\Model\DataObject\Folder\Dao getDao()
 */
class Folder extends DataObject
{
    /**
     * @var string
     */
    protected $o_type = 'folder';

    /**
     * @param array $values
     *
     * @return Folder
     */
    public static function create($values)
    {
        $object = new static();
        self::checkCreateData($values);
        $object->setValues($values);

        $object->save();

        return $object;
    }

    /**
     * @param bool|null $isUpdate
     * @param array $params additional parameters (e.g. "versionNote" for the version note)
     *
     * @throws \Exception
     */
    protected function update($isUpdate = null, $params = [])
    {
        parent::update($isUpdate, $params);
        $this->getDao()->update($isUpdate);
    }

    /**
     * @inheritdoc
     */
    public function delete()
    {
        if ($this->getId() == 1) {
            throw new \Exception('root-node cannot be deleted');
        }

        parent::delete();
    }
}
