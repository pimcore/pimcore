<?php
declare(strict_types=1);

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
     * {@inheritdoc}
     */
    protected string $o_type = 'folder';

    /**
     * @param array $values
     *
     * @return Folder
     */
    public static function create(array $values): Folder
    {
        $object = new static();
        self::checkCreateData($values);
        $object->setValues($values);

        $object->save();

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    protected function update(bool $isUpdate = null, array $params = [])
    {
        parent::update($isUpdate, $params);
        $this->getDao()->update($isUpdate);
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        if ($this->getId() == 1) {
            throw new \Exception('root-node cannot be deleted');
        }

        parent::delete();
    }
}
