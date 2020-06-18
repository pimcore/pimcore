<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Analytics\Google;

use Pimcore\Analytics\AbstractTracker;
use Pimcore\Analytics\Code\CodeBlock;
use Pimcore\Analytics\Code\CodeCollector;
use Pimcore\Analytics\Google\Config\Config;
use Pimcore\Analytics\Google\Config\ConfigProvider;
use Pimcore\Analytics\Google\Event\TrackingDataEvent;
use Pimcore\Analytics\SiteId\SiteId;
use Pimcore\Analytics\SiteId\SiteIdProvider;
use Pimcore\Config\Config as ConfigObject;
use Pimcore\Event\Analytics\GoogleAnalyticsEvents;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Templating\EngineInterface;

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

    /**
     * @var SiteIdProvider
     */
    private $siteIdProvider;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var EngineInterface
     */
    private $templatingEngine;

    /**
     * @var string|null
     */
    private $defaultPath;

    /**
     * @var array
     */
    private $blocks = [
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
        EngineInterface $templatingEngine
    ) {
        parent::__construct($siteIdProvider);

        $this->siteIdProvider = $siteIdProvider;
        $this->configProvider = $configProvider;
        $this->eventDispatcher = $eventDispatcher;
        $this->templatingEngine = $templatingEngine;
    }

    public function getDefaultPath()
    {
        return $this->defaultPath;
    }

    public function setDefaultPath(string $defaultPath = null)
    {
        $this->defaultPath = $defaultPath;
    }

    protected function buildCodeCollector(): CodeCollector
    {
        return new CodeCollector($this->blocks, self::BLOCK_AFTER_TRACK);
    }

    protected function buildCode(SiteId $siteId)
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
     * @param ConfigObject $siteConfig
     * @param SiteId|null $siteId
     *
     * @return string
     */
    public function generateCodeForSiteConfig(ConfigObject $siteConfig, SiteId $siteId = null)
    {
        if (null === $siteId) {
            $siteId = $this->siteIdProvider->getForRequest();
        }

        $config = $this->configProvider->getConfig();

        return $this->doBuildCode($siteId, $config, $siteConfig);
    }

    private function doBuildCode(SiteId $siteId, Config $config, ConfigObject $siteConfig)
    {
        $data = [
            'siteId' => $siteId,
            'config' => $config,
            'siteConfig' => $siteConfig,
            'trackId' => $siteConfig->get('trackid'),
            'defaultPath' => $this->getDefaultPath(),
            'universalConfiguration' => $siteConfig->get('universal_configuration') ?? null,
            'retargeting' => $siteConfig->get('retargetingcode') ?? false,
        ];

        if ($siteConfig->get('gtagcode')) {
            $template = '@PimcoreCore/Analytics/Tracking/Google/Analytics/gtagTrackingCode.html.twig';

            $data['gtagConfig'] = $this->getTrackerConfigurationFromJson($siteConfig->get('universal_configuration') ?? null, [
                'anonymize_ip' => true,
            ]);
        } elseif ($siteConfig->get('asynchronouscode') || $siteConfig->get('retargetingcode')) {
            $template = '@PimcoreCore/Analytics/Tracking/Google/Analytics/asynchronousTrackingCode.html.twig';
        } else {
            $template = '@PimcoreCore/Analytics/Tracking/Google/Analytics/universalTrackingCode.html.twig';
        }

        $blocks = $this->buildCodeBlocks($siteId, $siteConfig);

        $event = new TrackingDataEvent($config, $siteId, $data, $blocks, $template);
        $this->eventDispatcher->dispatch(GoogleAnalyticsEvents::CODE_TRACKING_DATA, $event);

        return $this->renderTemplate($event);
    }

    private function getTrackerConfigurationFromJson($configValue = null, array $defaultConfig = []): array
    {
        $config = [];
        if (!empty($configValue)) {
            $jsonConfig = @json_decode($configValue, true);
            if (JSON_ERROR_NONE === json_last_error() && is_array($jsonConfig)) {
                $config = $jsonConfig;
            } else {
                $this->logger->warning('Failed to parse analytics tracker custom configuration: {error}', [
                    'error' => json_last_error_msg() ?? 'not an array',
                ]);
            }
        }

        return array_merge($defaultConfig, $config);
    }

    private function buildCodeBlocks(SiteId $siteId, ConfigObject $siteConfig): array
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

    private function buildBlockData(ConfigObject $siteConfig): array
    {
        $blockData = [];

        if (!empty($siteConfig->get('additionalcodebeforeinit'))) {
            $blockData[self::BLOCK_BEFORE_INIT] = $siteConfig->get('additionalcodebeforeinit');
        }

        if (!empty($siteConfig->get('additionalcodebeforepageview'))) {
            $blockData[self::BLOCK_BEFORE_TRACK] = $siteConfig->get('additionalcodebeforepageview');
        }

        if (!empty($siteConfig->get('additionalcode'))) {
            $blockData[self::BLOCK_AFTER_TRACK] = $siteConfig->get('additionalcode');
        }

        return $blockData;
    }

    private function renderTemplate(TrackingDataEvent $event): string
    {
        $data = $event->getData();
        $data['blocks'] = $event->getBlocks();

        $code = $this->templatingEngine->render(
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
