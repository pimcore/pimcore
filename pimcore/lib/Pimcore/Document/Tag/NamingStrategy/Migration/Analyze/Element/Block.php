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

final class Block extends AbstractBlock
{
    /**
     * @inheritdoc
     */
    protected function resolveChildIndexes($data = null): array
    {
        if (empty($data)) {
            return [];
        }

        $data = unserialize($data);
        if (!$data) {
            return [];
        }

        // block indexes is just a plain array of indexes
        return array_map(function ($index) {
            return (int)$index;
        }, array_values($data));
    }
}
