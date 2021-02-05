<?php

namespace Pimcore\Tests\Helper\Document;

use Codeception\Module;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document\Editable\Checkbox;
use Pimcore\Model\Document\Page;
use Pimcore\Tests\Helper\AbstractTestDataHelper;

class TestDataHelper extends AbstractTestDataHelper
{

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
     * @param Page $object
     * @param string $field
     * @param int $seed
     */
    public function assertCheckbox(Page $page, $field, $seed = 1)
    {
        /** @var Checkbox $value */
        $value = $page->getEditable($field)->getValue();
        $expected = ($seed % 2) == true;

        $this->assertEquals($expected, $value);
    }

}
