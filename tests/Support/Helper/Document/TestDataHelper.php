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

namespace Pimcore\Tests\Support\Helper\Document;

use Carbon\Carbon;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Document;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Document\Editable\Areablock;
use Pimcore\Model\Document\Editable\Block;
use Pimcore\Model\Document\Editable\Checkbox;
use Pimcore\Model\Document\Editable\Date;
use Pimcore\Model\Document\Editable\Embed;
use Pimcore\Model\Document\Editable\Image;
use Pimcore\Model\Document\Editable\Input;
use Pimcore\Model\Document\Editable\Link;
use Pimcore\Model\Document\Editable\Multiselect;
use Pimcore\Model\Document\Editable\Numeric;
use Pimcore\Model\Document\Editable\Pdf;
use Pimcore\Model\Document\Editable\Relation;
use Pimcore\Model\Document\Editable\Relations;
use Pimcore\Model\Document\Editable\Scheduledblock;
use Pimcore\Model\Document\Editable\Select;
use Pimcore\Model\Document\Editable\Table;
use Pimcore\Model\Document\Editable\Textarea;
use Pimcore\Model\Document\Editable\Video;
use Pimcore\Model\Document\Editable\Wysiwyg;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Tests\Support\Helper\AbstractTestDataHelper;
use Pimcore\Tests\Support\Util\TestHelper;

class TestDataHelper extends AbstractTestDataHelper
{
    public function assertAreablock(PageSnippet $pagesnippet, string $field, int $seed = 1): void
    {
        /** @var Areablock $editable */
        $editable = $pagesnippet->getEditable($field);
        $this->assertInstanceOf(Areablock::class, $editable);
        $value = $editable->getValue();

        $expected = $this->createAreablockData($seed);

        $this->assertEquals($expected, $value);
    }

    public function createAreablockData(int $seed = 1): array
    {
        return [
            [
                'key' => 4,
                'type' => 'standard-teaser',
                'hidden' => false,
            ],
            [
                'key' => 1,
                'type' => 'wysiwyg',
                'hidden' => true,
            ],
        ];
    }

    public function assertCheckbox(PageSnippet $pagesnippet, string $field, int $seed = 1): void
    {
        /** @var Checkbox $editable */
        $editable = $pagesnippet->getEditable($field);
        $this->assertInstanceOf(Checkbox::class, $editable);
        $value = $editable->getValue();
        $expected = ($seed % 2) == true;

        $this->assertEquals($expected, $value);
    }

    public function assertDate(PageSnippet $pagesnippet, string $field, int $seed = 1): void
    {
        /** @var Date $editable */
        $editable = $pagesnippet->getEditable($field);
        $this->assertInstanceOf(Date::class, $editable);
        $value = $editable->getValue();
        $this->assertInstanceOf(Carbon::class, $value);
        $expected = strtotime('2021-02-1' . ($seed % 10));

        $this->assertEquals($expected, $value->getTimestamp());
    }

    public function assertEmbed(Page $page, string $field, int $seed = 1): void
    {
        /** @var Embed $editable */
        $editable = $page->getEditable($field);
        $this->assertInstanceOf(Embed::class, $editable);
        $value = $editable->getUrl();

        $this->assertEquals('http://someurl' . $seed, $value);
    }

    public function assertImage(PageSnippet $pagesnippet, string $field, int $seed = 1, array $params = []): void
    {
        /** @var Image $editable */
        $editable = $pagesnippet->getEditable($field);
        $this->assertInstanceOf(Image::class, $editable);
        $value = $editable->getImage();
        $this->assertInstanceOf(\Pimcore\Model\Asset\Image::class, $value);

        $expectedImage = $params['asset'];
        $this->assertEquals($expectedImage->getId(), $value->getId());
    }

    public function assertInput(PageSnippet $pagesnippet, string $field, int $seed = 1): void
    {
        /** @var Input $editable */
        $editable = $pagesnippet->getEditable($field);
        $this->assertInstanceOf(Input::class, $editable);
        $value = $editable->getValue();

        $this->assertEquals('content' . $seed, $value);
    }

    public function assertLink(PageSnippet $pagesnippet, string $field, int $seed = 1, array $params = []): void
    {
        /** @var Link $editable */
        $editable = $pagesnippet->getEditable($field);

        $this->assertInstanceOf(Link::class, $editable);
        $target = $editable->getTarget();

        /** @var Asset $expectedTarget */
        $expectedTarget = $params['target'];

        $this->assertEquals($expectedTarget->getFullPath(), $editable->getHref());

        $this->assertEquals('some title' . $seed, $editable->getTitle());
        $this->assertEquals('some text' . $seed, $editable->getText());
        $this->assertEquals('_blank', $editable->getTarget());
    }

