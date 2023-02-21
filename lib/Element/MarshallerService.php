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
