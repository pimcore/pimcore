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

namespace Pimcore\Bundle\SeoBundle\Maintenance;

use Pimcore\Bundle\SeoBundle\Model\Redirect;
use Pimcore\Maintenance\TaskInterface;

/**
 * @internal
 */
class RedirectCleanupTask implements TaskInterface
{
    public function execute(): void
    {
        $list = new Redirect\Listing();
        $list->setCondition('active = 1 AND expiry < '.time()." AND expiry IS NOT NULL AND expiry != ''");
        $list->load();

        foreach ($list->getRedirects() as $redirect) {
            $redirect->setActive(false);
            $redirect->save();
        }
    }
}
