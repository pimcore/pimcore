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

namespace Pimcore\Model\Element\Tag;

use Doctrine\DBAL\ArrayParameterType;
use Pimcore\Db;

/**
 * Answers "does this tag have children?" for all tags of one Tag\Listing result with a single query,
 * instead of one COUNT query per tag. The query only runs when the first answer is asked for.
 *
 * Nothing is cached beyond the lifetime of the listing result: the lookup is only referenced by the tags that were
 * loaded together. A tag saved or deleted in this process invalidates every lookup built before (the caller then
 * falls back to its own query).
 *
 * @internal
 */
final class ChildrenLookup
{
    private const CHUNK_SIZE = 1000;

    /**
     * Incremented whenever a tag is saved or deleted in this process.
     */
    private static int $generation = 0;

    /**
     * @var array<int, true>|null ids of the tags (of the group) that have children, null until the query ran
     */
    private ?array $parentIds = null;

    private int $builtAtGeneration = 0;

    /**
     * @param int[] $tagIds ids of all tags that were loaded together
     */
    public function __construct(private readonly array $tagIds)
    {
    }

    public static function markChanged(): void
    {
        self::$generation++;
    }

    /**
     * @return bool|null null if a tag changed since the lookup was built, so the answer might be outdated
     */
    public function hasChildren(int $tagId): ?bool
    {
        if ($this->parentIds === null) {
            $this->build();
        } elseif ($this->builtAtGeneration !== self::$generation) {
            return null;
        }

        return isset($this->parentIds[$tagId]);
    }

    private function build(): void
    {
        $this->builtAtGeneration = self::$generation;
        $this->parentIds = [];

        $db = Db::get();
        foreach (array_chunk($this->tagIds, self::CHUNK_SIZE) as $chunk) {
            $parentIds = $db->fetchFirstColumn(
                'SELECT DISTINCT parentId FROM tags WHERE parentId IN (?)',
                [$chunk],
                [ArrayParameterType::INTEGER]
            );

            foreach ($parentIds as $parentId) {
                $this->parentIds[(int) $parentId] = true;
            }
        }
    }
}
