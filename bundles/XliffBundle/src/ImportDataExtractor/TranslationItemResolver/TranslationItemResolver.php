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

namespace Pimcore\Bundle\XliffBundle\ImportDataExtractor\TranslationItemResolver;

use Pimcore\Bundle\XliffBundle\TranslationItemCollection\TranslationItem;
use Pimcore\Model\Element;

class TranslationItemResolver implements TranslationItemResolverInterface
{
    public function resolve(string $type, string $id): ?TranslationItem
    {
        if (!$element = Element\Service::getElementById($type, (int) $id)) {
            return null;
        }

        return new TranslationItem($type, $id, $element);
    }
}
