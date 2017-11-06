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

namespace Pimcore\Analytics\Piwik;

use Pimcore\Analytics\AbstractTracker;
use Pimcore\Analytics\Code\CodeBlock;
use Pimcore\Analytics\Code\CodeCollector;
use Pimcore\Analytics\Piwik\Config\Config;
use Pimcore\Analytics\Piwik\Config\ConfigProvider;
use Pimcore\Analytics\Piwik\Event\TrackingDataEvent;
use Pimcore\Analytics\SiteId\SiteId;
use Pimcore\Analytics\SiteId\SiteIdProvider;
use Pimcore\Config\Config as ConfigObject;
use Pimcore\Event\Analytics\PiwikEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Templating\EngineInterface;

class Tracker extends AbstractTracker
{
    const BLOCK_BEFORE_SCRIPT_TAG = 'beforeScriptTag';
    const BLOCK_BEFORE_SCRIPT = 'beforeScript';
    const BLOCK_BEFORE_TRACK = 'beforeTrack';
    const BLOCK_TRACK = 'track';
    const BLOCK_AFTER_TRACK = 'afterTrack';
    const BLOCK_BEFORE_ASYNC = 'beforeAsync';
    const BLOCK_AFTER_ASYNC = 'afterAsync';
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
     * @var array
     */
    private $blocks = [
        self::BLOCK_BEFORE_SCRIPT_TAG,
        self::BLOCK_BEFORE_SCRIPT,
        self::BLOCK_BEFORE_TRACK,
        self::BLOCK_TRACK,
        self::BLOCK_AFTER_TRACK,
        self::BLOCK_BEFORE_ASYNC,
        self::BLOCK_AFTER_ASYNC,
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

        $this->configProvider   = $configProvider;
        $this->eventDispatcher  = $eventDispatcher;
        $this->templatingEngine = $templatingEngine;
    }

    protected function buildCodeCollector(): CodeCollector
    {
        return new CodeCollector($this->blocks, self::BLOCK_TRACK);
    }

    protected function buildCode(SiteId $siteId)
    {
        $config = $this->configProvider->getConfig();
        if (!$config->isConfigured()) {
            return null;
        }

        $configKey = $siteId->getConfigKey();
        if (!$config->isSiteConfigured($configKey)) {
            return null;
        }

        $data = [
            'siteId'      => $siteId,
            'config'      => $config,
            'piwikSiteId' => $config->getPiwikSiteId($siteId->getConfigKey()),
            'piwikUrl'    => $config->getPiwikUrl()
        ];

        $blocks = $this->buildCodeBlocks($config, $siteId);

        $template = '@PimcoreCore/Analytics/Tracking/Piwik/trackingCode.html.twig';

        $event = new TrackingDataEvent($config, $siteId, $data, $blocks, $template);
        $this->eventDispatcher->dispatch(PiwikEvents::CODE_TRACKING_DATA, $event);

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
        $configKey     = $siteId->getConfigKey();
        $trackerConfig = $config->getConfigForSite($configKey);
        $blockData     = $this->buildBlockData($trackerConfig, $config, $siteId);

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

    private function buildBlockData(ConfigObject $trackerConfig, Config $config, SiteId $siteId): array
    {
        $blockData = [];

        if (!empty($trackerConfig->code_before_init)) {
            $blockData[self::BLOCK_BEFORE_SCRIPT] = $trackerConfig->code_before_init;
        }

        if (!empty($trackerConfig->code_before_track)) {
            $blockData[self::BLOCK_BEFORE_TRACK] = $trackerConfig->code_before_track;
        }

        $blockData[self::BLOCK_TRACK] = [
            "_paq.push(['trackPageView']);",
            "_paq.push(['enableLinkTracking']);",
        ];

        if (!empty($trackerConfig->code_after_track)) {
            $blockData[self::BLOCK_AFTER_TRACK] = $trackerConfig->code_after_track;
        }

        $blockData[self::BLOCK_BEFORE_ASYNC] = [
            "_paq.push(['setTrackerUrl', u+'piwik.php']);",
            sprintf("_paq.push(['setSiteId', '%d']);", $config->getPiwikSiteId($siteId->getConfigKey()))
        ];

        return $blockData;
    }
}
