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

use Exception;
use Pimcore\Model\DataObject;

/**
 * @method \Pimcore\Model\DataObject\Folder\Dao getDao()
 */
class Folder extends DataObject
{
    protected string $type = 'folder';

    public static function create(array $values): Folder
    {
        $object = new static();
        self::checkCreateData($values);
        $object->setValues($values);

        $object->save();

        return $object;
    }

    protected function update(bool $isUpdate = null, array $params = []): void
    {
        parent::update($isUpdate, $params);
        $this->getDao()->update($isUpdate);
    }

    public function delete(): void
    {
        if ($this->getId() == 1) {
            throw new Exception('root-node cannot be deleted');
        }

        parent::delete();
    }
}
