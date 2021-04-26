<?php

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Tests\Model\Document;

use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document\Editable\Input;
use Pimcore\Model\Document\Page;
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

    public function testEmbed()
    {
        $this->createTestPage('embed');

        $this->reloadPage();
        $this->testDataHelper->assertEmbed($this->testPage, 'embed', $this->seed);
    }

    public function testImage()
    {
        $this->createTestPage('image', $returnData);

        $this->reloadPage();
        $this->testDataHelper->assertImage($this->testPage, 'image', $this->seed, $returnData);
    }

    public function testInput()
    {
        $this->createTestPage('input');

        $this->reloadPage();
        $this->testDataHelper->assertInput($this->testPage, 'input', $this->seed);
    }

    public function testLink()
    {
        TestHelper::createEmptyObjects();
        $this->createTestPage('link', $returnData);

        $this->reloadPage();
        $this->testDataHelper->assertLink($this->testPage, 'link', $this->seed, $returnData);
    }

    public function testMultiselect()
    {
        $this->createTestPage('multiselect');

        $this->reloadPage();
        $this->testDataHelper->assertMultiselect($this->testPage, 'multiselect', $this->seed);
    }

    public function testNumeric()
    {
        $this->createTestPage('numeric');

        $this->reloadPage();
        $this->testDataHelper->assertNumeric($this->testPage, 'numeric', $this->seed);
    }

    public function testPdf()
    {
        $this->createTestPage('pdf', $returnData);

        $this->reloadPage();
        $this->testDataHelper->assertPdf($this->testPage, 'pdf', $this->seed, $returnData);
    }

    public function testRelation()
    {
        TestHelper::createEmptyObjects();
        $this->createTestPage('relation', $returnData);

        $this->reloadPage();
        $this->testDataHelper->assertRelation($this->testPage, 'relation', $this->seed, $returnData);
    }

    public function testRelations()
    {
        TestHelper::createEmptyObjects();
        $this->createTestPage('relations', $returnData);

        $this->reloadPage();
        $this->testDataHelper->assertRelations($this->testPage, 'relations', $this->seed, $returnData);
    }

    public function testScheduledblock()
    {
        $this->createTestPage('scheduledblock');

        $this->reloadPage();
        $this->testDataHelper->assertScheduledblock($this->testPage, 'scheduledblock', $this->seed);
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
     * @param Concrete $object
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

    public function reloadPage()
    {
        $this->testPage = Page::getById($this->testPage->getId(), true);
    }

    public function testSelect()
    {
        $this->createTestPage('select');

        $this->reloadPage();
        $this->testDataHelper->assertSelect($this->testPage, 'select', $this->seed);
    }

    public function testTable()
    {
        $this->createTestPage('table');

        $this->reloadPage();
        $this->testDataHelper->assertTable($this->testPage, 'table', $this->seed);
    }

    public function testTextarea()
    {
        $this->createTestPage('textarea');

        $this->reloadPage();
        $this->testDataHelper->assertTextarea($this->testPage, 'textarea', $this->seed);
    }

    public function testVideo()
    {
        TestHelper::createEmptyObjects();
        $this->createTestPage('video', $returnData);

        $this->reloadPage();
        $this->testDataHelper->assertVideo($this->testPage, 'video', $this->seed, $returnData);
    }

    public function testWysiwyg()
    {
        $this->createTestPage('wysiwyg');

        $this->reloadPage();
        $this->testDataHelper->assertWysiwyg($this->testPage, 'wysiwyg', $this->seed);
    }
}
