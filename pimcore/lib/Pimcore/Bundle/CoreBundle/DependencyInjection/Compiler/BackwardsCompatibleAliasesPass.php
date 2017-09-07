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

namespace Pimcore\Bundle\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;

/**
 * Adds aliases for services which were renamed to Symfony 3.3 (fully qualified class name) format
 */
class BackwardsCompatibleAliasesPass implements CompilerPassInterface
{
    private $mapping = [
        'pimcore.extension.config' => \Pimcore\Extension\Config::class,
        'pimcore.extension.bundle_manager' => \Pimcore\Extension\Bundle\PimcoreBundleManager::class,

        'pimcore.maintenance.schedule_manager' => \Pimcore\Model\Schedule\Manager\Procedural::class,

        'pimcore.bundle_locator' => \Pimcore\HttpKernel\BundleLocator\BundleLocator::class,
        'pimcore.web_path_resolver' => \Pimcore\Service\WebPathResolver::class,
        'pimcore.tool.assets_installer' => \Pimcore\Tool\AssetsInstaller::class,

        'pimcore.controller.config.config_normalizer' => \Pimcore\Controller\Config\ConfigNormalizer::class,
        'pimcore.controller.config.controller_data_provider' => \Pimcore\Controller\Config\ControllerDataProvider::class,

        'pimcore.locale' => \Pimcore\Service\Locale::class,
        'pimcore.locale.intl_formatter' => \Pimcore\Service\IntlFormatterService::class,

        // aliases for event listeners exposing methods (e.g. can be disabled)
        'pimcore.event_listener.workflow_management' => \Pimcore\Bundle\CoreBundle\EventListener\WorkflowManagementListener::class,
        'pimcore.event_listener.frontend.google_analytics_code' => \Pimcore\Bundle\CoreBundle\EventListener\Frontend\GoogleAnalyticsCodeListener::class,
        'pimcore.event_listener.frontend.cookie_policy_notice' => \Pimcore\Bundle\CoreBundle\EventListener\Frontend\CookiePolicyNoticeListener::class,
        'pimcore.event_listener.frontend.google_tag_manager' => \Pimcore\Bundle\CoreBundle\EventListener\Frontend\GoogleTagManagerListener::class,
        'pimcore.event_listener.frontend.tag_manager' => \Pimcore\Bundle\CoreBundle\EventListener\Frontend\TagManagerListener::class,
        'pimcore.event_listener.frontend.targeting' => \Pimcore\Bundle\CoreBundle\EventListener\Frontend\TargetingListener::class,
        'pimcore.event_listener.frontend.full_page_cache' => \Pimcore\Bundle\CoreBundle\EventListener\Frontend\FullPageCacheListener::class,

        // templating helpers
        'pimcore.templating.action_renderer' => \Pimcore\Templating\Renderer\ActionRenderer::class,
        'pimcore.templating.include_renderer' => \Pimcore\Templating\Renderer\IncludeRenderer::class,
        'pimcore.templating.tag_renderer' => \Pimcore\Templating\Renderer\TagRenderer::class,
        'pimcore.templating.view_helper.action' => \Pimcore\Templating\Helper\Action::class,
        'pimcore.templating.view_helper.get_param' => \Pimcore\Templating\Helper\GetParam::class,
        'pimcore.templating.view_helper.get_all_params' => \Pimcore\Templating\Helper\GetAllParams::class,
        'pimcore.templating.view_helper.glossary' => \Pimcore\Templating\Helper\Glossary::class,
        'pimcore.templating.view_helper.inc' => \Pimcore\Templating\Helper\Inc::class,
        'pimcore.templating.view_helper.pimcore_url' => \Pimcore\Templating\Helper\PimcoreUrl::class,
        'pimcore.templating.view_helper.placeholder' => \Pimcore\Templating\Helper\Placeholder::class,
        'pimcore.templating.view_helper.head_title' => \Pimcore\Templating\Helper\HeadTitle::class,
        'pimcore.templating.view_helper.head_link' => \Pimcore\Templating\Helper\HeadLink::class,
        'pimcore.templating.view_helper.head_script' => \Pimcore\Templating\Helper\HeadScript::class,
        'pimcore.templating.view_helper.inline_script' => \Pimcore\Templating\Helper\InlineScript::class,
        'pimcore.templating.view_helper.head_style' => \Pimcore\Templating\Helper\HeadStyle::class,
        'pimcore.templating.view_helper.head_meta' => \Pimcore\Templating\Helper\HeadMeta::class,
        'pimcore.templating.view_helper.device' => \Pimcore\Templating\Helper\Device::class,
        'pimcore.templating.view_helper.cache' => \Pimcore\Templating\Helper\Cache::class,
        'pimcore.templating.view_helper.navigation' => \Pimcore\Templating\Helper\Navigation::class,
    ];

    public function process(ContainerBuilder $container)
    {
        foreach ($this->mapping as $alias => $service) {
            if ($container->has($alias)) {
                throw new LogicException(sprintf(
                    'The service "%1$s" is already defined. Can\'t alias "%1$s" to "%2$s"',
                    $alias, $service
                ));
            }

            $container->setAlias($alias, $service);
        }
    }
}
