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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Maintenance\Tasks;

use Pimcore\Maintenance\TaskInterface;
use Pimcore\Model\Redirect;

final class RedirectCleanupTask implements TaskInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute()
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
