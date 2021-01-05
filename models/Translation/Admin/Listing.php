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
 * @package    Translation
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Translation\Admin;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Translation\Admin\Listing\Dao getDao()
 *
 * @deprecated
 */
class Listing extends Model\Translation\AbstractTranslation\Listing
{
    /**
     * @var string
     */
    protected $domain = Model\Translation\Translation::DOMAIN_ADMIN;
}
