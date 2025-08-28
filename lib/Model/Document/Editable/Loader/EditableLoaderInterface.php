<?php

declare(strict_types = 1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\Document\Editable\Loader;

use Pimcore\Loader\ImplementationLoader\LoaderInterface;
use Pimcore\Model\Document\Editable;

interface EditableLoaderInterface extends LoaderInterface
{
    /**
     * Builds a tag instance
     *
     *
     */
    public function build(string $name, array $params = []): Editable;
}
