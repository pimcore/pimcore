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

use Pimcore;
use Pimcore\Bundle\CoreBundle\Command\Document\GeneratePagePreviews;
use Pimcore\Cache;
use Pimcore\Config;
use Pimcore\Helper\GotenbergHelper;
use Pimcore\Image\HtmlToImage;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Messenger\GeneratePagePreviewMessage;
use Pimcore\Messenger\Handler\GeneratePagePreviewHandler;
use Pimcore\SystemSettingsConfig;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Psr\Log\NullLogger;
use ReflectionProperty;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The page preview worker and the preview command hand a URL to the Gotenberg renderer. A fake
 * Gotenberg endpoint records what the renderer is asked to load, to prove that the origin always
 * comes from the trusted configuration and never from a request host or from the queued message.
 *
 * @group model.document.document
 */
class PagePreviewRendererTest extends ModelTestCase
{
    private const ATTACKER_CONTROLLED_HOST_URL = 'http://127.0.0.3:18080';

    private const PREVIEW_URL_PREFIX = 'http://trusted-preview-host';

    private const SYSTEM_DOMAIN = 'pimcore.example';

    private const CACHE_KEY_GOTENBERG_PING = 'gotenberg_ping';

    /** @var array<string, mixed>|null */
    private ?array $documentsConfig = null;

    /** @var array<string, mixed>|null */
    private ?array $gotenbergConfig = null;

    /** @var array<string, mixed>|null */
    private ?array $originalSystemSettings = null;

    private RequestStack $requestStack;

    private string $requestLog;

    private string $routerFile;

    /** @var resource|null */
    private $fakeGotenberg = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->documentsConfig = Config::getSystemConfiguration('documents');
        $this->gotenbergConfig = Config::getSystemConfiguration('gotenberg');

        $systemSettings = SystemSettingsConfig::get();
        unset($systemSettings['writeable']);
        $this->originalSystemSettings = $systemSettings;

        $this->requestStack = Pimcore::getContainer()->get('request_stack');
        $this->requestStack->push(Request::create(self::ATTACKER_CONTROLLED_HOST_URL . '/'));

        $this->requestLog = sys_get_temp_dir() . '/page-preview-renderer-requests-' . uniqid() . '.log';
        $this->routerFile = sys_get_temp_dir() . '/page-preview-renderer-router-' . uniqid() . '.php';

