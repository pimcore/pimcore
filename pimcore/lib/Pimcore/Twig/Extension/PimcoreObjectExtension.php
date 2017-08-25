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
use Pimcore\Model\Document;
use Pimcore\Model\Object;

class PimcoreObjectExtension extends \Twig_Extension
{
    public function getFunctions()
    {
        // simple object access functions in case documents/assets/objects need to be loaded directly in the template
        return [
            new \Twig_Function('pimcore_document', [Document::class, 'getById']),
            new \Twig_Function('pimcore_asset', [Asset::class, 'getById']),
            new \Twig_Function('pimcore_object', [Object\AbstractObject::class, 'getById']),
            new \Twig_Function('pimcore_document_wrap_hardlink', [Document\Hardlink\Service::class, 'wrap']),
        ];
    }
}
