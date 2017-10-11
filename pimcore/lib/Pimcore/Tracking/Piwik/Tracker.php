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

namespace Pimcore\Tracking\Piwik;

use Pimcore\Config\Config;
use Pimcore\Event\Tracking\Piwik\TrackingDataEvent;
use Pimcore\Event\Tracking\PiwikEvents;
use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Model\Site;
use Pimcore\Tracking\AbstractTracker;
use Pimcore\Tracking\CodeBlock;
use Pimcore\Tracking\CodeContainer;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Templating\EngineInterface;

class Tracker extends AbstractTracker
{
    const BLOCK_BEFORE_SCRIPT_TAG = 'beforeScriptTag';
    const BLOCK_AFTER_SCRIPT_TAG = 'afterScriptTag';
    const BLOCK_BEFORE_SCRIPT = 'beforeScript';
    const BLOCK_AFTER_SCRIPT = 'afterScript';
    const BLOCK_BEFORE_ASYNC = 'beforeAsync';
    const BLOCK_AFTER_ASYNC = 'afterAsync';
    const BLOCK_ACTIONS = 'actions';

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var EngineInterface
     */
    private $templatingEngine;

    /**
     * @var CodeContainer
     */
    private $codeContainer;

    /**
     * @var array
     */
    private $blocks = [
        self::BLOCK_BEFORE_SCRIPT_TAG,
        self::BLOCK_BEFORE_SCRIPT,
        self::BLOCK_ACTIONS,
        self::BLOCK_BEFORE_ASYNC,
        self::BLOCK_AFTER_ASYNC,
        self::BLOCK_AFTER_SCRIPT,
        self::BLOCK_AFTER_SCRIPT_TAG,
    ];

    public function __construct(
        SiteResolver $siteResolver,
        EventDispatcherInterface $eventDispatcher,
        EngineInterface $templatingEngine
    )
    {
        parent::__construct($siteResolver);

        $this->eventDispatcher  = $eventDispatcher;
        $this->templatingEngine = $templatingEngine;
        $this->codeContainer    = new CodeContainer(self::BLOCK_ACTIONS, $this->blocks);
    }

    protected function getCodeContainer(): CodeContainer
    {
        return $this->codeContainer;
    }

    protected function generateCode(string $configKey = self::CONFIG_KEY_MAIN_DOMAIN, Site $site = null)
    {
        $reportConfig = \Pimcore\Config::getReportConfig();
        if (!$reportConfig->piwik) {
            return null;
        }

        $config     = $reportConfig->piwik;
        $siteConfig = $config->sites->$configKey;

        if (!$siteConfig) {
            return null;
        }

        if (empty($config->piwik_url)) {
            return null;
        }

        if (empty($siteConfig->site_id)) {
            return null;
        }

        $data = $this->buildTemplateData($configKey, $config, $siteConfig, $site);
        $code = $this->templatingEngine->render(
            '@PimcoreCore/Tracking/Piwik/trackingCode.html.twig',
            $data
        );

        $code = trim($code);

        return $code;
    }

    private function buildTemplateData(string $configKey, Config $config, Config $siteConfig, Site $site = null): array
    {
        $siteId   = (int)$siteConfig->site_id;
        $piwikUrl = (string)$config->piwik_url;

        $data = [
            'site'       => $site,
            'config'     => $config,
            'siteConfig' => $siteConfig,
            'siteId'     => $siteId,
            'piwikUrl'   => $piwikUrl,
        ];

        $blocks = [];
        foreach ($this->blocks as $block) {
            $calls = [];

            if (self::BLOCK_BEFORE_SCRIPT === $block && !empty($siteConfig->code_before_init)) {
                $calls = [
                    $siteConfig->code_before_init
                ];
            }

            if (self::BLOCK_ACTIONS === $block) {
                $calls = $this->generateActionCalls($siteConfig);
            }

            if (self::BLOCK_BEFORE_ASYNC === $block) {
                $calls = $this->generateBeforeAsyncCalls($piwikUrl, $siteId);
            }

            $codeBlock = new CodeBlock($calls);
            $this->codeContainer->addToCodeBlock($configKey, $block, $codeBlock);

            $blocks[$block] = $codeBlock;
        }

        $trackingDataEvent = new TrackingDataEvent($data, $blocks, $config, $siteConfig, $site);
        $this->eventDispatcher->dispatch(PiwikEvents::CODE_TRACKING_DATA, $trackingDataEvent);

        $trackingData = $trackingDataEvent->getData();
        $trackingData['blocks'] = [];

        /** @var CodeBlock $codeBlock */
        foreach ($trackingDataEvent->getBlocks() as $block => $codeBlock) {
            $trackingData['blocks'][$block] = $codeBlock->asString();
        }

        return $trackingData;
    }

    private function generateActionCalls(Config $siteConfig): array
    {
        $calls = [
            "_paq.push(['trackPageView']);",
            "_paq.push(['enableLinkTracking']);",
        ];

        if (!empty($siteConfig->code_before_track)) {
            array_unshift($calls, $siteConfig->code_before_track);
        }

        if (!empty($siteConfig->code_after_track)) {
            $calls[] = $siteConfig->code_after_track;
        }

        return $calls;
    }

    private function generateBeforeAsyncCalls(string $piwikUrl, int $siteId): array
    {
        return [
            "var u='//" . $piwikUrl . "/';",
            "_paq.push(['setTrackerUrl', u+'piwik.php']);",
            sprintf("_paq.push(['setSiteId', '%d']);", $siteId)
        ];
    }
}
