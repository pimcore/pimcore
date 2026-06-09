<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
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