        $this->startFakeGotenberg();
        $this->resetRendererCaches();
    }

    protected function tearDown(): void
    {
        if (is_resource($this->fakeGotenberg)) {
            proc_terminate($this->fakeGotenberg);
            proc_close($this->fakeGotenberg);
        }
        @unlink($this->requestLog);
        @unlink($this->routerFile);

        $this->requestStack->pop();
        Config::setSystemConfiguration($this->documentsConfig, 'documents');
        Config::setSystemConfiguration($this->gotenbergConfig, 'gotenberg');

        if (null !== $this->originalSystemSettings) {
            $this->systemSettingsConfig()->testSave($this->originalSystemSettings);
        }

        $this->resetRendererCaches();

        parent::tearDown();
    }

    public function testHandlerRendersTheConfiguredPrefixInsteadOfTheMessageHost(): void
    {
        $page = $this->createPage();
        $this->configure(self::PREVIEW_URL_PREFIX, self::SYSTEM_DOMAIN);

        $this->handle($page->getId());

        $this->assertRenderedUrls([self::PREVIEW_URL_PREFIX . $page->getRealFullPath()]);
    }

    public function testHandlerRendersTheSystemDomainInsteadOfTheMessageHostWithoutAPrefix(): void
    {
        $page = $this->createPage();
        $this->configure('', self::SYSTEM_DOMAIN);

        $this->handle($page->getId());

        $this->assertRenderedUrls(['http://' . self::SYSTEM_DOMAIN . $page->getRealFullPath()]);
    }

    public function testHandlerRendersNothingWhenNoTrustedOriginIsConfigured(): void
    {
        $page = $this->createPage();
        $this->configure('', '');

        $this->handle($page->getId());

        $this->assertRenderedUrls([]);
    }

    public function testCommandRendersTheSystemDomainInsteadOfTheRequestHostWithoutAPrefix(): void
    {
        $page = $this->createPage();
        $this->configure('', self::SYSTEM_DOMAIN);

        $tester = new CommandTester(new GeneratePagePreviews());
        $tester->execute(['--parent' => '1']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertRenderedUrls(['http://' . self::SYSTEM_DOMAIN . '/', 'http://' . self::SYSTEM_DOMAIN . $page->getRealFullPath()]);
    }

    public function testCommandFailsWhenNoTrustedOriginIsConfigured(): void
    {
        $this->createPage();
        $this->configure('', '');

        $tester = new CommandTester(new GeneratePagePreviews());
        $tester->execute(['--parent' => '1']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertRenderedUrls([]);
    }

    private function createPage(): Pimcore\Model\Document\Page
    {
        // created before previews are enabled, so that saving it does not queue a message of its own
        $config = $this->documentsConfig;
        $config['generate_preview'] = false;
        Config::setSystemConfiguration($config, 'documents');

        return TestHelper::createEmptyDocumentPage();
    }

    private function handle(int $pageId): void
    {
        $handler = new GeneratePagePreviewHandler(new NullLogger());
        $handler(new GeneratePagePreviewMessage($pageId, self::ATTACKER_CONTROLLED_HOST_URL));
    }

    private function configure(string $previewUrlPrefix, string $systemDomain): void
    {
        $documents = $this->documentsConfig;
        $documents['generate_preview'] = true;
        $documents['preview_url_prefix'] = $previewUrlPrefix;
        Config::setSystemConfiguration($documents, 'documents');

        $systemSettings = $this->originalSystemSettings ?? [];
        $systemSettings['general']['domain'] = $systemDomain;
        $this->systemSettingsConfig()->testSave($systemSettings);
    }

    /**
     * @param list<string> $expectedUrls
     */
    private function assertRenderedUrls(array $expectedUrls): void
    {
        $recorded = is_file($this->requestLog) ? (string) file_get_contents($this->requestLog) : '';

        $this->assertStringNotContainsString('127.0.0.3', $recorded, 'The renderer must never be pointed at the request host');

        $urls = array_values(array_filter(explode("\x1e", $recorded), static fn (string $url): bool => $url !== ''));

        sort($expectedUrls);
        sort($urls);

        $this->assertSame($expectedUrls, $urls);
    }

    private function startFakeGotenberg(): void
    {
        file_put_contents($this->routerFile, sprintf(
            '<?php
            if (parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH) === "/health") {
                echo "ok";
                return;
            }
            file_put_contents(%s, ($_POST["url"] ?? "") . "\x1e", FILE_APPEND);
            http_response_code(500);',
            var_export($this->requestLog, true)
        ));

        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $port = (int) parse_url((string) stream_socket_get_name($socket, false), PHP_URL_PORT);
        fclose($socket);

        $pipes = [];
        $process = proc_open(
            [PHP_BINARY, '-S', '127.0.0.1:' . $port, $this->routerFile],
            [['pipe', 'r'], ['file', '/dev/null', 'w'], ['file', '/dev/null', 'w']],
            $pipes
        );
        $this->assertIsResource($process);
        $this->fakeGotenberg = $process;

        for ($attempt = 0; $attempt < 50; $attempt++) {
            $connection = @fsockopen('127.0.0.1', $port, timeout: 0.2);
            if ($connection !== false) {
                fclose($connection);

                break;
            }
            usleep(100000);
        }

        $config = $this->gotenbergConfig;
        $config['base_url'] = 'http://127.0.0.1:' . $port;
        Config::setSystemConfiguration($config, 'gotenberg');
    }

    /**
     * The availability of the renderer is memoized in process memory and in the cache.
     */
    private function resetRendererCaches(): void
    {
        (new ReflectionProperty(GotenbergHelper::class, 'validPing'))->setValue(null, null);
        (new ReflectionProperty(HtmlToImage::class, 'supportedAdapter'))->setValue(null, null);
        Cache::remove(self::CACHE_KEY_GOTENBERG_PING);
    }

    private function systemSettingsConfig(): SystemSettingsConfig
    {
        return new SystemSettingsConfig(
            Pimcore::getContainer()->get('event_dispatcher'),
            Pimcore::getContainer()->get(LocaleServiceInterface::class)
        );
    }
}
