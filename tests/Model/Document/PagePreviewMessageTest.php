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

namespace Pimcore\Tests\Model\Document;

use Closure;
use Pimcore;
use Pimcore\Config;
use Pimcore\Messenger\GeneratePagePreviewMessage;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\Event\SendMessageToTransportsEvent;

/**
 * Page::save() queues the tree preview with the host Gotenberg's Chromium has to load: the
 * configured documents.preview_url_prefix, or the host of the current request without it.
 *
 * @group model.document.document
 */
class PagePreviewMessageTest extends ModelTestCase
{
    private const REQUEST_HOST_URL = 'https://admin.example.test';

    private const PREVIEW_URL_PREFIX = 'http://internal-host';

    /** @var array<string, mixed>|null */
    private ?array $documentsConfig = null;

    private RequestStack $requestStack;

    private EventDispatcherInterface $eventDispatcher;

    private Closure $listener;

    /** @var list<GeneratePagePreviewMessage> */
    private array $dispatched = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->documentsConfig = Config::getSystemConfiguration('documents');

        // Page::save() resolves the fallback host from the main request, as a backend save does
        $this->requestStack = Pimcore::getContainer()->get('request_stack');
        $this->requestStack->push(Request::create(self::REQUEST_HOST_URL . '/'));

        // capture what is handed to the transport, independent of the configured transport
        $this->dispatched = [];
        $this->listener = function (SendMessageToTransportsEvent $event): void {
            $message = $event->getEnvelope()->getMessage();
            if ($message instanceof GeneratePagePreviewMessage) {
                $this->dispatched[] = $message;
            }
        };
        $this->eventDispatcher = Pimcore::getContainer()->get('event_dispatcher');
        $this->eventDispatcher->addListener(SendMessageToTransportsEvent::class, $this->listener);
    }

    protected function tearDown(): void
    {
        $this->eventDispatcher->removeListener(SendMessageToTransportsEvent::class, $this->listener);
        $this->requestStack->pop();
        Config::setSystemConfiguration($this->documentsConfig, 'documents');

        parent::tearDown();
    }

    public function testConfiguredPreviewUrlPrefixWinsOverTheRequestHost(): void
    {
        $this->configurePreviews(true, self::PREVIEW_URL_PREFIX);

        $page = TestHelper::createEmptyDocumentPage();

        $this->assertCount(1, $this->dispatched);
        $this->assertSame($page->getId(), $this->dispatched[0]->getPageId());
        $this->assertSame(self::PREVIEW_URL_PREFIX, $this->dispatched[0]->getHostUrl());
    }

    public function testRequestHostIsUsedWithoutAPreviewUrlPrefix(): void
    {
        $this->configurePreviews(true, '');

        TestHelper::createEmptyDocumentPage();

        $this->assertCount(1, $this->dispatched);
        $this->assertSame(self::REQUEST_HOST_URL, $this->dispatched[0]->getHostUrl());
    }

    public function testNothingIsQueuedWhilePreviewsAreDisabled(): void
    {
        $this->configurePreviews(false, self::PREVIEW_URL_PREFIX);

        TestHelper::createEmptyDocumentPage();

        $this->assertSame([], $this->dispatched);
    }

    private function configurePreviews(bool $generatePreview, string $previewUrlPrefix): void
    {
        $config = $this->documentsConfig ?? [];
        $config['generate_preview'] = $generatePreview;
        $config['preview_url_prefix'] = $previewUrlPrefix;

        Config::setSystemConfiguration($config, 'documents');
    }
}
