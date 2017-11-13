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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ImportResolver;

use Pimcore\Model\DataObject\Concrete;

class Id
{
    protected $config;

    protected $idIdx;

    /**
     * Id constructor.
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->idIdx = $this->config->resolverSettings->column;
    }

    public function resolve($parentId, $rowData)
    {
        if (!is_null($this->idIdx)) {
            $id = $rowData[$this->idIdx];

            return Concrete::getById($id);
        }
    }
}
