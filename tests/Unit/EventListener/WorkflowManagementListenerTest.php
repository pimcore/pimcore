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

namespace Pimcore\Tests\Unit\EventListener;

use PHPUnit\Framework\MockObject\MockObject;
use Pimcore\Bundle\CoreBundle\EventListener\WorkflowManagementListener;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Event\Model\AssetEvent;
use Pimcore\Event\Model\DocumentEvent;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Workflow\EventSubscriber\NotesSubscriber;
use Pimcore\Workflow\ExpressionService;
use Pimcore\Workflow\Manager;
use Pimcore\Workflow\MarkingStore\PendingMarkingStoreInterface;
use Pimcore\Workflow\SupportStrategy\ExpressionSupportStrategy;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Workflow\Exception\LogicException;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\WorkflowInterface;

class WorkflowManagementListenerTest extends TestCase
{
    private const WORKFLOW_NAME = 'test_wf';

    private const ASSET_CACHE_KEY = 'asset_42';

    private const DOCUMENT_CACHE_KEY = 'document_7';

    protected function tearDown(): void
    {
        $runtimeCache = RuntimeCache::getInstance();
        foreach ([self::ASSET_CACHE_KEY, self::DOCUMENT_CACHE_KEY, self::documentPathCacheKey('/about'), self::documentPathCacheKey('/about-us')] as $cacheKey) {
            if ($runtimeCache->offsetExists($cacheKey)) {
                $runtimeCache->offsetUnset($cacheKey);
            }
        }

        parent::tearDown();
    }

    /**
     * A marking kept pending on an element (changePublishedState "save_version")
     * belongs to the draft: a version-only save must leave it pending, a full
     * save must persist it.
     */
    public function testPendingMarkingsArePersistedOnFullSaveOnly(): void
    {
        $element = $this->createElementWithPendingMarking();
        $store = $this->createPendingMarkingStore($element, 1);

        $manager = $this->createMock(Manager::class);
        $manager->expects($this->once())->method('getWorkflowByName')->with(self::WORKFLOW_NAME)->willReturn($this->createWorkflow($store));

        $listener = new WorkflowManagementListener($manager);

        $listener->onElementPostUpdate(new AssetEvent($element, ['saveVersionOnly' => true]));
        $listener->onElementPostUpdate(new AssetEvent($element));
    }

    /**
     * The pending place was set while the workflow applied to the element. Publishing the
     * draft must not re-evaluate the workflow's support strategy against the content being
     * published: a subject-dependent strategy (e.g. an expression) that turns false for the
     * draft would otherwise silently drop the place. The workflow is therefore resolved by
     * name, not via the registry.
     */
    public function testPendingMarkingIsPersistedEvenIfSupportStrategyRejectsTheDraft(): void
    {
        $element = $this->createElementWithPendingMarking();
        $store = $this->createPendingMarkingStore($element, 1);
        $workflow = $this->createWorkflow($store);

        $expressionService = $this->createMock(ExpressionService::class);
        $expressionService->method('evaluateExpression')->willReturn(false);

        $registry = new Registry();
        $registry->addWorkflow(
            $workflow,
            new ExpressionSupportStrategy($expressionService, Asset::class, 'subject.getKey() == "supported"')
        );

        $manager = $this->getMockBuilder(Manager::class)
            ->setConstructorArgs([$registry, $this->createMock(NotesSubscriber::class), $expressionService, new EventDispatcher()])
            ->onlyMethods(['getWorkflowByName'])
            ->getMock();
        $manager->registerWorkflow(self::WORKFLOW_NAME, ['type' => 'workflow']);
        $manager->expects($this->once())->method('getWorkflowByName')->with(self::WORKFLOW_NAME)->willReturn($workflow);

        $this->assertSame(
            [],
            $manager->getAllWorkflowsForSubject($element),
            'Precondition: the support strategy rejects the element, so a registry lookup would drop the workflow.'
        );

        (new WorkflowManagementListener($manager))->onElementPostUpdate(new AssetEvent($element));
    }

    public function testPendingMarkingOfAnUnknownWorkflowIsSkipped(): void
    {
        $element = $this->createElementWithPendingMarking();

        $manager = $this->createMock(Manager::class);
        $manager->method('getWorkflowByName')->willThrowException(new LogicException('workflow test_wf not found'));

        (new WorkflowManagementListener($manager))->onElementPostUpdate(new AssetEvent($element));

        $this->assertSame([self::WORKFLOW_NAME => ['review']], $element->__getPendingWorkflowMarkings());
    }

    public function testElementsWithoutPendingMarkingsAreLeftAlone(): void
    {
        $manager = $this->createMock(Manager::class);
        $manager->expects($this->never())->method('getWorkflowByName');

        $listener = new WorkflowManagementListener($manager);

        $element = new Asset();
        $element->setId(42);

        $listener->onElementPostUpdate(new AssetEvent($element));
    }

