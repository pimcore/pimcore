<?php

declare(strict_types=1);

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

namespace Pimcore\Model\Document\Traits;

use Pimcore\Model\Document;
use Pimcore\Model\Redirect;
use Pimcore\Tool\Frontend;

trait RedirectHelperTrait
{
    protected function createRedirectForFormerPath(string $oldPath, ?Document $oldDocument)
    {
        $config = \Pimcore\Config::getSystemConfig();
        if ($oldPath && $config->documents->createredirectwhenmoved && $oldPath != $this->getRealFullPath()) {
            // create redirect for old path
            $redirect = new Redirect();
            $redirect->setType(Redirect::TYPE_PATH);
            $redirect->setRegex(true);
            $redirect->setTarget($this->getId());
            $redirect->setSource('@^' . $oldPath . '/?$@');
            $redirect->setStatusCode(301);
            $redirect->setExpiry(time() + 86400 * 60); // this entry is removed automatically after 60 days

            //set source site
            if ($oldDocument) {
                $oldSite = Frontend::getSiteForDocument($oldDocument);
                if ($oldSite) {
                    $redirect->setSourceSite($oldSite->getId());
                    $oldPath = preg_replace('@^' . preg_quote($oldSite->getRootPath(), '@') . '@', '', $oldPath);
                    $redirect->setSource('@^' . $oldPath . '/?$@');
                }
            }

            //set target site
            $newSite = Frontend::getSiteForDocument($this);
            if ($newSite) {
                $redirect->setTargetSite($newSite->getId());
            }

            $redirect->save();
        }
    }
}