    public function assertMultiselect(PageSnippet $pagesnippet, string $field, int $seed = 1): void
    {
        /** @var Select $editable */
        $editable = $pagesnippet->getEditable($field);
        $this->assertInstanceOf(Multiselect::class, $editable);

        $expected = ['1', '2'];

        $this->assertEquals($expected, $editable->getValue());
    }

    public function assertNumeric(PageSnippet $pagesnippet, string $field, int $seed = 1): void
    {
        /** @var Numeric $editable */
        $editable = $pagesnippet->getEditable($field);
        $this->assertInstanceOf(Numeric::class, $editable);
        $value = $editable->getValue();

        $this->assertEquals(123 + $seed, $value);
    }

    public function assertPdf(PageSnippet $pagesnippet, string $field, int $seed = 1, array $params = []): void
    {
        /** @var Pdf $editable */
        $editable = $pagesnippet->getEditable($field);
        $this->assertInstanceOf(Pdf::class, $editable);
        $value = $editable->getElement();
        $this->assertInstanceOf(Document::class, $value);

        $expectedPdf = $params['pdf'];
        $this->assertEquals($expectedPdf->getId(), $value->getId());
    }

    public function assertRelation(PageSnippet $pagesnippet, string $field, int $seed = 1, array $params = []): void
    {
        /** @var Relation $editable */
        $editable = $pagesnippet->getEditable($field);
        $this->assertInstanceOf(Relation::class, $editable);
        $value = $editable->getElement();

        $expectedTarget = $params['target'];

        $this->assertEquals($expectedTarget->getId(), $value->getId());
        $this->assertEquals($expectedTarget->getType(), $value->getType());
    }

    public function assertRelations(PageSnippet $pagesnippet, string $field, int $seed = 1, array $params = []): void
    {
        /** @var Relations $editable */
        $editable = $pagesnippet->getEditable($field);
        $this->assertInstanceOf(Relations::class, $editable);
        $value = $editable->getElements();

        $expectedTargets = $params['targets'];
        $this->assertCount(count($expectedTargets), $value);

        for ($i = 0; $i < count($expectedTargets); $i++) {
            $this->assertNotNull($value[$i]);
            $this->assertInstanceOf(AbstractObject::class, $value[$i]);
            $this->assertObjectsEqual($expectedTargets[$i], $value[$i]);
        }
    }

    public function assertScheduledblock(Page $page, string $field, int $seed = 1): void
    {
        /** @var Scheduledblock $editable */
        $editable = $page->getEditable($field);
        $this->assertInstanceOf(Scheduledblock::class, $editable);
        $value = $editable->getIndices();

        $expected = $this->createScheduledblockData($seed);

        $this->assertEquals($expected, $value);
    }

    public function createScheduledblockData(int $seed = 1): array
    {
        return [
            [
                'key' => 4,
                'date' => 1613383345 + $seed,
            ],
            [
                'key' => 1,
                'date' => 1613383345 + 6 + $seed,
            ],
        ];
    }

    public function assertSelect(PageSnippet $pagesnippet, string $field, int $seed = 1): void
    {
        /** @var Select $editable */
        $editable = $pagesnippet->getEditable($field);
        $this->assertInstanceOf(Select::class, $editable);

        $expected = 1 + ($seed % 2);

        $this->assertEquals($expected, $editable->getValue());
    }

    public function assertTable(PageSnippet $pagesnippet, string $field, int $seed = 1): void
    {
        /** @var Table $editable */
        $editable = $pagesnippet->getEditable($field);
        $this->assertInstanceOf(Table::class, $editable);
        $value = $editable->getValue();

        $this->assertEquals($this->createTableData($seed), $value);
    }

    public function createTableData(int $seed = 1): array
    {
        return [
            ['a' . $seed, 'b' . $seed, 'c' . $seed],
            [1 + $seed, 2 + $seed, 3 + $seed],
        ];
    }

    public function assertVideo(PageSnippet $pagesnippet, string $field, int $seed = 1, array $params = []): void
    {
        /** @var Video $editable */
        $editable = $pagesnippet->getEditable($field);
        $this->assertInstanceOf(Video::class, $editable);

        $video = $editable->getVideoAsset();
        $this->assertInstanceOf(\Pimcore\Model\Asset\Video::class, $video);

        $expectedVideo = $params['video'];
        $this->assertInstanceOf(\Pimcore\Model\Asset\Video::class, $expectedVideo);

        $this->assertEquals($expectedVideo->getId(), $video->getId());

        $poster = $editable->getPosterAsset();
        $expectedPoster = $params['poster'];
        $this->assertInstanceOf(\Pimcore\Model\Asset\Image::class, $poster);
        $this->assertInstanceOf(\Pimcore\Model\Asset\Image::class, $expectedPoster);

        $this->assertEquals($expectedPoster->getId(), $poster->getId());

        $this->assertEquals('some title ' . $seed, $editable->getTitle());
        $this->assertEquals('some description ' . $seed, $editable->getDescription());
    }

