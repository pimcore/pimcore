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

namespace Pimcore\Tests\Model\Document;

use InvalidArgumentException;
use Pimcore\Model\Document\Page;
use Pimcore\Tests\Support\Helper\Document\TestDataHelper;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Class ElementTest
 *
 * @package Pimcore\Tests\Model\Document
 *
 * @group model.document.document
 */
class EditableTest extends ModelTestCase
{
    protected int $seed = 1;

    protected Page $testPage;

    protected TestDataHelper $testDataHelper;

    public function _inject(TestDataHelper $testData): void
    {
        $this->testDataHelper = $testData;
    }

    public function testAreablock(): void
    {
        $this->createTestPage('areablock');

        $this->reloadPage();
        $this->testDataHelper->assertAreablock($this->testPage, 'areablock', $this->seed);
    }

    public function testCheckbox(): void
    {
        $this->createTestPage('checkbox');

        $this->reloadPage();
        $this->testDataHelper->assertCheckbox($this->testPage, 'checkbox', $this->seed);
    }

    public function testDate(): void
    {
        $this->createTestPage('date');

        $this->reloadPage();
        $this->testDataHelper->assertDate($this->testPage, 'date', $this->seed);
    }

    public function testEmbed(): void
    {
        $this->createTestPage('embed');

        $this->reloadPage();
        $this->testDataHelper->assertEmbed($this->testPage, 'embed', $this->seed);
    }

    public function testImage(): void
    {
        $returnData = [];
        $this->createTestPage('image', $returnData);

        $this->reloadPage();
        $this->testDataHelper->assertImage($this->testPage, 'image', $this->seed, $returnData);
    }

    public function testInput(): void
    {
        $this->createTestPage('input');

        $this->reloadPage();
        $this->testDataHelper->assertInput($this->testPage, 'input', $this->seed);
    }

    public function testLink(): void
    {
        $returnData = [];
        TestHelper::createEmptyObjects();
        $this->createTestPage('link', $returnData);

        $this->reloadPage();
        $this->testDataHelper->assertLink($this->testPage, 'link', $this->seed, $returnData);
    }

    public function testMultiselect(): void
    {
        $this->createTestPage('multiselect');

        $this->reloadPage();
        $this->testDataHelper->assertMultiselect($this->testPage, 'multiselect', $this->seed);
    }

    public function testNumeric(): void
    {
        $this->createTestPage('numeric');

        $this->reloadPage();
        $this->testDataHelper->assertNumeric($this->testPage, 'numeric', $this->seed);
    }

    public function testPdf(): void
    {
        $returnData = [];
        $this->createTestPage('pdf', $returnData);

        $this->reloadPage();
        $this->testDataHelper->assertPdf($this->testPage, 'pdf', $this->seed, $returnData);
    }

    public function testRelation(): void
    {
        $returnData = [];
        TestHelper::createEmptyObjects();
        $this->createTestPage('relation', $returnData);

        $this->reloadPage();
        $this->testDataHelper->assertRelation($this->testPage, 'relation', $this->seed, $returnData);
    }

    public function testRelations(): void
    {
        $returnData = [];
        TestHelper::createEmptyObjects();
        $this->createTestPage('relations', $returnData);

        $this->reloadPage();
        $this->testDataHelper->assertRelations($this->testPage, 'relations', $this->seed, $returnData);
    }

    public function testScheduledblock(): void
    {
        $this->createTestPage('scheduledblock');

        $this->reloadPage();
        $this->testDataHelper->assertScheduledblock($this->testPage, 'scheduledblock', $this->seed);
    }

    protected function createTestPage(array|string $fields = [], array &$returnData = []): Page|\Pimcore\Model\Document
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
     */
    protected function fillPage(Page $document, array|string $fields = [], array &$returnData = []): void
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
            throw new InvalidArgumentException('Fields needs to be an array');
        }

        foreach ($fields as $field) {
            $method = $field['method'];

            if (!$method) {
                throw new InvalidArgumentException(sprintf('Need a method to call'));
            }

            if (!method_exists($this->testDataHelper, $method)) {
                throw new InvalidArgumentException(sprintf('Method %s does not exist', $method));
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

    public function reloadPage(): void
    {
        $this->testPage = Page::getById($this->testPage->getId(), ['force' => true]);
    }

    public function testSelect(): void
    {
        $this->createTestPage('select');

        $this->reloadPage();
        $this->testDataHelper->assertSelect($this->testPage, 'select', $this->seed);
    }

    public function testTable(): void
    {
        $this->createTestPage('table');

        $this->reloadPage();
        $this->testDataHelper->assertTable($this->testPage, 'table', $this->seed);
    }

    public function testTextarea(): void
    {
        $this->createTestPage('textarea');

        $this->reloadPage();
        $this->testDataHelper->assertTextarea($this->testPage, 'textarea', $this->seed);
    }

    public function testVideo(): void
    {
        $returnData = [];
        TestHelper::createEmptyObjects();
        $this->createTestPage('video', $returnData);

        $this->reloadPage();
        $this->testDataHelper->assertVideo($this->testPage, 'video', $this->seed, $returnData);
    }

    public function testWysiwyg(): void
    {
        $this->createTestPage('wysiwyg');

        $this->reloadPage();
        $this->testDataHelper->assertWysiwyg($this->testPage, 'wysiwyg', $this->seed);
    }

    public function testBlock(): void
    {
        $this->createTestPage('block');

        $this->reloadPage();
        $this->testDataHelper->assertBlock($this->testPage, 'block', $this->seed);
    }
}
