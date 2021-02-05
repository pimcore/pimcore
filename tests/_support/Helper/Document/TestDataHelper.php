<?php

namespace Pimcore\Tests\Helper\Document;

use Carbon\Carbon;
use Pimcore\Model\Document\Editable\Areablock;
use Pimcore\Model\Document\Editable\Checkbox;
use Pimcore\Model\Document\Editable\Date;
use Pimcore\Model\Document\Editable\Image;
use Pimcore\Model\Document\Editable\Input;
use Pimcore\Model\Document\Editable\Multiselect;
use Pimcore\Model\Document\Editable\Numeric;
use Pimcore\Model\Document\Editable\Select;
use Pimcore\Model\Document\Editable\Table;
use Pimcore\Model\Document\Editable\Textarea;
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
     * @param Page $object
     * @param string $field
     * @param int $seed
     */
    public function assertInput(Page $page, $field, $seed = 1)
    {
        /** @var Input $editable */
        $editable = $page->getEditable($field);
        $this->assertInstanceOf(Input::class, $editable);
        $value = $editable->getValue();

        $this->assertEquals("content" . $seed, $value);
    }

    /**
     * @param Page $object
     * @param string $field
     * @param int $seed
     */
    public function assertMultiselect(Page $page, $field, $seed = 1)
    {
        /** @var Select $editable */
        $editable = $page->getEditable($field);
        $this->assertInstanceOf(Multiselect::class, $editable);

        $expected = ["1", "2"];

        $this->assertEquals($expected, $editable->getValue());
    }

    /**
     * @param Page $object
     * @param string $field
     * @param int $seed
     */
    public function assertNumeric(Page $page, $field, $seed = 1)
    {
        /** @var Numeric $editable */
        $editable = $page->getEditable($field);
        $this->assertInstanceOf(Numeric::class, $editable);
        $value = $editable->getValue();

        $this->assertEquals(123 + $seed, $value);
    }

    /**
     * @param Page $object
     * @param string $field
     * @param int $seed
     */
    public function assertSelect(Page $page, $field, $seed = 1)
    {
        /** @var Select $editable */
        $editable = $page->getEditable($field);
        $this->assertInstanceOf(Select::class, $editable);

        $expected = 1 + ($seed % 2);

        $this->assertEquals($expected, $editable->getValue());
    }

    /**
     * @param Page $object
     * @param string $field
     * @param int $seed
     */
    public function assertTable(Page $page, $field, $seed = 1)
    {
        /** @var Table $editable */
        $editable = $page->getEditable($field);
        $this->assertInstanceOf(Table::class, $editable);
        $value = $editable->getValue();

        $this->assertEquals($this->createTableData($seed), $value);
    }

    public function createTableData($seed = 1) {
        return [
            ['a' . $seed, 'b' . $seed, 'c'. $seed],
            [1 + $seed, 2 + $seed, 3 + $seed]
        ];
    }

    /**
     * @param Page $object
     * @param string $field
     * @param int $seed
     */
    public function assertWysiwyg(Page $page, $field, $seed = 1)
    {
        $this->assertTextarea($page, $field, $seed);
    }

    /**
     * @param Page $object
     * @param string $field
     * @param int $seed
     */
    public function assertTextarea(Page $page, $field, $seed = 1)
    {
        /** @var Textarea $editable */
        $editable = $page->getEditable($field);
        $this->assertInstanceOf(Textarea::class, $editable);
        $value = $editable->getValue();

        $this->assertEquals("content<br>" . $seed, $value);
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

    /**
     * @param Page $page
     * @param string $field
     * @param int $seed
     */
    public function fillInput(Page $page, $field, $seed = 1)
    {
        $editable = new Input();
        $editable->setName($field);
        $editable->setDataFromEditmode("content" . $seed);
        $page->setEditable($editable);
    }

    /**
     * @param Page $page
     * @param string $field
     * @param int $seed
     */
    public function fillMultiselect(Page $page, $field, $seed = 1)
    {
        $setter = 'set' . ucfirst($field);

        $editable = new Multiselect();
        $editable->setName($field);
        $editable->setDataFromEditmode(['1', '2']);
        $page->setEditable($editable);
    }

    /**
     * @param Page $page
     * @param string $field
     * @param int $seed
     */
    public function fillNumeric(Page $page, $field, $seed = 1)
    {
        $setter = 'set' . ucfirst($field);

        $editable = new Numeric();
        $editable->setName($field);
        $editable->setDataFromEditmode(123 + $seed);
        $page->setEditable($editable);
    }

    /**
     * @param Page $page
     * @param string $field
     * @param int $seed
     */
    public function fillSelect(Page $page, $field, $seed = 1)
    {
        $setter = 'set' . ucfirst($field);

        $editable = new Select();
        $editable->setName($field);
        $editable->setDataFromEditmode(1 + ($seed % 2));
        $page->setEditable($editable);
    }

    /**
     * @param Page $page
     * @param string $field
     * @param int $seed
     */
    public function fillTable(Page $page, $field, $seed = 1)
    {
        $editable = new Table();
        $editable->setName($field);
        $editable->setDataFromEditmode($this->createTableData($seed));
        $page->setEditable($editable);
    }

    /**
     * @param Page $page
     * @param string $field
     * @param int $seed
     */
    public function fillWysiwyg(Page $page, $field, $seed = 1)
    {
        $this->fillTextarea($page, $field, $seed);
    }

    /**
     * @param Page $page
     * @param string $field
     * @param int $seed
     */
    public function fillTextarea(Page $page, $field, $seed = 1)
    {
        $editable = new Textarea();
        $editable->setName($field);
        $editable->setDataFromEditmode("content<br>" . $seed);
        $page->setEditable($editable);
    }

}
