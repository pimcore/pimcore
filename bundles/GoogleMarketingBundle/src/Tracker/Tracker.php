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

namespace Pimcore\Bundle\GoogleMarketingBundle\Tracker;

use Pimcore\Bundle\GoogleMarketingBundle\Code\CodeBlock;
use Pimcore\Bundle\GoogleMarketingBundle\Code\CodeCollector;
use Pimcore\Bundle\GoogleMarketingBundle\Config\Config;
use Pimcore\Bundle\GoogleMarketingBundle\Config\ConfigProvider;
use Pimcore\Bundle\GoogleMarketingBundle\Event\GoogleAnalyticsEvents;
use Pimcore\Bundle\GoogleMarketingBundle\Model\Event\TrackingDataEvent;
use Pimcore\Bundle\GoogleMarketingBundle\SiteId\SiteId;
use Pimcore\Bundle\GoogleMarketingBundle\SiteId\SiteIdProvider;
use Psr\Log\LoggerAwareTrait;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

class Tracker extends AbstractTracker
{
    use LoggerAwareTrait;

    const BLOCK_BEFORE_SCRIPT_TAG = 'beforeScriptTag';

    const BLOCK_BEFORE_SCRIPT = 'beforeScript';

    const BLOCK_BEFORE_INIT = 'beforeInit';

    const BLOCK_BEFORE_TRACK = 'beforeTrack';

    const BLOCK_AFTER_TRACK = 'afterTrack';

    const BLOCK_AFTER_SCRIPT = 'afterScript';

    const BLOCK_AFTER_SCRIPT_TAG = 'afterScriptTag';

    private SiteIdProvider $siteIdProvider;

    private ConfigProvider $configProvider;

    private EventDispatcherInterface $eventDispatcher;

    private Environment $twig;

    private ?string $defaultPath = null;

    private array $blocks = [
        self::BLOCK_BEFORE_SCRIPT_TAG,
        self::BLOCK_BEFORE_SCRIPT,
        self::BLOCK_BEFORE_INIT,
        self::BLOCK_BEFORE_TRACK,
        self::BLOCK_AFTER_TRACK,
        self::BLOCK_AFTER_SCRIPT,
        self::BLOCK_AFTER_SCRIPT_TAG,
    ];

    public function __construct(
        SiteIdProvider $siteIdProvider,
        ConfigProvider $configProvider,
        EventDispatcherInterface $eventDispatcher,
        Environment $twig
    ) {
        parent::__construct($siteIdProvider);

        $this->siteIdProvider = $siteIdProvider;
        $this->configProvider = $configProvider;
        $this->eventDispatcher = $eventDispatcher;
        $this->twig = $twig;
    }

    public function getDefaultPath(): ?string
    {
        return $this->defaultPath;
    }

    public function setDefaultPath(string $defaultPath = null): void
    {
        $this->defaultPath = $defaultPath;
    }

    protected function buildCodeCollector(): CodeCollector
    {
        return new CodeCollector($this->blocks, self::BLOCK_AFTER_TRACK);
    }

    protected function buildCode(SiteId $siteId): ?string
    {
        $config = $this->configProvider->getConfig();

        $configKey = $siteId->getConfigKey();
        if (!$config->isSiteConfigured($configKey)) {
            return null;
        }

        $siteConfig = $config->getConfigForSite($configKey);

        return $this->doBuildCode($siteId, $config, $siteConfig);
    }

    /**
     * This method exists for BC with the existing Pimcore\Google\Analytics implementation which supports to pass a config
     * object without a Site ID. Should be removed at a later point.
     *
     * @param array $siteConfig
     * @param SiteId|null $siteId
     *
     * @return string
     */
    public function generateCodeForSiteConfig(array $siteConfig, SiteId $siteId = null): string
    {
        if (null === $siteId) {
            $siteId = $this->siteIdProvider->getForRequest();
        }

        $config = $this->configProvider->getConfig();

        return $this->doBuildCode($siteId, $config, $siteConfig);
    }

