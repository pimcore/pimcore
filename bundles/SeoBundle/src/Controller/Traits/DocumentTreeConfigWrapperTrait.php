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

use Pimcore\Bundle\AdminBundle\Service\ElementService;
use Pimcore\Model\Element\ElementInterface;


/**
 * @internal
 */
trait DocumentTreeConfigWrapperTrait
{
    public function __construct(
        private ElementService $elementService
    ) {
    }

    public function getTreeNodeConfig(ElementInterface $element): array
    {
        if(class_exists(ElementService::class)) {
            return $this->elementService->getElementTreeNodeConfig($element);
        }
        return [];
    }

}
