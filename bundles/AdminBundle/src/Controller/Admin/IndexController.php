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

namespace Pimcore\Bundle\AdminBundle\Controller\Admin;

use Doctrine\DBAL\Connection;
use Exception;
use Pimcore\Analytics\Google\Config\SiteConfigProvider;
use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Pimcore\Bundle\AdminBundle\Security\CsrfProtectionHandler;
use Pimcore\Config;
use Pimcore\Controller\KernelResponseEventInterface;
use Pimcore\Event\Admin\IndexActionSettingsEvent;
use Pimcore\Event\AdminEvents;
use Pimcore\Maintenance\Executor;
use Pimcore\Maintenance\ExecutorInterface;
use Pimcore\Model\DataObject\ClassDefinition\CustomLayout;
use Pimcore\Model\Document\DocType;
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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
class IndexController extends AdminController implements KernelResponseEventInterface
{
    private EventDispatcherInterface $eventDispatcher;

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
    ): Response {
        $user = $this->getAdminUser();
        $perspectiveConfig = new \Pimcore\Perspective\Config();
        $templateParams = [
            'config' => $config,
            'perspectiveConfig' => $perspectiveConfig,
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
        $settingsEvent = new IndexActionSettingsEvent($templateParams['settings'] ?? []);
        $this->eventDispatcher->dispatch($settingsEvent, AdminEvents::INDEX_ACTION_SETTINGS);
        $templateParams['settings'] = $settingsEvent->getSettings();

        return $this->render('@PimcoreAdmin/admin/index/index.html.twig', $templateParams);
    }

    /**
     * @Route("/index/statistics", name="pimcore_admin_index_statistics", methods={"GET"})
     *
     * @param Request $request
     * @param Connection $db
     * @param KernelInterface $kernel
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function statisticsAction(Request $request, Connection $db, KernelInterface $kernel): JsonResponse
    {
        // DB
        try {
            $tables = $db->fetchAllAssociative('SELECT TABLE_NAME as name,TABLE_ROWS as `rows` from information_schema.TABLES
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
                'pimcore_major_version' => Version::getMajorVersion(),
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

    protected function addRuntimePerspective(array &$templateParams, User $user): static
    {
        $runtimePerspective = \Pimcore\Perspective\Config::getRuntimePerspective($user);
        $templateParams['runtimePerspective'] = $runtimePerspective;

        return $this;
    }

    protected function addPluginAssets(array &$templateParams): static
    {
        $templateParams['pluginJsPaths'] = $this->bundleManager->getJsPaths();
        $templateParams['pluginCssPaths'] = $this->bundleManager->getCssPaths();

        return $this;
    }

    protected function buildPimcoreSettings(Request $request, array &$templateParams, User $user, KernelInterface $kernel, ExecutorInterface $maintenanceExecutor, CsrfProtectionHandler $csrfProtection, SiteConfigProvider $siteConfigProvider): static
    {
        $config                = $templateParams['config'];
        $dashboardHelper       = new \Pimcore\Helper\Dashboard($user);
        $customAdminEntrypoint = $this->getParameter('pimcore_admin.custom_admin_route_name');

        try {
            $adminEntrypointUrl = $this->generateUrl($customAdminEntrypoint, [], UrlGeneratorInterface::ABSOLUTE_URL);
        } catch (Exception) {
            // if the custom admin entrypoint is not defined, return null in the settings
            $adminEntrypointUrl = null;
        }

        $settings = [
            'instanceId'          => $this->getInstanceId(),
            'version'             => Version::getVersion(),
            'build'               => Version::getRevision(),
            'debug'               => \Pimcore::inDebugMode(),
            'devmode'             => \Pimcore::inDevMode(),
            'disableMinifyJs'     => \Pimcore::disableMinifyJs(),
            'environment'         => $kernel->getEnvironment(),
            'cached_environments' => Tool::getCachedSymfonyEnvironments(),
            'sessionId'           => htmlentities(Session::getSessionId(), ENT_QUOTES, 'UTF-8'),

            // languages
            'language'         => $request->getLocale(),
            'websiteLanguages' => Admin::reorderWebsiteLanguages(
                $this->getAdminUser(),
                $config['general']['valid_languages'],
                true
            ),

            // flags
            'showCloseConfirmation'          => true,
            'debug_admin_translations'       => (bool)$config['general']['debug_admin_translations'],
            'document_generatepreviews'      => (bool)$config['documents']['generate_preview'],
            'asset_disable_tree_preview'     => (bool)$config['assets']['disable_tree_preview'],
            'chromium'                       => \Pimcore\Image\Chromium::isSupported(),
            'videoconverter'                 => \Pimcore\Video::isAvailable(),
            'asset_hide_edit'                => (bool)$config['assets']['hide_edit_image'],
            'main_domain'                    => $config['general']['domain'],
            'custom_admin_entrypoint_url'    => $adminEntrypointUrl,
            'timezone'                       => $config['general']['timezone'],
            'tile_layer_url_template'        => $config['maps']['tile_layer_url_template'],
            'geocoding_url_template'         => $config['maps']['geocoding_url_template'],
            'reverse_geocoding_url_template' => $config['maps']['reverse_geocoding_url_template'],
            'asset_tree_paging_limit'        => $config['assets']['tree_paging_limit'],
            'document_tree_paging_limit'     => $config['documents']['tree_paging_limit'],
            'object_tree_paging_limit'       => $config['objects']['tree_paging_limit'],
            'maxmind_geoip_installed'        => (bool) $this->getParameter('pimcore.geoip.db_file'),
            'hostname'                       => htmlentities(\Pimcore\Tool::getHostname(), ENT_QUOTES, 'UTF-8'),

            'document_auto_save_interval' => $config['documents']['auto_save_interval'],
            'object_auto_save_interval'   => $config['objects']['auto_save_interval'],

            // perspective and portlets
            'perspective'           => $templateParams['runtimePerspective'],
            'availablePerspectives' => \Pimcore\Perspective\Config::getAvailablePerspectives($user),
            'disabledPortlets'      => $dashboardHelper->getDisabledPortlets(),

            // google analytics
            'google_analytics_enabled' => (bool) $siteConfigProvider->isSiteReportingConfigured(),

            // this stuff is used to decide whether the "add" button should be grayed out or not
            'image-thumbnails-writeable'          => (new \Pimcore\Model\Asset\Image\Thumbnail\Config())->isWriteable(),
            'video-thumbnails-writeable'          => (new \Pimcore\Model\Asset\Video\Thumbnail\Config())->isWriteable(),
            'document-types-writeable'            => (new DocType())->isWriteable(),
            'web2print-writeable'                 => \Pimcore\Web2Print\Config::isWriteable(),
            'predefined-properties-writeable'     => (new \Pimcore\Model\Property\Predefined())->isWriteable(),
            'predefined-asset-metadata-writeable' => (new \Pimcore\Model\Metadata\Predefined())->isWriteable(),
            'perspectives-writeable'              => \Pimcore\Perspective\Config::isWriteable(),
            'custom-views-writeable'              => \Pimcore\CustomView\Config::isWriteable(),
            'class-definition-writeable'          => isset($_SERVER['PIMCORE_CLASS_DEFINITION_WRITABLE']) ? (bool)$_SERVER['PIMCORE_CLASS_DEFINITION_WRITABLE'] : true,
            'object-custom-layout-writeable' => (new CustomLayout())->isWriteable(),
        ];

        $this
            ->addSystemVarSettings($settings)
            ->addMaintenanceSettings($settings, $maintenanceExecutor)
            ->addMailSettings($settings, $config)
            ->addCustomViewSettings($settings)
            ->addNotificationSettings($settings, $config);

        $settings['csrfToken'] = $csrfProtection->getCsrfToken();

        $templateParams['settings'] = $settings;

        return $this;
    }

    private function getInstanceId(): string
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

    protected function addSystemVarSettings(array &$settings): static
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

    protected function addMaintenanceSettings(array &$settings, ExecutorInterface $maintenanceExecutor): static
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

    protected function addMailSettings(array &$settings, Config $config): static
    {
        //mail settings
        $mailIncomplete = false;
        if (isset($config['email'])) {
            if (\Pimcore::inDebugMode() && empty($config['email']['debug']['email_addresses'])) {
                $mailIncomplete = true;
            }
            if (empty($config['email']['sender']['email'])) {
                $mailIncomplete = true;
            }
        }

        $settings['mail'] = !$mailIncomplete;
        $settings['mailDefaultAddress'] = $config['email']['sender']['email'] ?? null;

        return $this;
    }

    protected function addCustomViewSettings(array &$settings): static
    {
        $cvData = [];

        // still needed when publishing objects
        $cvConfig = \Pimcore\CustomView\Config::get();

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
     * @return $this
     */
    protected function addNotificationSettings(array &$settings, Config $config): static
    {
        $enabled = (bool)$config['notifications']['enabled'];

        $settings['notifications_enabled'] = $enabled;
        $settings['checknewnotification_enabled'] = $enabled && (bool) $config['notifications']['check_new_notification']['enabled'];

        // convert the config parameter interval (seconds) in milliseconds
        $settings['checknewnotification_interval'] = $config['notifications']['check_new_notification']['interval'] * 1000;

        return $this;
    }

    public function onKernelResponseEvent(ResponseEvent $event): void
    {
        $event->getResponse()->headers->set('X-Frame-Options', 'deny', true);
    }
}
