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

namespace Pimcore\Analytics\GoogleTagManager;

use Pimcore\Analytics\AbstractTracker;
use Pimcore\Analytics\Code\CodeBlock;
use Pimcore\Analytics\Code\CodeCollector;
use Pimcore\Analytics\GoogleTagManager\Config\Config;
use Pimcore\Analytics\GoogleTagManager\Config\ConfigProvider;
use Pimcore\Analytics\GoogleTagManager\Event\TrackingDataEvent;
use Pimcore\Analytics\SiteId\SiteId;
use Pimcore\Analytics\SiteId\SiteIdProvider;
use Pimcore\Config\Config as ConfigObject;
use Pimcore\Event\Analytics\GoogleAnalyticsEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Templating\EngineInterface;

class Tracker extends AbstractTracker
{
    const BLOCK_TRACK = 'track';

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
     * @var array
     */
    private $blocks = [
        self::BLOCK_TRACK,
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

    protected function buildCodeCollector(): CodeCollector
    {
        return new CodeCollector($this->blocks, self::BLOCK_TRACK);
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
            'siteConfig' => $siteConfig,
            'containerId' => $siteConfig->containerId,
        ];

        $blocks = $this->buildCodeBlocks($siteId, $siteConfig);

        $template = '@PimcoreCore/Analytics/Tracking/GoogleTagManager/dataLayer.html.twig';

        $event = new TrackingDataEvent($config, $siteId, $data, $blocks, $template);
        $this->eventDispatcher->dispatch(GoogleAnalyticsEvents::CODE_TRACKING_DATA, $event);

        return $this->renderTemplate($event);
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
