<?php

namespace Pimcore\Tests\Model\Document;

use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document\Editable\Input;
use Pimcore\Model\Document\Email;
use Pimcore\Model\Document\Link;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Document\Service;
use Pimcore\Tests\Helper\Document\TestDataHelper;
use Pimcore\Tests\Test\ModelTestCase;
use Pimcore\Tests\Util\TestHelper;

/**
 * Class ElementTest
 *
 * @package Pimcore\Tests\Model\Document
 * @group model.document.document
 */
class EditableTest extends ModelTestCase
{

    /**
     * @var int
     */
    protected $seed = 1;

    /** @var Page */
    protected $testPage;

    /** @var TestDataHelper */
    protected $testDataHelper;

    /**
     * @param TestDataHelper $testData
     */
    public function _inject(TestDataHelper $testData)
    {
        $this->testDataHelper = $testData;
    }

    public function testAreablock()
    {
        $this->createTestPage('areablock');

        $this->reloadPage();
        $this->testDataHelper->assertAreablock($this->testPage, 'areablock', $this->seed);
    }

    protected function createTestPage($fields = [], &$returnData = [])
    {
        $this->testPage = TestHelper::createEmptyDocumentPage();
        $this->assertInstanceOf(Page::class, $this->testPage);
        if ($fields) {
            $this->fillPage($this->testPage, $fields, $returnData);
        }

        $this->testPage->save();
        return $this->testPage;
    }

    /**
     * Calls fill* methods on the object as needed in test
     *
     * @param Concrete     $object
     * @param array|string $fields
     * @param array $returnData
     */
    protected function fillPage(Page $document, $fields = [], &$returnData = [])
    {
        // allow to pass only a string (e.g. input) -> fillInput($object, "input", $seed)
        if (!is_array($fields)) {
            $fields = [
                [
                    'method' => 'fill' . ucfirst($fields),
                    'field' => $fields,
                ],
            ];
        }

        if (!is_array($fields)) {
            throw new \InvalidArgumentException('Fields needs to be an array');
        }

        foreach ($fields as $field) {
            $method = $field['method'];

            if (!$method) {
                throw new \InvalidArgumentException(sprintf('Need a method to call'));
            }

            if (!method_exists($this->testDataHelper, $method)) {
                throw new \InvalidArgumentException(sprintf('Method %s does not exist', $method));
            }

            $methodArguments = [$document, $field['field'], $this->seed];

            $additionalArguments = isset($field['arguments']) ? $field['arguments'] : [];
            foreach ($additionalArguments as $aa) {
                $methodArguments[] = $aa;
            }

            $methodArguments[] = &$returnData;

            call_user_func_array([$this->testDataHelper, $method], $methodArguments);
        }
    }

    public function reloadPage() {
        $this->testPage = Page::getById($this->testPage->getId(), true);
    }

    public function testCheckbox()
    {
        $this->createTestPage('checkbox');

        $this->reloadPage();
        $this->testDataHelper->assertCheckbox($this->testPage, 'checkbox', $this->seed);
    }

    public function testDate()
    {
        $this->createTestPage('date');

        $this->reloadPage();
        $this->testDataHelper->assertDate($this->testPage, 'date', $this->seed);
    }

    public function testImage()
    {
        $this->createTestPage('image', $returnData);

        $this->reloadPage();
        $this->testDataHelper->assertImage($this->testPage, 'image', $this->seed, $returnData);
    }

    public function testImagex()
    {
        $this->createTestPage('checkbox');

        $this->reloadPage();
        $this->testDataHelper->assertCheckbox($this->testPage, 'checkbox', $this->seed);
    }


}
