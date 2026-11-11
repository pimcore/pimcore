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

namespace Pimcore\DataObject\BlockDataMarshaller;

use Pimcore\Marshaller\MarshallerInterface;

/**
 * @internal
 *
 * An image gallery's normalized form is a list of normalized hotspot images, so each item goes
 * through the hotspotimage block marshaller.
 */
class ImageGallery implements MarshallerInterface
{
    public function __construct(private readonly Hotspotimage $hotspotimageMarshaller)
    {
    }

    public function marshal(mixed $value, array $params = []): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        foreach ($value as &$item) {
            $item = $this->hotspotimageMarshaller->marshal($item, $params);
        }

        return $value;
    }

    public function unmarshal(mixed $value, array $params = []): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        foreach ($value as &$item) {
            $item = $this->hotspotimageMarshaller->unmarshal($item, $params);
        }

        return $value;
    }
}
