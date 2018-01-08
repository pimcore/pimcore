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
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Templating\EngineInterface;

class Tracker extends AbstractTracker
{
    const BLOCK_BEFORE_SCRIPT_TAG = 'beforeScriptTag';
    const BLOCK_BEFORE_SCRIPT = 'beforeScript';
    const BLOCK_BEFORE_INIT = 'beforeInit';
    const BLOCK_BEFORE_TRACK = 'beforeTrack';
    const BLOCK_AFTER_TRACK = 'afterTrack';
    const BLOCK_AFTER_SCRIPT = 'afterScript';
    const BLOCK_AFTER_SCRIPT_TAG = 'afterScriptTag';

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
    )
    {
        parent::__construct($siteIdProvider);

        $this->configProvider   = $configProvider;
        $this->eventDispatcher  = $eventDispatcher;
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

        $data = [
            'siteId'                 => $siteId,
            'config'                 => $config,
            'siteConfig'             => $siteConfig,
            'trackId'                => $siteConfig->trackid,
            'defaultPath'            => $this->getDefaultPath(),
            'universalConfiguration' => $siteConfig->universal_configuration ?? null,
            'retargeting'            => $siteConfig->retargetingcode ?? false,
        ];

        $template = '@PimcoreCore/Analytics/Tracking/Google/Analytics/universalTrackingCode.html.twig';
        if ($siteConfig->asynchronouscode || $siteConfig->retargetingcode) {
            $template = '@PimcoreCore/Analytics/Tracking/Google/Analytics/asynchronousTrackingCode.html.twig';
        }

        $blocks = $this->buildCodeBlocks($config, $siteId);

        $event = new TrackingDataEvent($config, $siteId, $data, $blocks, $template);
        $this->eventDispatcher->dispatch(GoogleAnalyticsEvents::CODE_TRACKING_DATA, $event);

        return $this->renderTemplate($event);
    }

    private function renderTemplate(TrackingDataEvent $event): string
    {
        $data           = $event->getData();
        $data['blocks'] = $event->getBlocks();

        $code = $this->templatingEngine->render(
            $event->getTemplate(),
            $data
        );

        $code = trim($code);

        return $code;
    }

    private function buildCodeBlocks(Config $config, SiteId $siteId): array
    {
        $configKey  = $siteId->getConfigKey();
        $siteConfig = $config->getConfigForSite($configKey);
        $blockData  = $this->buildBlockData($siteConfig, $config, $siteId);

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

    private function buildBlockData(ConfigObject $siteConfig, Config $config, SiteId $siteId): array
    {
        $blockData = [];

        if (!empty($siteConfig->additionalcodebeforeinit)) {
            $blockData[self::BLOCK_BEFORE_INIT] = $siteConfig->additionalcodebeforeinit;
        }

        if (!empty($siteConfig->additionalcodebeforepageview)) {
            $blockData[self::BLOCK_BEFORE_TRACK] = $siteConfig->additionalcodebeforepageview;
        }

        if (!empty($siteConfig->additionalcode)) {
            $blockData[self::BLOCK_AFTER_TRACK] = $siteConfig->additionalcode;
        }

        return $blockData;
    }
}
