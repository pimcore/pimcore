<?php

declare(strict_types = 1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document\Tag\Loader;

use Pimcore\Loader\ImplementationLoader\ImplementationLoader;
use Pimcore\Model\Document\Tag;

class TagLoader extends ImplementationLoader implements TagLoaderInterface
{
    /**
     * @inheritDoc
     */
    public function build(string $name, array $params = []): Tag
    {
        return parent::build($name, $params);
    }
}
