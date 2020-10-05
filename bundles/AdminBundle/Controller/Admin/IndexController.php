<?php
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

namespace Pimcore\Bundle\AdminBundle\Controller\Admin;

use Linfo;
use Pimcore\Analytics\Google\Config\SiteConfigProvider;
use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Bundle\AdminBundle\EventListener\CsrfProtectionListener;
use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Pimcore\Config;
use Pimcore\Controller\Configuration\TemplatePhp;
use Pimcore\Controller\EventedControllerInterface;
use Pimcore\Db\ConnectionInterface;
use Pimcore\Event\Admin\IndexActionSettingsEvent;
use Pimcore\Event\Admin\IndexSettingsEvent;
use Pimcore\Event\AdminEvents;
use Pimcore\Google;
use Pimcore\Maintenance\Executor;
use Pimcore\Maintenance\ExecutorInterface;
use Pimcore\Model\Element\Service;
use Pimcore\Model\User;
use Pimcore\Templating\Model\ViewModel;
use Pimcore\Tool;
use Pimcore\Tool\Admin;
use Pimcore\Tool\Session;
use Pimcore\Version;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AdminController implements EventedControllerInterface
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
     * @TemplatePhp()
     *
     * @param Request $request
     * @param SiteConfigProvider $siteConfigProvider
     * @param KernelInterface $kernel
     * @param Executor $maintenanceExecutor
     * @param CsrfProtectionListener $csrfProtectionListener
     * @param Config $config
     *
     * @return ViewModel
     *
     * @throws \Exception
     */
    public function indexAction(
        Request $request,
        SiteConfigProvider $siteConfigProvider,
        KernelInterface $kernel,
        Executor $maintenanceExecutor,
        CsrfProtectionListener $csrfProtectionListener,
        Config $config
    ) {
        $user = $this->getAdminUser();
        $view = new ViewModel([
            'config' => $config,
        ]);

        $this
            ->addRuntimePerspective($view, $user)
            ->addReportConfig($view)
            ->addPluginAssets($view);

        $settings = $this->buildPimcoreSettings($request, $view, $user, $kernel, $maintenanceExecutor, $csrfProtectionListener);
        $this->buildGoogleAnalyticsSettings($view, $settings, $siteConfigProvider);

        if ($user->getTwoFactorAuthentication('required') && !$user->getTwoFactorAuthentication('enabled')) {
            // only one login is allowed to setup 2FA by the user himself
            $user->setTwoFactorAuthentication('enabled', true);
            // disable the 2FA prompt for the current session
            Tool\Session::useSession(function (AttributeBagInterface $adminSession) {
                $adminSession->set('2fa_required', false);
            });

            $user->save();
            $settings->getParameters()->add([
                'twoFactorSetupRequired' => true,
            ]);
        }

        // allow to alter settings via an event
        $settingsEvent = new IndexActionSettingsEvent($settings->getAllParameters());
        $this->eventDispatcher->dispatch(AdminEvents::INDEX_ACTION_SETTINGS, $settingsEvent);
        $settings->initialize($settingsEvent->getSettings());

        $this->eventDispatcher->dispatch(AdminEvents::INDEX_SETTINGS, new IndexSettingsEvent($settings));

        $view->settings = $settings;

        return $view;
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
        $mysqlVersion = null;
        try {
            $tables = $db->fetchAll('SELECT TABLE_NAME as name,TABLE_ROWS as rows from information_schema.TABLES
                WHERE TABLE_ROWS IS NOT NULL AND TABLE_SCHEMA = ?', [$db->getDatabase()]);

            $mysqlVersion = $db->fetchOne('SELECT VERSION()');
        } catch (\Exception $e) {
            $tables = [];
        }

        // System
        try {
            $linfo = new Linfo\Linfo([
                'show' => [
                    'os' => true,
                    'ram' => true,
                    'cpu' => true,
                    'virtualization' => true,
                    'distro' => true,
                ],
            ]);
            $linfo->scan();
            $systemData = $linfo->getInfo();
            $system = [
                'OS' => $systemData['OS'],
                'Distro' => $systemData['Distro'],
                'RAMTotal' => $systemData['RAM']['total'],
                'CPUCount' => count($systemData['CPU']),
                'CPUModel' => $systemData['CPU'][0]['Model'],
                'CPUClock' => $systemData['CPU'][0]['MHz'],
                'virtualization' => $systemData['virtualization'],
            ];
        } catch (\Exception $e) {
            $system = [];
        }

        try {
            $data = [
                'instanceId' => $this->getInstanceId(),
                'pimcore_version' => Version::getVersion(),
                'pimcore_hash' => Version::getRevision(),
                'php_version' => PHP_VERSION,
                'mysql_version' => $mysqlVersion,
                'bundles' => array_keys($kernel->getBundles()),
                'system' => $system,
                'tables' => $tables,
            ];
        } catch (\Exception $e) {
            $data = [];
        }

        return $this->adminJson($data);
    }

    /**
     * @param ViewModel $view
     * @param User $user
     *
     * @return $this
     */
    protected function addRuntimePerspective(ViewModel $view, User $user)
    {
        $runtimePerspective = Config::getRuntimePerspective($user);

        $view->runtimePerspective = $runtimePerspective;

        return $this;
    }

    /**
     * @param ViewModel $view
     *
     * @return $this
     */
    protected function addReportConfig(ViewModel $view)
    {
        // TODO where is this used?
        $view->report_config = Config::getReportConfig();

        return $this;
    }

    /**
     * @param ViewModel $view
     *
     * @return $this
     */
    protected function addPluginAssets(ViewModel $view)
    {
        $bundleManager = $this->get('pimcore.extension.bundle_manager');

        $view->pluginJsPaths = $bundleManager->getJsPaths();
        $view->pluginCssPaths = $bundleManager->getCssPaths();

        return $this;
    }

    /**
     * @param Request $request
     * @param ViewModel $view
     * @param User $user
     * @param KernelInterface $kernel
     * @param ExecutorInterface $maintenanceExecutor
     * @param CsrfProtectionListener $csrfProtectionListener
     *
     * @return ViewModel
     */
    protected function buildPimcoreSettings(Request $request, ViewModel $view, User $user, KernelInterface $kernel, ExecutorInterface $maintenanceExecutor, CsrfProtectionListener $csrfProtectionListener)
    {
        $config = $view->config;
        $settings = new ViewModel([
            'instanceId' => $this->getInstanceId(),
            'version' => Version::getVersion(),
            'build' => Version::getRevision(),
            'debug' => \Pimcore::inDebugMode(),
            'devmode' => \Pimcore::inDevMode(),
            'disableMinifyJs' => \Pimcore::disableMinifyJs(),
            'environment' => $kernel->getEnvironment(),
            'cached_environments' => Tool::getCachedSymfonyEnvironments(),
            'sessionId' => htmlentities(Session::getSessionId(), ENT_QUOTES, 'UTF-8'),
        ]);

        // languages
        $settings->getParameters()->add([
            'language' => $request->getLocale(),
            'websiteLanguages' => Admin::reorderWebsiteLanguages(
                $this->getAdminUser(),
                $config['general']['valid_languages'],
                true
            ),
        ]);

        // flags
        $namingStrategy = $this->get('pimcore.document.tag.naming.strategy');

        $settings->getParameters()->add([
            'showCloseConfirmation' => true,
            'debug_admin_translations' => (bool)$config['general']['debug_admin_translations'],
            'document_generatepreviews' => (bool)$config['documents']['generate_preview'],
            'document_naming_strategy' => $namingStrategy->getName(),
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
        ]);

        $dashboardHelper = new \Pimcore\Helper\Dashboard($user);

        // perspective and portlets
        $settings->getParameters()->add([
            'perspective' => $view->runtimePerspective,
            'availablePerspectives' => Config::getAvailablePerspectives($user),
            'disabledPortlets' => $dashboardHelper->getDisabledPortlets(),
        ]);

        $this
            ->addSystemVarSettings($settings)
            ->addMaintenanceSettings($settings, $maintenanceExecutor)
            ->addMailSettings($settings, $config)
            ->addCustomViewSettings($settings);

        $settings->csrfToken = $csrfProtectionListener->getCsrfToken();

        return $settings;
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

    private function buildGoogleAnalyticsSettings(
        ViewModel $view,
        ViewModel $settings,
        SiteConfigProvider $siteConfigProvider
    ) {
        $config = $view->config;

        $settings->getParameters()->add([
            'google_analytics_enabled' => (bool)$siteConfigProvider->isSiteReportingConfigured(),
            'google_webmastertools_enabled' => (bool)Google\Webmastertools::isConfigured(),
        ]);
    }

    /**
     * @param ViewModel $settings
     *
     * @return $this
     */
    protected function addSystemVarSettings(ViewModel $settings)
    {
        // upload limit
        $max_upload = filesize2bytes(ini_get('upload_max_filesize') . 'B');
        $max_post = filesize2bytes(ini_get('post_max_size') . 'B');
        $upload_mb = min($max_upload, $max_post);

        $settings->upload_max_filesize = (int)$upload_mb;

        // session lifetime (gc)
        $session_gc_maxlifetime = ini_get('session.gc_maxlifetime');
        if (empty($session_gc_maxlifetime)) {
            $session_gc_maxlifetime = 120;
        }

        $settings->session_gc_maxlifetime = (int)$session_gc_maxlifetime;

        return $this;
    }

    /**
     * @param ViewModel $settings
     * @param ExecutorInterface $maintenanceExecutor
     *
     * @return $this
     */
    protected function addMaintenanceSettings(ViewModel $settings, ExecutorInterface $maintenanceExecutor)
    {
        // check maintenance
        $maintenance_active = false;
        if ($lastExecution = $maintenanceExecutor->getLastExecution()) {
            if ((time() - $lastExecution) < 3660) { // maintenance script should run at least every hour + a little tolerance
                $maintenance_active = true;
            }
        }

        $settings->maintenance_active = $maintenance_active;
        $settings->maintenance_mode = Admin::isInMaintenanceMode();

        return $this;
    }

    /**
     * @param ViewModel $settings
     * @param Config $config
     *
     * @return $this
     */
    protected function addMailSettings(ViewModel $settings, $config)
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

        $settings->mail = !$mailIncomplete;
        $settings->mailDefaultAddress = $config['email']['sender']['email'] ?? null;

        return $this;
    }

    /**
     * @param ViewModel $settings
     *
     * @return $this
     */
    protected function addCustomViewSettings(ViewModel $settings)
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
                    $tmpData['allowedClasses'] = isset($tmpData['classes']) && $tmpData['classes'] ? explode(',', $tmpData['classes']) : null;
                    $tmpData['showroot'] = (bool)$tmpData['showroot'];

                    // Check if a user has privileges to that node
                    if ($rootNode->isAllowed('list')) {
                        $cvData[] = $tmpData;
                    }
                }
            }
        }

        $settings->customviews = $cvData;

        return $this;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $event->getResponse()->headers->set('X-Frame-Options', 'deny', true);
    }
}
