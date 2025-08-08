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

namespace Pimcore\Element;

use Pimcore\Marshaller\MarshallerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class MarshallerService
{
    private ServiceLocator $marshallerLocator;

    public function __construct(ServiceLocator $marshallerLocator)
    {
        $this->marshallerLocator = $marshallerLocator;
    }

    public function buildFieldefinitionMarshaller(string $format, string $name): MarshallerInterface
    {
        return $this->marshallerLocator->get($format . '_' . $name);
    }

    public function supportsFielddefinition(string $format, string $name): bool
    {
        return $this->marshallerLocator->has($format . '_' . $name);
    }
}
