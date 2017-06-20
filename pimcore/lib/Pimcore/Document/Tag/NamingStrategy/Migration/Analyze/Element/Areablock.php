<?php

declare(strict_types=1);

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

namespace Pimcore\Document\Tag\NamingStrategy\Migration\Analyze\Element;

final class Areablock extends AbstractBlock
{
    /**
     * @inheritdoc
     */
    protected function resolveChildIndexes(array $data): array
    {
        // areablock indexes are rows with key and type
        $indexes = [];
        foreach ($data as $item) {
            $indexes[] = (int)$item['key'];
        }

        return $indexes;
    }
}
