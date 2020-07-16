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

namespace Pimcore\Twig\Extension;

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PimcoreObjectExtension extends AbstractExtension
{
    public function getFunctions()
    {
        // simple object access functions in case documents/assets/objects need to be loaded directly in the template
        return [
            new TwigFunction('pimcore_document', [Document::class, 'getById']),
            new TwigFunction('pimcore_site', [Site::class, 'getById']),
            new TwigFunction('pimcore_site_by_root_id', [Site::class, 'getByRootId']),
            new TwigFunction('pimcore_site_by_domain', [Site::class, 'getByDomain']),
            new TwigFunction('pimcore_site_is_request', [Site::class, 'isSiteRequest']),
            new TwigFunction('pimcore_site_current', [Site::class, 'getCurrentSite']),
            new TwigFunction('pimcore_asset', [Asset::class, 'getById']),
            new TwigFunction('pimcore_object', [DataObject\AbstractObject::class, 'getById']),
            new TwigFunction('pimcore_document_wrap_hardlink', [Document\Hardlink\Service::class, 'wrap']),
        ];
    }
}
