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

namespace Pimcore\Cdn;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('pimcore.cdn.image_transform_adapter', ['optimizer' => 'null'])]
class NullImageTransformAdapter implements ImageTransformAdapterInterface
{
    public function buildUrl(string $originalPath, ThumbnailTransform $transform): string
    {
        return $originalPath;
    }
}
