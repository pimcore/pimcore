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

namespace Pimcore\Translation\ImportDataExtractor\TranslationItemResolver;

use Pimcore\Model\Element;
use Pimcore\Translation\TranslationItemCollection\TranslationItem;

class TranslationItemResolver implements TranslationItemResolverInterface
{
    public function resolve(string $type, string $id): ?TranslationItem
    {
        if (!$element = Element\Service::getElementById($type, $id)) {
            return null;
        }

        return new TranslationItem($type, $id, $element);
    }
}