    private function doBuildCode(SiteId $siteId, Config $config, array $siteConfig): string
    {
        $data = [
            'siteId' => $siteId,
            'config' => $config,
            'siteConfig' => $siteConfig,
            'trackId' => $siteConfig['trackid'],
            'defaultPath' => $this->getDefaultPath(),
            'universalConfiguration' => $siteConfig['universal_configuration'] ?? null,
            'retargeting' => $siteConfig['retargetingcode'] ?? false,
        ];

        if ($siteConfig['gtagcode']) {
            $template = '@PimcoreGoogleMarketing/Analytics/Tracking/Google/Analytics/gtagTrackingCode.html.twig';

            $data['gtagConfig'] = $this->getTrackerConfigurationFromJson($siteConfig['universal_configuration'] ?? null, [
                'anonymize_ip' => true,
            ]);
        } elseif (isset($siteConfig['asynchronouscode']) || isset($siteConfig['retargetingcode'])) {
            $template = '@PimcoreGoogleMarketing/Analytics/Tracking/Google/Analytics/asynchronousTrackingCode.html.twig';
        } else {
            $template = '@PimcoreGoogleMarketing/Analytics/Tracking/Google/Analytics/universalTrackingCode.html.twig';
        }

        $blocks = $this->buildCodeBlocks($siteId, $siteConfig);

        $event = new TrackingDataEvent($config, $siteId, $data, $blocks, $template);
        $this->eventDispatcher->dispatch($event, GoogleAnalyticsEvents::CODE_TRACKING_DATA);

        return $this->renderTemplate($event);
    }

    /**
     * @param array<string, mixed> $defaultConfig
     *
     * @return array<string, mixed>
     */
    private function getTrackerConfigurationFromJson(string $configValue = null, array $defaultConfig = []): array
    {
        $config = [];
        if (!empty($configValue)) {
            $jsonConfig = @json_decode($configValue, true);
            if (JSON_ERROR_NONE === json_last_error() && is_array($jsonConfig)) {
                $config = $jsonConfig;
            } else {
                $this->logger->warning('Failed to parse analytics tracker custom configuration: {error}', [
                    'error' => json_last_error_msg(),
                ]);
            }
        }

        return array_merge($defaultConfig, $config);
    }

    /**
     * @param SiteId $siteId
     * @param array<string, mixed> $siteConfig
     *
     * @return array<string, CodeBlock>
     */
    private function buildCodeBlocks(SiteId $siteId, array $siteConfig): array
    {
        $blockData = $this->buildBlockData($siteConfig);

        $blocks = [];
        foreach ($this->blocks as $block) {
            $codeBlock = new CodeBlock();

            if (isset($blockData[$block])) {
                $codeBlock->append($blockData[$block]);
            }

            $this->getCodeCollector()->enrichCodeBlock($siteId, $codeBlock, $block);

            $blocks[$block] = $codeBlock;
        }

        return $blocks;
    }

    /**
     * @param array<string, mixed> $siteConfig
     *
     * @return array<string, mixed>
     */
    private function buildBlockData(array $siteConfig): array
    {
        $blockData = [];

        if (!empty($siteConfig['additionalcodebeforeinit'])) {
            $blockData[self::BLOCK_BEFORE_INIT] = $siteConfig['additionalcodebeforeinit'];
        }

        if (!empty($siteConfig['additionalcodebeforepageview'])) {
            $blockData[self::BLOCK_BEFORE_TRACK] = $siteConfig['additionalcodebeforepageview'];
        }

        if (!empty($siteConfig['additionalcode'])) {
            $blockData[self::BLOCK_AFTER_TRACK] = $siteConfig['additionalcode'];
        }

        return $blockData;
    }

    private function renderTemplate(TrackingDataEvent $event): string
    {
        $data = $event->getData();
        $data['blocks'] = $event->getBlocks();

        $code = $this->twig->render(
            $event->getTemplate(),
            $data
        );

        $code = trim($code);
        if (!empty($code)) {
            $code = "\n" . $code . "\n";
        }

        return $code;
    }
}
