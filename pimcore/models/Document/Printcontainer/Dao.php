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
 * @package    Document
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document\Printcontainer;

use \Pimcore\Model\Document;

/**
 * @property \Pimcore\Model\Document\Printcontainer $model
 */
class Dao extends Document\PrintAbstract\Dao
{
    public function getLastedChildModificationDate()
    {
        $path = $this->model->getFullPath();

        return $this->db->fetchOne("SELECT modificationDate FROM documents WHERE path LIKE ? ORDER BY modificationDate DESC LIMIT 0,1", [$path . "%"]);
    }
}
