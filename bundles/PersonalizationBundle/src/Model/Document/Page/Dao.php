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

namespace Pimcore\Bundle\PersonalizationBundle\Model\Document\Page;

use Pimcore\Bundle\PersonalizationBundle\Model\Document\Targeting\TargetingDocumentDaoInterface;
use Pimcore\Bundle\PersonalizationBundle\Model\Document\Targeting\TargetingDocumentDaoTrait;
use Pimcore\Model;

/**
 * @internal
 *
 * @property \Pimcore\Bundle\PersonalizationBundle\Model\Document\Page $model
 */
class Dao extends Model\Document\Page\Dao implements TargetingDocumentDaoInterface
{
    use TargetingDocumentDaoTrait;
}
