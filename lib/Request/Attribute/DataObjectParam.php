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

namespace Pimcore\Request\Attribute;

use Attribute;

/**
 * Argument to resolve a DataObject.
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class DataObjectParam
{
    public function __construct(
        public ?string $class = null,
        public ?bool $unpublished = null,
        public ?array $options = null
    ) {
    }
}
