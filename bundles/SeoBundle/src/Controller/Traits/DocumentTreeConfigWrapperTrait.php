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

namespace Pimcore\Bundle\SeoBundle\Controller\Traits;

use Pimcore\Bundle\AdminBundle\Controller\Traits\DocumentTreeConfigTrait;
use Pimcore\Model\Element\ElementInterface;

if (trait_exists(DocumentTreeConfigTrait::class)) {
    /**
     * @internal
     */
    trait DocumentTreeConfigWrapperTrait
    {
        use DocumentTreeConfigTrait;
    }
} else {
    /**
     * @internal
     */
    trait DocumentTreeConfigWrapperTrait
    {
        public function getTreeNodeConfig(ElementInterface $element): array
        {
            return [];
        }
    }
}
