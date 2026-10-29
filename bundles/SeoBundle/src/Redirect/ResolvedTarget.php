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

namespace Pimcore\Bundle\SeoBundle\Redirect;

use Pimcore\Model\Element\ElementType;

/**
 * An element a redirect target points to: the ID stored in the redirect and the type it
 * was resolved as.
 */
final readonly class ResolvedTarget
{
    public function __construct(
        public int $id,
        public ElementType $type,
    ) {
    }

    /**
     * The ID in the form the redirect stores it.
     */
    public function getTargetValue(): string
    {
        return (string) $this->id;
    }
}
