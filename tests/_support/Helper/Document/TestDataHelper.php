<?php

namespace Pimcore\Tests\Helper\Document;

use Carbon\Carbon;
use Pimcore\Model\Document\Editable\Areablock;
use Pimcore\Model\Document\Editable\Checkbox;
use Pimcore\Model\Document\Editable\Date;
use Pimcore\Model\Document\Editable\Image;
use Pimcore\Model\Document\Page;
use Pimcore\Tests\Helper\AbstractTestDataHelper;
use Pimcore\Tests\Util\TestHelper;

class TestDataHelper extends AbstractTestDataHelper
{

    /**
     * @param Page $object
     * @param string $field
     * @param int $seed
     */
    public function assertAreablock(Page $page, $field, $seed = 1)
    {
        /** @var Areablock $editable */
        $editable = $page->getEditable($field);
        $this->assertInstanceOf(Areablock::class, $editable);
        $value = $editable->getValue();

        $expected = $this->createAreablockData($seed);

        $this->assertEquals($expected, $value);
    }

    /**
     * @param int $seed
     * @return array[]
     */
    public function createAreablockData($seed = 1)
    {
        return [
            [
            "key" => 4,
            "type" => "standard-teaser",
            "hidden" => false
            ],
            [
                "key" => 1,
                "type" => "wysiwyg",
                "hidden" => true
            ]
        ];
    }

    /**
     * @param Page $object
     * @param string $field
     * @param int $seed
     */
    public function assertCheckbox(Page $page, $field, $seed = 1)
    {
        /** @var Checkbox $editable */
        $editable = $page->getEditable($field);
        $this->assertInstanceOf(Checkbox::class, $editable);
        $value = $editable->getValue();
        $expected = ($seed % 2) == true;

        $this->assertEquals($expected, $value);
    }

    /**
     * @param Page $object
     * @param string $field
     * @param int $seed
     */
    public function assertDate(Page $page, $field, $seed = 1)
    {
        /** @var Date $editable */
        $editable = $page->getEditable($field);
        $this->assertInstanceOf(Date::class, $editable);
        $value = $editable->getValue();
        $this->assertInstanceOf(Carbon::class, $value);
        $expected = strtotime("2021-02-1" . ($seed % 10));

        $this->assertEquals($expected, $value->getTimestamp());
    }

    /**
     * @param Page $object
     * @param string $field
     * @param int $seed
     * @param array $params
     */
    public function assertImage(Page $page, $field, $seed = 1, $params = [])
    {
        /** @var Image $editable */
        $editable = $page->getEditable($field);
        $this->assertInstanceOf(Image::class, $editable);
        $value = $editable->getImage();
        $this->assertInstanceOf(\Pimcore\Model\Asset\Image::class, $value);

        $expectedImage = $params["asset"];
        $this->assertEquals($expectedImage->getId(), $value->getId());
    }

    /**
     * @param Page $page
     * @param string $field
     * @param int $seed
     */
    public function fillAreablock(Page $page, $field, $seed = 1)
    {
        $editable = new Areablock();
        $editable->setName($field);
        $data = $this->createAreablockData($seed);
        $editable->setDataFromEditmode($data);
        $page->setEditable($editable);
    }

    /**
     * @param Page $page
     * @param string $field
     * @param int $seed
     */
    public function fillCheckbox(Page $page, $field, $seed = 1)
    {
        $editable = new Checkbox();
        $editable->setName($field);
        $editable->setDataFromResource(($seed % 2) == true);
        $page->setEditable($editable);
    }

    /**
     * @param Page $page
     * @param string $field
     * @param int $seed
     */
    public function fillDate(Page $page, $field, $seed = 1)
    {
        $editable = new Date();
        $editable->setName($field);
        $dateStr = "2021-02-1" . ($seed % 10);
        $editable->setDataFromEditmode($dateStr);
        $page->setEditable($editable);
    }

    /**
     * @param Page $page
     * @param string $field
     * @param int $seed
     * @param array $returnData
     */
    public function fillImage(Page $page, $field, $seed = 1, &$returnData)
    {
        $asset = TestHelper::createImageAsset();
        $editable = new Image();
        $editable->setName($field);
        $editable->setDataFromEditmode(["id" => $asset->getId()]);
        $returnData = [
            "asset" => $asset
        ];
        $page->setEditable($editable);
    }



}
