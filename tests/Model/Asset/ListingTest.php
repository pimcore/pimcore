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

namespace Pimcore\Tests\Model\Asset;

use Doctrine\DBAL\Query\QueryBuilder;
use Pimcore\Db;
use Pimcore\Model\Asset;
use Pimcore\Model\Element\Tag;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Class ListingTest
 *
 * @package Pimcore\Tests\Model\Document
 *
 * @group model.asset.asset
 */
class ListingTest extends ModelTestCase
{
    public function testListCount(): void
    {
        $db = Db::get();

        $count = $db->fetchOne('SELECT count(*) from assets');
        $this->assertEquals(1, $count, 'expected 1 asset');

        $tagA = TestHelper::createTag('A');
        $tagB = TestHelper::createTag('B');

        $image1 = TestHelper::createImageAsset();
        TestHelper::assignTag($tagA, $image1);
        TestHelper::assignTag($tagB, $image1);

        $image2 = TestHelper::createImageAsset();

        $image3 = TestHelper::createImageAsset();
        TestHelper::assignTag($tagB, $image3);

        $document = TestHelper::createDocumentAsset();
        TestHelper::assignTag($tagA, $document);
        TestHelper::assignTag($tagB, $document);

        $video = TestHelper::createVideoAsset();
        TestHelper::assignTag($tagB, $video);

        $count = $db->fetchOne('SELECT count(*) from assets');
        $this->assertEquals(6, $count, 'expected 6 assets');

        $list = new Asset\Listing();
        $totalCount = $list->getTotalCount();
        $this->assertEquals(6, $totalCount, 'expected 6 assets');

        $list = new Asset\Listing();
        $list->setLimit(3);
        $list->setOffset(1);
        $count = $list->getCount();
        $this->assertEquals(3, $count, 'expected 3 assets');

        $list = new Asset\Listing();
        $list->setLimit(10);
        $list->setOffset(1);
        $count = $list->getCount();
        $this->assertEquals(5, $count, 'expected 5 assets');

        $list = new Asset\Listing();
        $list->setLimit(10);
        $list->setOffset(1);
        $list->load();                      // with load
        $count = $list->getCount();
        $this->assertEquals(5, $count, 'expected 5 assets');
        $totalCount = $list->getTotalCount();
        $this->assertEquals(6, $totalCount, 'expected 6 assets');

        // Test: on a grouped query, the ->getTotalCount() should correctly count the number of rows
        $list = new Asset\Listing();
        $list->getDao()->onCreateQueryBuilder(function (QueryBuilder $queryBuilder) use ($tagA, $tagB) {
            $this->joinTags($queryBuilder, $tagA, $tagB);
        });

        $totalCount = $list->getTotalCount();
        $this->assertEquals(4, $totalCount, 'expected 4 assets on totalCount of grouped query');
        $list->load();
        $count = $list->getCount();
        $this->assertEquals(4, $count, 'expected 4 assets on grouped query');

        // Test: on a grouped limited query, the ->getTotalCount() should correctly count the number of total rows
        $list = new Asset\Listing();
        $list->getDao()->onCreateQueryBuilder(function (QueryBuilder $queryBuilder) use ($tagA, $tagB) {
            $this->joinTags($queryBuilder, $tagA, $tagB);
        });
        $list->setLimit(3);

        $totalCount = $list->getTotalCount();
        $this->assertEquals(4, $totalCount, 'expected 4 assets on totalCount of grouped limited query');
        $list->load();
        $count = $list->getCount();
        $this->assertEquals(3, $count, 'expected 3 assets on grouped limited query');
    }

    private function joinTags(QueryBuilder $queryBuilder, Tag ...$tags): void
    {
        $expressionBuilder = $queryBuilder->expr();
        $tagIds = array_map(fn (Tag $tag) => $expressionBuilder->literal((string)$tag->getId()), $tags);

        // Require assets to have one of the tags
        $queryBuilder
            ->innerJoin(
                'assets',
                'tags_assignment',
                'ta',
                (string) $expressionBuilder->and(
                    $expressionBuilder->in('ta.tagid', $tagIds),
                    $expressionBuilder->eq('ta.ctype', $expressionBuilder->literal('asset')),
                    $expressionBuilder->eq('ta.cid', 'assets.id')
                )
            )
            ->groupBy('assets.id')
        ;
    }
}
