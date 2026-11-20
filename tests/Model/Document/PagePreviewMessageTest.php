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
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Messenger\GeneratePagePreviewMessage;
use Pimcore\SystemSettingsConfig;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\Event\SendMessageToTransportsEvent;

/**
 * Page::save() queues the tree preview with the host Gotenberg's Chromium has to load: the
 * configured documents.preview_url_prefix, or (without it) the system's main domain setting. The
 * host of the save request itself must never be used, since that host is attacker-controlled and
 * would otherwise let an inbound request pick the network destination the renderer navigates to.
 *
 * @group model.document.document
 */
class PagePreviewMessageTest extends ModelTestCase
{
    private const ATTACKER_CONTROLLED_REQUEST_HOST_URL = 'https://127.0.0.3:18080';

    private const PREVIEW_URL_PREFIX = 'http://internal-host';

    private const SYSTEM_DOMAIN = 'pimcore.example';

    /** @var array<string, mixed>|null */
    private ?array $documentsConfig = null;

    /** @var array<string, mixed>|null */
    private ?array $originalSystemSettings = null;

    private RequestStack $requestStack;

    private EventDispatcherInterface $eventDispatcher;

    private Closure $listener;

    /** @var list<GeneratePagePreviewMessage> */
    private array $dispatched = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->documentsConfig = Config::getSystemConfiguration('documents');

        $systemSettings = SystemSettingsConfig::get();
        unset($systemSettings['writeable']);
        $this->originalSystemSettings = $systemSettings;

        // Page::save() must ignore this and fall back to the configured system domain instead
        $this->requestStack = Pimcore::getContainer()->get('request_stack');
        $this->requestStack->push(Request::create(self::ATTACKER_CONTROLLED_REQUEST_HOST_URL . '/'));

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

        if (null !== $this->originalSystemSettings) {
            $this->systemSettingsConfig()->testSave($this->originalSystemSettings);
        }

        parent::tearDown();
    }

    public function testConfiguredPreviewUrlPrefixWinsOverTheRequestHost(): void
    {
        $this->configurePreviews(true, self::PREVIEW_URL_PREFIX);
        $this->configureSystemDomain(self::SYSTEM_DOMAIN);

        $page = TestHelper::createEmptyDocumentPage();

        $this->assertCount(1, $this->dispatched);
        $this->assertSame($page->getId(), $this->dispatched[0]->getPageId());
        $this->assertSame(self::PREVIEW_URL_PREFIX, $this->dispatched[0]->getHostUrl());
    }

    public function testSystemDomainIsUsedInsteadOfTheRequestHostWithoutAPreviewUrlPrefix(): void
    {
        $this->configurePreviews(true, '');
        $this->configureSystemDomain(self::SYSTEM_DOMAIN);

        TestHelper::createEmptyDocumentPage();

        $this->assertCount(1, $this->dispatched);
        $this->assertSame('http://' . self::SYSTEM_DOMAIN, $this->dispatched[0]->getHostUrl());
    }

    public function testNothingIsQueuedWhenNeitherAPreviewUrlPrefixNorASystemDomainIsConfigured(): void
    {
        $this->configurePreviews(true, '');
        $this->configureSystemDomain('');

        TestHelper::createEmptyDocumentPage();

        $this->assertSame([], $this->dispatched);
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

    private function configureSystemDomain(string $domain): void
    {
        $systemSettings = $this->originalSystemSettings ?? [];
        $systemSettings['general']['domain'] = $domain;

        $this->systemSettingsConfig()->testSave($systemSettings);
    }

    private function systemSettingsConfig(): SystemSettingsConfig
    {
        return new SystemSettingsConfig(
            Pimcore::getContainer()->get('event_dispatcher'),
            Pimcore::getContainer()->get(LocaleServiceInterface::class)
        );
    }
}