    public function assertWysiwyg(PageSnippet $pagesnippet, string $field, int $seed = 1): void
    {
        /** @var Wysiwyg $editable */
        $editable = $pagesnippet->getEditable($field);
        $this->assertInstanceOf(Wysiwyg::class, $editable);
        $value = $editable->getValue();

        $this->assertEquals('content<br />' . $seed, $value);
    }

    public function assertTextarea(PageSnippet $pagesnippet, string $field, int $seed = 1): void
    {
        /** @var Textarea $editable */
        $editable = $pagesnippet->getEditable($field);
        $this->assertInstanceOf(Textarea::class, $editable);
        $value = $editable->getValue();

        $this->assertEquals('content<br />' . $seed, $value);
    }

    public function assertBlock(PageSnippet $pagesnippet, string $field, int $seed = 1): void
    {
        /** @var Block $editable */
        $editable = $pagesnippet->getEditable($field);
        $this->assertInstanceOf(Block::class, $editable);
        $value = $editable->getValue();

        $expected = $this->createBlockData();

        //assert block indices data
        $this->assertEquals(array_keys($expected), $value);

        $blockElements = $editable->getElements();

        //assert editables at index 1
        /** @var Input $blockInput1 */
        $blockInput1 = $blockElements[0]->getEditable('input');
        $this->assertEquals($expected[1]['input'], $blockInput1->getValue());

        /** @var Image $blockImage1 */
        $blockImage1 = $blockElements[0]->getEditable('image');
        $this->assertInstanceOf(Asset\Image::class, $blockImage1->getImage());

        //assert editables at index 2
        /** @var Input $blockInput2 */
        $blockInput2 = $blockElements[1]->getEditable('input');
        $this->assertEquals($expected[2]['input'], $blockInput2->getValue());

        /** @var Image $blockImage2 */
        $blockImage2 = $blockElements[1]->getEditable('image');
        $this->assertInstanceOf(Asset\Image::class, $blockImage2->getImage());
    }

    public function createBlockData(?Page $page = null, ?string $blockName = null): array
    {
        $asset = TestHelper::createImageAsset('blockimage-');
        $blockIndices =  [
            '1' => [
                'input' => 'block text 1',
            ],
            '2' => [
                'input' => 'block text 2',
            ],
        ];

        if ($page && $blockName) {
            foreach ($blockIndices as $blockIdx => $blockVal) {
                //Add editable on each block index
                $input = new Input();
                $input->setName($blockName . ':' . $blockIdx . '.input');
                $input->setDataFromResource($blockVal['input']);
                $page->setEditable($input);

                $image = new Image();
                $image->setName($blockName . ':' . $blockIdx . '.image');
                $image->setDataFromEditmode(['id' => $asset->getId()]);
                $page->setEditable($image);
            }
        }

        return $blockIndices;
    }

    public function fillAreablock(Page $page, string $field, int $seed = 1): void
    {
        $editable = new Areablock();
        $editable->setName($field);
        $data = $this->createAreablockData($seed);
        $editable->setDataFromEditmode($data);
        $page->setEditable($editable);
    }

    public function fillCheckbox(Page $page, string $field, int $seed = 1): void
    {
        $editable = new Checkbox();
        $editable->setName($field);
        $editable->setDataFromResource(($seed % 2) == true);
        $page->setEditable($editable);
    }

    public function fillDate(Page $page, string $field, int $seed = 1): void
    {
        $editable = new Date();
        $editable->setName($field);
        $dateStr = '2021-02-1' . ($seed % 10);
        $editable->setDataFromEditmode($dateStr);
        $page->setEditable($editable);
    }

    public function fillEmbed(Page $page, string $field, int $seed = 1): void
    {
        $editable = new Embed();
        $editable->setName($field);
        $editable->setDataFromEditmode(['url' => 'http://someurl' . $seed]);
        $page->setEditable($editable);
    }

    public function fillImage(Page $page, string $field, int $seed = 1, ?array &$returnData): void
    {
        $asset = TestHelper::createImageAsset();
        $editable = new Image();
        $editable->setName($field);
        $editable->setDataFromEditmode(['id' => $asset->getId()]);
        $returnData = [
            'asset' => $asset,
        ];
        $page->setEditable($editable);
    }

