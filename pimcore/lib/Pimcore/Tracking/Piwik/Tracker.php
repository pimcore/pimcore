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
use Pimcore\Tracking\CodeBlock;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Templating\EngineInterface;

class Tracker
{
    const BLOCK_BEFORE_SCRIPT_TAG = 'beforeScriptTag';
    const BLOCK_AFTER_SCRIPT_TAG = 'afterScriptTag';
    const BLOCK_BEFORE_SCRIPT = 'beforeScript';
    const BLOCK_AFTER_SCRIPT = 'afterScript';
    const BLOCK_BEFORE_ASYNC = 'beforeAsync';
    const BLOCK_AFTER_ASYNC = 'afterAsync';
    const BLOCK_ACTIONS = 'actions';

    const POSITION_PREPEND = 'prepend';
    const POSITION_APPEND = 'append';

    const CONFIG_KEY_MAIN_DOMAIN = 'default';

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var EngineInterface
     */
    private $templatingEngine;

    /**
     * @var SiteResolver
     */
    private $siteResolver;

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

    /**
     * @var array
     */
    private $additionalCode = [];

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        EngineInterface $templatingEngine,
        SiteResolver $siteResolver
    )
    {
        $this->eventDispatcher  = $eventDispatcher;
        $this->templatingEngine = $templatingEngine;
        $this->siteResolver     = $siteResolver;
    }

    /**
     * Get code for the current site if any/fall back to main domain
     *
     * @param Site|null $site
     *
     * @return null|string Null if no tracking is configured
     */
    public function getCode(Site $site = null)
    {
        if (null !== $site) {
            return $this->getSiteCode($site);
        } elseif ($this->siteResolver->isSiteRequest()) {
            $site = $this->siteResolver->getSite();

            return $this->getSiteCode($site);
        }

        return $this->getMainCode();
    }

    /**
     * Get code for main domain
     *
     * @return null|string
     */
    public function getMainCode()
    {
        return $this->generateCode(self::CONFIG_KEY_MAIN_DOMAIN);
    }

    /**
     * Get code for a specific site
     *
     * @param Site $site
     *
     * @return null|string
     */
    public function getSiteCode(Site $site)
    {
        return $this->generateCode($this->getSiteConfigKey($site), $site);
    }

    /**
     * Adds additional code to the tracker
     *
     * @param string $code  The code to add
     * @param string $block The block where to add the code
     * @param bool $prepend Whether to prepend the code to the code block
     * @param Site|string|null $config
     */
    public function addAdditionalCode(string $code, string $block = self::BLOCK_ACTIONS, bool $prepend = false, $config = null)
    {
        if (!in_array($block, $this->blocks)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid block "%s". Valid values are %s',
                $block,
                implode(', ', $this->blocks)
            ));
        }

        $configKey = $this->getConfigKey($config);
        if (!isset($this->additionalCode[$configKey])) {
            $this->additionalCode[$configKey] = [];
        }

        if (!isset($this->additionalCode[$configKey][$block])) {
            $this->additionalCode[$configKey][$block] = [];
        }

        $position = $prepend ? self::POSITION_PREPEND : self::POSITION_APPEND;
        if (!isset($this->additionalCode[$configKey][$block][$position])) {
            $this->additionalCode[$configKey][$block][$position] = [];
        }

        $this->additionalCode[$configKey][$block][$position][] = $code;
    }

    private function generateCode(string $configKey = self::CONFIG_KEY_MAIN_DOMAIN, Site $site = null)
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
            $this->addAdditionalBlockCalls($configKey, $block, $codeBlock);

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

    private function addAdditionalBlockCalls(string $configKey, string $block, CodeBlock $codeBlock)
    {
        if (!isset($this->additionalCode[$configKey])) {
            return;
        }

        $blockCalls = $this->additionalCode[$configKey][$block] ?? [];
        if (empty($blockCalls)) {
            return;
        }

        foreach ([self::POSITION_PREPEND, self::POSITION_APPEND] as $position) {
            if (isset($blockCalls[$position])) {
                if (self::POSITION_PREPEND === $position) {
                    $codeBlock->prepend($blockCalls[$position]);
                } else {
                    $codeBlock->append($blockCalls[$position]);
                }
            }
        }
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

    /**
     * Get config key from an input which can either be a string key or a Site. If nothing is given
     * the current site will be resolved.
     *
     * @param Site|string|null $config
     *
     * @return string
     */
    private function getConfigKey($config = null): string
    {
        $configKey = null;
        if (null !== $config) {
            if ($config instanceof Site) {
                $configKey = $this->getSiteConfigKey($config);
            } else {
                $configKey = (string)$config;
            }
        } else {
            $configKey = self::CONFIG_KEY_MAIN_DOMAIN;
            if ($this->siteResolver->isSiteRequest()) {
                $configKey = $this->getSiteConfigKey($this->siteResolver->getSite());
            }
        }

        return $configKey;
    }

    private function getSiteConfigKey(Site $site): string
    {
        return sprintf('site_%s', $site->getId());
    }
}
