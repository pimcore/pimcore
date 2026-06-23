<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
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