    public function fillInput(Page $page, string $field, int $seed = 1): void
    {
        $editable = new Input();
        $editable->setName($field);
        $editable->setDataFromEditmode('content' . $seed);
        $page->setEditable($editable);
    }

    public function fillLink(Page $page, string $field, int $seed = 1, ?array &$returnData): void
    {
        $target = TestHelper::createImageAsset();
        $editable = new Link();
        $editable->setName($field);

        $editable->setDataFromEditmode([
                'internalType' => 'asset',
                'linktype' => 'internal',
                'path' => $target->getFullPath(),
                'text' => 'some text' . $seed,
                'title' => 'some title' . $seed,
                'target' => '_blank', ]
        );

        $page->setEditable($editable);

        $returnData = [
            'target' => $target,
        ];
    }

    public function fillMultiselect(Page $page, string $field, int $seed = 1): void
    {
        $editable = new Multiselect();
        $editable->setName($field);
        $editable->setDataFromEditmode(['1', '2']);
        $page->setEditable($editable);
    }

    public function fillNumeric(Page $page, string $field, int $seed = 1): void
    {
        $editable = new Numeric();
        $editable->setName($field);
        $editable->setDataFromEditmode(123 + $seed);
        $page->setEditable($editable);
    }

    public function fillPdf(Page $page, string $field, int $seed = 1, ?array &$returnData): void
    {
        $pdf = TestHelper::createDocumentAsset();
        $editable = new Pdf();
        $editable->setName($field);
        $editable->setDataFromEditmode(['id' => $pdf->getId()]);
        $returnData = [
            'pdf' => $pdf,
        ];
        $page->setEditable($editable);
    }

    public function fillRelation(Page $page, string $field, int $seed = 1, ?array &$returnData): void
    {
        $editable = new Relation();
        $editable->setName($field);
        $objects = $this->getObjectList();

        $editable->setDataFromEditmode([
                'id' => $objects[0]->getId(),
                'type' => 'object', ]

        );
        $page->setEditable($editable);

        $returnData = [
            'target' => $objects[0],
        ];
    }

    public function fillRelations(Page $page, string $field, int $seed = 1, ?array &$returnData): void
    {
        $editable = new Relations();
        $editable->setName($field);
        $objects = $this->getObjectList();

        $objects = array_slice($objects, 0, 4);
        $list = [];
        foreach ($objects as $object) {
            $list[] = [
                'id' => $object->getId(),
                'type' => 'object', ];
        }
        $editable->setDataFromEditmode($list);
        $page->setEditable($editable);

        $returnData = [
            'targets' => $objects,
        ];
    }

    public function fillScheduledblock(Page $page, string $field, int $seed = 1): void
    {
        $editable = new Scheduledblock();
        $editable->setName($field);
        $data = $this->createScheduledblockData($seed);
        $editable->setDataFromEditmode($data);
        $page->setEditable($editable);
    }

    public function fillSelect(Page $page, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);

        $editable = new Select();
        $editable->setName($field);
        $editable->setDataFromEditmode(1 + ($seed % 2));
        $page->setEditable($editable);
    }

    public function fillTable(Page $page, string $field, int $seed = 1): void
    {
        $editable = new Table();
        $editable->setName($field);
        $editable->setDataFromEditmode($this->createTableData($seed));
        $page->setEditable($editable);
    }

    public function fillVideo(Page $page, string $field, int $seed = 1, ?array &$returnData): void
    {
        $video = TestHelper::createVideoAsset();
        $poster = TestHelper::createImageAsset();
        $editable = new Video();
        $editable->setName($field);
        $editable->setDataFromEditmode(
            ['id' => $video->getId(),
                'path' => $video->getFullPath(),
                'title' => 'some title ' . $seed,
                'description' => 'some description ' . $seed,
                'poster' => $poster->getFullPath(),
                'type' => 'asset',
            ]);

        $returnData = [
            'video' => $video,
            'poster' => $poster,
        ];
        $page->setEditable($editable);
    }

    public function fillWysiwyg(Page $page, string $field, int $seed = 1): void
    {
        $editable = new Wysiwyg();
        $editable->setName($field);
        $editable->setDataFromEditmode('content<br />' . $seed);
        $page->setEditable($editable);
    }

    public function fillTextarea(Page $page, string $field, int $seed = 1): void
    {
        $editable = new Textarea();
        $editable->setName($field);
        $editable->setDataFromEditmode('content<br />' . $seed);
        $page->setEditable($editable);
    }

    public function fillBlock(Page $page, string $field, int $seed = 1): void
    {
        $editable = new Block();
        $editable->setName($field);
        $data = $this->createBlockData($page, $field);
        $editable->setDataFromEditmode(array_keys($data));
        $page->setEditable($editable);
    }
}