    /**
     * When the element that was saved as a version is the instance held by the runtime
     * cache (no draft existed before the transition), later loads in the same process
     * must not get the draft's pending place as if it were the published one.
     */
    public function testVersionOnlySaveDetachesTheDraftInstanceFromTheRuntimeCache(): void
    {
        $element = $this->createElementWithPendingMarking();
        RuntimeCache::set(self::ASSET_CACHE_KEY, $element);

        $this->createListener()->onElementPostUpdate(new AssetEvent($element, ['saveVersionOnly' => true]));

        $this->assertFalse(RuntimeCache::isRegistered(self::ASSET_CACHE_KEY), 'The draft instance must be dropped from the runtime cache.');
        $this->assertSame(
            [self::WORKFLOW_NAME => ['review']],
            $element->__getPendingWorkflowMarkings(),
            'The draft instance itself keeps its pending marking.'
        );
    }

    public function testVersionOnlySaveOfADraftLeavesTheCachedPublishedInstanceAlone(): void
    {
        $published = new Asset();
        $published->setId(42);
        RuntimeCache::set(self::ASSET_CACHE_KEY, $published);

        $draft = $this->createElementWithPendingMarking();

        $this->createListener()->onElementPostUpdate(new AssetEvent($draft, ['saveVersionOnly' => true]));

        $this->assertSame($published, RuntimeCache::get(self::ASSET_CACHE_KEY), 'Only the instance carrying the draft state may be dropped.');
    }

    public function testVersionOnlySaveWithoutPendingMarkingsLeavesTheRuntimeCacheAlone(): void
    {
        $element = new Asset();
        $element->setId(42);
        RuntimeCache::set(self::ASSET_CACHE_KEY, $element);

        $this->createListener()->onElementPostUpdate(new AssetEvent($element, ['saveVersionOnly' => true]));

        $this->assertSame($element, RuntimeCache::get(self::ASSET_CACHE_KEY));
    }

    /**
     * Documents are registered under their id and under their path.
     */
    public function testVersionOnlySaveDetachesADocumentFromBothRuntimeCacheKeys(): void
    {
        $document = $this->createDocumentWithPendingMarking();

        $pathKey = self::documentPathCacheKey('/about');
        RuntimeCache::set(self::DOCUMENT_CACHE_KEY, $document);
        RuntimeCache::set($pathKey, $document);

        $this->createListener()->onElementPostUpdate(new DocumentEvent($document, ['saveVersionOnly' => true]));

        $this->assertFalse(RuntimeCache::isRegistered(self::DOCUMENT_CACHE_KEY));
        $this->assertFalse(RuntimeCache::isRegistered($pathKey));
    }

    /**
     * The draft may have renamed or moved the document without saving: the path entry was made
     * under the old path, so it must be found by the cached instance, not by the current path.
     */
    public function testVersionOnlySaveDetachesADocumentRegisteredUnderItsOldPath(): void
    {
        $document = $this->createDocumentWithPendingMarking();

        $oldPathKey = self::documentPathCacheKey('/about');
        RuntimeCache::set(self::DOCUMENT_CACHE_KEY, $document);
        RuntimeCache::set($oldPathKey, $document);

        $document->setKey('about-us');
        $this->assertSame('/about-us', $document->getRealFullPath(), 'Precondition: the unsaved rename changed the path.');

        $this->createListener()->onElementPostUpdate(new DocumentEvent($document, ['saveVersionOnly' => true]));

        $this->assertFalse(RuntimeCache::isRegistered($oldPathKey), 'The entry made under the old path must be dropped as well.');
        $this->assertFalse(RuntimeCache::isRegistered(self::DOCUMENT_CACHE_KEY));
    }

    private function createDocumentWithPendingMarking(): Document\Page
    {
        $document = new Document\Page();
        $document->setId(7);
        $document->setParentId(1);
        $document->setPath('/');
        $document->setKey('about');
        $document->__setPendingWorkflowMarking(self::WORKFLOW_NAME, ['review']);

        return $document;
    }

    /**
     * Mirrors Document::getPathCacheKey(), which is not public.
     */
    private static function documentPathCacheKey(string $path): string
    {
        return 'document_path_' . md5($path);
    }

    private function createListener(): WorkflowManagementListener
    {
        $manager = $this->createMock(Manager::class);
        $manager->expects($this->never())->method('getWorkflowByName');

        return new WorkflowManagementListener($manager);
    }

    private function createElementWithPendingMarking(): Asset
    {
        $element = new Asset();
        $element->setId(42);
        $element->__setPendingWorkflowMarking(self::WORKFLOW_NAME, ['review']);

        return $element;
    }

    private function createPendingMarkingStore(Asset $element, int $expectedPersistCalls): PendingMarkingStoreInterface&MockObject
    {
        $store = $this->createMock(PendingMarkingStoreInterface::class);
        $store->expects($this->exactly($expectedPersistCalls))->method('persistPendingMarking')->with($element);

        return $store;
    }

    private function createWorkflow(PendingMarkingStoreInterface $store): WorkflowInterface&MockObject
    {
        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->method('getName')->willReturn(self::WORKFLOW_NAME);
        $workflow->method('getMarkingStore')->willReturn($store);

        return $workflow;
    }
}
