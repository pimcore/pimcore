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

namespace Pimcore\Model\Webservice\Data\Document;

use Pimcore\Model;

/**
 * @deprecated
 */
class Hardlink extends Model\Webservice\Data\Document
{
    /**
     * static type of this object
     *
     * @var string
     */
    public $type = 'hardlink';

    /**
     * @var int
     */
    public $sourceId;

    /**
     * @var bool
     */
    public $propertiesFromSource;

    /**
     * @var bool
     */
    public $childrenFromSource;
}
