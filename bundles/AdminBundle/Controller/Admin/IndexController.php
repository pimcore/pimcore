<?php

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\AdminBundle\Controller\Admin;

use Pimcore\Analytics\Google\Config\SiteConfigProvider;
use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Pimcore\Bundle\AdminBundle\Security\CsrfProtectionHandler;
use Pimcore\Config;
use Pimcore\Controller\KernelResponseEventInterface;
use Pimcore\Db\ConnectionInterface;
use Pimcore\Event\Admin\IndexActionSettingsEvent;
use Pimcore\Event\AdminEvents;
use Pimcore\Extension\Bundle\PimcoreBundleManager;
use Pimcore\Google;
use Pimcore\Maintenance\Executor;
use Pimcore\Maintenance\ExecutorInterface;
use Pimcore\Model\Element\Service;
use Pimcore\Model\User;
use Pimcore\Tool;
use Pimcore\Tool\Admin;
use Pimcore\Tool\Session;
use Pimcore\Version;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
class IndexController extends AdminController implements KernelResponseEventInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @Route("/", name="pimcore_admin_index", methods={"GET"})
     *
     * @param Request $request
     * @param SiteConfigProvider $siteConfigProvider
     * @param KernelInterface $kernel
     * @param Executor $maintenanceExecutor
     * @param CsrfProtectionHandler $csrfProtection
     * @param Config $config
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function indexAction(
        Request $request,
        SiteConfigProvider $siteConfigProvider,
        KernelInterface $kernel,
        Executor $maintenanceExecutor,
        CsrfProtectionHandler $csrfProtection,
        Config $config
    ) {
        $user = $this->getAdminUser();
        $templateParams = [
            'config' => $config,
        ];

        $this
            ->addRuntimePerspective($templateParams, $user)
            ->addPluginAssets($templateParams);

        $this->buildPimcoreSettings($request, $templateParams, $user, $kernel, $maintenanceExecutor, $csrfProtection, $siteConfigProvider);

        if ($user->getTwoFactorAuthentication('required') && !$user->getTwoFactorAuthentication('enabled')) {
            // only one login is allowed to setup 2FA by the user himself
            $user->setTwoFactorAuthentication('enabled', true);
            // disable the 2FA prompt for the current session
            Tool\Session::useSession(function (AttributeBagInterface $adminSession) {
                $adminSession->set('2fa_required', false);
            });

            $user->save();

            $templateParams['settings']['twoFactorSetupRequired'] = true;
        }

        // allow to alter settings via an event
        $settingsEvent = new IndexActionSettingsEvent($templateParams);
        $this->eventDispatcher->dispatch($settingsEvent, AdminEvents::INDEX_ACTION_SETTINGS);
        $templateParams = $settingsEvent->getSettings();

        return $this->render('@PimcoreAdmin/Admin/Index/index.html.twig', $templateParams);
    }

    /**
     * @Route("/index/statistics", name="pimcore_admin_index_statistics", methods={"GET"})
     *
     * @param Request $request
     * @param ConnectionInterface $db
     * @param KernelInterface $kernel
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function statisticsAction(Request $request, ConnectionInterface $db, KernelInterface $kernel)
    {
        // DB
        try {
            $tables = $db->fetchAll('SELECT TABLE_NAME as name,TABLE_ROWS as `rows` from information_schema.TABLES
                WHERE TABLE_ROWS IS NOT NULL AND TABLE_SCHEMA = ?', [$db->getDatabase()]);
        } catch (\Exception $e) {
            $tables = [];
        }

        try {
            $mysqlVersion = $db->fetchOne('SELECT VERSION()');
        } catch (\Exception $e) {
            $mysqlVersion = null;
        }

        try {
            $data = [
                'instanceId' => $this->getInstanceId(),
                'pimcore_major_version' => 10,
                'pimcore_version' => Version::getVersion(),
                'pimcore_hash' => Version::getRevision(),
                'php_version' => PHP_VERSION,
                'mysql_version' => $mysqlVersion,
                'bundles' => array_keys($kernel->getBundles()),
                'tables' => $tables,
            ];
        } catch (\Exception $e) {
            $data = [];
        }

        return $this->adminJson($data);
    }

    /**
     * @param array $templateParams
     * @param User $user
     *
     * @return $this
     */
    protected function addRuntimePerspective(array &$templateParams, User $user)
    {
        $runtimePerspective = Config::getRuntimePerspective($user);
        $templateParams['runtimePerspective'] = $runtimePerspective;

        return $this;
    }

    /**
     * @param array $templateParams
     *
     * @return $this
     */
    protected function addPluginAssets(array &$templateParams)
    {
        $bundleManager = $this->get(PimcoreBundleManager::class);

        $templateParams['pluginJsPaths'] = $bundleManager->getJsPaths();
        $templateParams['pluginCssPaths'] = $bundleManager->getCssPaths();

        return $this;
    }

    /**
     * @param Request $request
     * @param array $templateParams
     * @param User $user
     * @param KernelInterface $kernel
     * @param ExecutorInterface $maintenanceExecutor
     * @param CsrfProtectionHandler $csrfProtection
     * @param SiteConfigProvider $siteConfigProvider
     *
     * @return $this
     */
    protected function buildPimcoreSettings(Request $request, array &$templateParams, User $user, KernelInterface $kernel, ExecutorInterface $maintenanceExecutor, CsrfProtectionHandler $csrfProtection, SiteConfigProvider $siteConfigProvider)
    {
        $config = $templateParams['config'];
        $dashboardHelper = new \Pimcore\Helper\Dashboard($user);

        $settings = [
            'instanceId' => $this->getInstanceId(),
            'version' => Version::getVersion(),
            'build' => Version::getRevision(),
            'debug' => \Pimcore::inDebugMode(),
            'devmode' => \Pimcore::inDevMode(),
            'disableMinifyJs' => \Pimcore::disableMinifyJs(),
            'environment' => $kernel->getEnvironment(),
            'cached_environments' => Tool::getCachedSymfonyEnvironments(),
            'sessionId' => htmlentities(Session::getSessionId(), ENT_QUOTES, 'UTF-8'),

            // languages
            'language' => $request->getLocale(),
            'websiteLanguages' => Admin::reorderWebsiteLanguages(
                $this->getAdminUser(),
                $config['general']['valid_languages'],
                true
            ),

            // flags
            'showCloseConfirmation' => true,
            'debug_admin_translations' => (bool)$config['general']['debug_admin_translations'],
            'document_generatepreviews' => (bool)$config['documents']['generate_preview'],
            'asset_disable_tree_preview' => (bool)$config['assets']['disable_tree_preview'],
            'htmltoimage' => \Pimcore\Image\HtmlToImage::isSupported(),
            'videoconverter' => \Pimcore\Video::isAvailable(),
            'asset_hide_edit' => (bool)$config['assets']['hide_edit_image'],
            'main_domain' => $config['general']['domain'],
            'timezone' => $config['general']['timezone'],
            'tile_layer_url_template' => $config['maps']['tile_layer_url_template'],
            'geocoding_url_template' => $config['maps']['geocoding_url_template'],
            'reverse_geocoding_url_template' => $config['maps']['reverse_geocoding_url_template'],
            'asset_tree_paging_limit' => $config['assets']['tree_paging_limit'],
            'document_tree_paging_limit' => $config['documents']['tree_paging_limit'],
            'object_tree_paging_limit' => $config['objects']['tree_paging_limit'],
            'maxmind_geoip_installed' => (bool) $this->getParameter('pimcore.geoip.db_file'),
            'hostname' => htmlentities(\Pimcore\Tool::getHostname(), ENT_QUOTES, 'UTF-8'),

            'document_auto_save_interval' => $config['documents']['auto_save_interval'],
            'object_auto_save_interval' => $config['objects']['auto_save_interval'],

            // perspective and portlets
            'perspective' => $templateParams['runtimePerspective'],
            'availablePerspectives' => Config::getAvailablePerspectives($user),
            'disabledPortlets' => $dashboardHelper->getDisabledPortlets(),

            // google analytics
            'google_analytics_enabled' => (bool) $siteConfigProvider->isSiteReportingConfigured(),
        ];

        $this
            ->addSystemVarSettings($settings)
            ->addMaintenanceSettings($settings, $maintenanceExecutor)
            ->addMailSettings($settings, $config)
            ->addCustomViewSettings($settings);

        $settings['csrfToken'] = $csrfProtection->getCsrfToken();

        $templateParams['settings'] = $settings;

        return $this;
    }

    /**
     * @return string
     */
    private function getInstanceId()
    {
        $instanceId = 'not-set';
        try {
            $instanceId = $this->getParameter('secret');
            $instanceId = sha1(substr($instanceId, 3, -3));
        } catch (\Exception $e) {
            // nothing to do
        }

        return $instanceId;
    }

    /**
     * @param array $settings
     *
     * @return $this
     */
    protected function addSystemVarSettings(array &$settings)
    {
        // upload limit
        $max_upload = filesize2bytes(ini_get('upload_max_filesize') . 'B');
        $max_post = filesize2bytes(ini_get('post_max_size') . 'B');
        $upload_mb = min($max_upload, $max_post);

        $settings['upload_max_filesize'] = (int) $upload_mb;

        // session lifetime (gc)
        $session_gc_maxlifetime = ini_get('session.gc_maxlifetime');
        if (empty($session_gc_maxlifetime)) {
            $session_gc_maxlifetime = 120;
        }

        $settings['session_gc_maxlifetime'] = (int)$session_gc_maxlifetime;

        return $this;
    }

    /**
     * @param array $settings
     * @param ExecutorInterface $maintenanceExecutor
     *
     * @return $this
     */
    protected function addMaintenanceSettings(array &$settings, ExecutorInterface $maintenanceExecutor)
    {
        // check maintenance
        $maintenance_active = false;
        if ($lastExecution = $maintenanceExecutor->getLastExecution()) {
            if ((time() - $lastExecution) < 3660) { // maintenance script should run at least every hour + a little tolerance
                $maintenance_active = true;
            }
        }

        $settings['maintenance_active'] = $maintenance_active;
        $settings['maintenance_mode'] = Admin::isInMaintenanceMode();

        return $this;
    }

    /**
     * @param array $settings
     * @param Config $config
     *
     * @return $this
     */
    protected function addMailSettings(array &$settings, $config)
    {
        //mail settings
        $mailIncomplete = false;
        if (isset($config['email'])) {
            if (empty($config['email']['debug']['email_addresses'])) {
                $mailIncomplete = true;
            }
            if (empty($config['email']['sender']['email'])) {
                $mailIncomplete = true;
            }
            if (($config['email']['method'] ?? '') == 'smtp' && empty($config['email']['smtp']['host'])) {
                $mailIncomplete = true;
            }
        }

        $settings['mail'] = !$mailIncomplete;
        $settings['mailDefaultAddress'] = $config['email']['sender']['email'] ?? null;

        return $this;
    }

    /**
     * @param array $settings
     *
     * @return $this
     */
    protected function addCustomViewSettings(array &$settings)
    {
        $cvData = [];

        // still needed when publishing objects
        $cvConfig = Tool::getCustomViewConfig();

        if ($cvConfig) {
            foreach ($cvConfig as $node) {
                $tmpData = $node;
                // backwards compatibility
                $treeType = $tmpData['treetype'] ? $tmpData['treetype'] : 'object';
                $rootNode = Service::getElementByPath($treeType, $tmpData['rootfolder']);

                if ($rootNode) {
                    $tmpData['rootId'] = $rootNode->getId();
                    $tmpData['allowedClasses'] = $tmpData['classes'] ?? null;
                    $tmpData['showroot'] = (bool)$tmpData['showroot'];

                    // Check if a user has privileges to that node
                    if ($rootNode->isAllowed('list')) {
                        $cvData[] = $tmpData;
                    }
                }
            }
        }

        $settings['customviews'] = $cvData;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function onKernelResponseEvent(ResponseEvent $event)
    {
        $event->getResponse()->headers->set('X-Frame-Options', 'deny', true);
    }
}
