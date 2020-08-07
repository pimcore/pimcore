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

use MatthiasMullie\Minify\JS;
use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Config;
use Pimcore\Controller\Config\ControllerDataProvider;
use Pimcore\Controller\Configuration\TemplatePhp;
use Pimcore\Db;
use Pimcore\File;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Logger;
use Pimcore\Tool;
use Pimcore\Translation\Translator;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * @Route("/misc")
 */
class MiscController extends AdminController
{
    /**
     * @Route("/get-available-modules", name="pimcore_admin_misc_getavailablemodules", methods={"GET"})
     *
     * @param ControllerDataProvider $provider
     *
     * @return JsonResponse
     */
    public function getAvailableModulesAction(ControllerDataProvider $provider)
    {
        // convert to normal array
        $bundles = array_values($provider->getBundles());

        $result = array_map(function (BundleInterface $bundle) {
            return [
                'name' => $bundle->getName(),
            ];
        }, $bundles);

        sort($result);

        return $this->adminJson([
            'data' => $result,
        ]);
    }

    /**
     * @Route("/get-available-controllers", name="pimcore_admin_misc_getavailablecontrollers", methods={"GET"})
     *
     * @param Request $request
     * @param ControllerDataProvider $provider
     *
     * @return JsonResponse
     */
    public function getAvailableControllersAction(Request $request, ControllerDataProvider $provider)
    {
        $routingDefaults = $this->getParameter('pimcore.routing.defaults');
        $bundle = $request->get('moduleName');
        $controllers = $provider->getControllers($bundle, $routingDefaults['bundle']);

        $result = array_map(function ($controller) {
            return [
                'name' => $controller,
            ];
        }, $controllers);

        sort($result);

        return $this->adminJson([
            'data' => $result,
        ]);
    }

    /**
     * @Route("/get-available-actions", name="pimcore_admin_misc_getavailableactions", methods={"GET"})
     *
     * @param Request $request
     * @param ControllerDataProvider $provider
     *
     * @return JsonResponse
     */
    public function getAvailableActionsAction(Request $request, ControllerDataProvider $provider)
    {
        $routingDefaults = $this->getParameter('pimcore.routing.defaults');
        $bundle = $request->get('moduleName');
        if (empty($bundle)) {
            $bundle = $routingDefaults['bundle'];
        }

        $controller = $request->get('controllerName');
        if (empty($controller)) {
            $controller = $routingDefaults['controller'];
        }

        $actions = $provider->getActions($controller, $bundle);

        $result = array_map(function ($action) {
            return [
                'name' => $action,
            ];
        }, $actions);

        sort($result);

        return $this->adminJson([
            'data' => $result,
        ]);
    }

    /**
     * @Route("/get-available-templates", name="pimcore_admin_misc_getavailabletemplates", methods={"GET"})
     *
     * @param ControllerDataProvider $provider
     *
     * @return JsonResponse
     */
    public function getAvailableTemplatesAction(ControllerDataProvider $provider)
    {
        $templates = $provider->getTemplates();

        $result = array_map(function ($template) {
            return [
                'path' => $template,
            ];
        }, $templates);

        sort($result);

        return $this->adminJson([
            'data' => $result,
        ]);
    }

    /**
     * @Route("/json-translations-system", name="pimcore_admin_misc_jsontranslationssystem", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function jsonTranslationsSystemAction(Request $request)
    {
        $language = $request->get('language');

        $translator = $this->get('translator');
        $translator->lazyInitialize('admin', $language);

        $translations = $translator->getCatalogue($language)->all('admin');
        if ($language != 'en') {
            // add en as a fallback
            $translator->lazyInitialize('admin', 'en');
            foreach ($translator->getCatalogue('en')->all('admin') as $key => $value) {
                if (!isset($translations[$key]) || empty($translations[$key])) {
                    $translations[$key] = $value;
                }
            }
        }

        $caseInsensitive = $translator instanceof Translator && $translator->getCaseInsensitive();
        $response = new Response('pimcore.system_i18n = ' . $this->encodeJson($translations) . ';pimcore.system_i18n_case_insensitive='. json_encode($caseInsensitive));
        $response->headers->set('Content-Type', 'text/javascript');

        return $response;
    }

    /**
     * @Route("/script-proxy", name="pimcore_admin_misc_scriptproxy", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function scriptProxyAction(Request $request)
    {
        $allowedFileTypes = ['js', 'css'];
        $scripts = explode(',', $request->get('scripts'));

        if ($request->get('scriptPath')) {
            $scriptPath = PIMCORE_WEB_ROOT . $request->get('scriptPath');
        } else {
            $scriptPath = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/';
        }

        $scriptsContent = '';
        foreach ($scripts as $script) {
            $filePath = $scriptPath . $script;
            if (is_file($filePath) && is_readable($filePath) && in_array(\Pimcore\File::getFileExtension($script), $allowedFileTypes)) {
                $scriptsContent .= file_get_contents($filePath);
            }
        }

        if (!empty($scriptsContent)) {
            $fileExtension = \Pimcore\File::getFileExtension($scripts[0]);
            $contentType = 'text/javascript';
            if ($fileExtension == 'css') {
                $contentType = 'text/css';
            }

            $lifetime = 86400;

            $response = new Response($scriptsContent);
            $response->headers->set('Cache-Control', 'max-age=' . $lifetime);
            $response->headers->set('Pragma', '');
            $response->headers->set('Content-Type', $contentType);
            $response->headers->set('Expires', gmdate('D, d M Y H:i:s', time() + $lifetime) . ' GMT');

            return $response;
        } else {
            throw $this->createNotFoundException('Scripts not found');
        }
    }

    /**
     * @Route("/admin-css", name="pimcore_admin_misc_admincss", methods={"GET"})
     *
     * @param Request $request
     * @param Config $config
     *
     * @return Response
     */
    public function adminCssAction(Request $request, Config $config)
    {
        // customviews config
        $cvData = Tool::getCustomViewConfig();

        $response = $this->render('PimcoreAdminBundle:Admin/Misc:admin-css.html.php', ['customviews' => $cvData, 'config' => $config]);
        $response->headers->set('Content-Type', 'text/css; charset=UTF-8');

        return $response;
    }

    /**
     * @Route("/ping", name="pimcore_admin_misc_ping", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function pingAction(Request $request)
    {
        $response = [
            'success' => true,
        ];

        return $this->adminJson($response);
    }

    /**
     * @Route("/available-languages", name="pimcore_admin_misc_availablelanguages", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function availableLanguagesAction(Request $request)
    {
        $locales = Tool::getSupportedLocales();
        $response = new Response('pimcore.available_languages = ' . $this->encodeJson($locales) . ';');
        $response->headers->set('Content-Type', 'text/javascript');

        return $response;
    }

    /**
     * @Route("/get-valid-filename", name="pimcore_admin_misc_getvalidfilename", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getValidFilenameAction(Request $request)
    {
        return $this->adminJson([
            'filename' => \Pimcore\Model\Element\Service::getValidKey($request->get('value'), $request->get('type')),
        ]);
    }

    /* FILEEXPLORER */

    /**
     * @Route("/fileexplorer-tree", name="pimcore_admin_misc_fileexplorertree", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function fileexplorerTreeAction(Request $request)
    {
        $this->checkPermission('fileexplorer');
        $referencePath = $this->getFileexplorerPath($request, 'node');

        $items = scandir($referencePath);
        $contents = [];

        foreach ($items as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            $file = $referencePath . '/' . $item;
            $file = str_replace('//', '/', $file);

            if (is_dir($file) || is_file($file)) {
                $itemConfig = [
                    'id' => '/fileexplorer' . str_replace(PIMCORE_PROJECT_ROOT, '', $file),
                    'text' => $item,
                    'leaf' => true,
                    'writeable' => is_writable($file),
                ];

                if (is_dir($file)) {
                    $itemConfig['leaf'] = false;
                    $itemConfig['type'] = 'folder';
                    if (is_dir_empty($file)) {
                        $itemConfig['loaded'] = true;
                    }
                    $itemConfig['expandable'] = true;
                } elseif (is_file($file)) {
                    $itemConfig['type'] = 'file';
                }

                $contents[] = $itemConfig;
            }
        }

        return $this->adminJson($contents);
    }

    /**
     * @Route("/fileexplorer-content", name="pimcore_admin_misc_fileexplorercontent", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function fileexplorerContentAction(Request $request)
    {
        $this->checkPermission('fileexplorer');

        $success = false;
        $writeable = false;
        $file = $this->getFileexplorerPath($request, 'path');
        $content = null;
        if (is_file($file)) {
            if (is_readable($file)) {
                $content = file_get_contents($file);
                $success = true;
                $writeable = is_writeable($file);
            }
        }

        return $this->adminJson([
            'success' => $success,
            'content' => $content,
            'writeable' => $writeable,
            'filename' => basename($file),
            'path' => preg_replace('@^' . preg_quote(PIMCORE_PROJECT_ROOT, '@') . '@', '', $file),
        ]);
    }

    /**
     * @Route("/fileexplorer-content-save", name="pimcore_admin_misc_fileexplorercontentsave", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function fileexplorerContentSaveAction(Request $request)
    {
        $this->checkPermission('fileexplorer');

        $success = false;

        if ($request->get('content') && $request->get('path')) {
            $file = $this->getFileexplorerPath($request, 'path');
            if (is_file($file) && is_writeable($file)) {
                File::put($file, $request->get('content'));

                $success = true;
            }
        }

        return $this->adminJson([
            'success' => $success,
        ]);
    }

    /**
     * @Route("/fileexplorer-add", name="pimcore_admin_misc_fileexploreradd", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function fileexplorerAddAction(Request $request)
    {
        $this->checkPermission('fileexplorer');

        $success = false;

        if ($request->get('filename') && $request->get('path')) {
            $path = $this->getFileexplorerPath($request, 'path');
            $file = $path . '/' . $request->get('filename');

            $file = resolvePath($file);
            if (strpos($file, PIMCORE_PROJECT_ROOT) !== 0) {
                throw new \Exception('not allowed');
            }

            if (is_writeable(dirname($file))) {
                File::put($file, '');

                $success = true;
            }
        }

        return $this->adminJson([
            'success' => $success,
        ]);
    }

    /**
     * @Route("/fileexplorer-add-folder", name="pimcore_admin_misc_fileexploreraddfolder", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function fileexplorerAddFolderAction(Request $request)
    {
        $this->checkPermission('fileexplorer');

        $success = false;

        if ($request->get('filename') && $request->get('path')) {
            $path = $this->getFileexplorerPath($request, 'path');
            $file = $path . '/' . $request->get('filename');

            $file = resolvePath($file);
            if (strpos($file, PIMCORE_PROJECT_ROOT) !== 0) {
                throw new \Exception('not allowed');
            }

            if (is_writeable(dirname($file))) {
                File::mkdir($file);

                $success = true;
            }
        }

        return $this->adminJson([
            'success' => $success,
        ]);
    }

    /**
     * @Route("/fileexplorer-delete", name="pimcore_admin_misc_fileexplorerdelete", methods={"DELETE"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function fileexplorerDeleteAction(Request $request)
    {
        $this->checkPermission('fileexplorer');
        $success = false;

        if ($request->get('path')) {
            $file = $this->getFileexplorerPath($request, 'path');
            if (is_writeable($file)) {
                unlink($file);
                $success = true;
            }
        }

        return $this->adminJson([
            'success' => $success,
        ]);
    }

    /**
     * @Route("/fileexplorer-rename", name="pimcore_admin_misc_fileexplorerrename", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function fileexplorerRenameAction(Request $request)
    {
        $this->checkPermission('fileexplorer');
        $success = false;

        if ($request->get('path') && $request->get('newPath')) {
            $file = $this->getFileexplorerPath($request, 'path');
            $newFile = $this->getFileexplorerPath($request, 'newPath');

            $success = rename($file, $newFile);
        }

        return $this->adminJson([
            'success' => $success,
        ]);
    }

    /**
     * @param Request $request
     * @param string $paramName
     *
     * @return mixed|string
     *
     * @throws \Exception
     */
    private function getFileexplorerPath(Request $request, $paramName = 'node')
    {
        $path = preg_replace("/^\/fileexplorer/", '', $request->get($paramName));
        $path = resolvePath(PIMCORE_PROJECT_ROOT . $path);

        if (strpos($path, PIMCORE_PROJECT_ROOT) !== 0) {
            throw new \Exception('operation permitted, permission denied');
        }

        return $path;
    }

    /**
     * @Route("/maintenance", name="pimcore_admin_misc_maintenance", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function maintenanceAction(Request $request)
    {
        $this->checkPermission('maintenance_mode');

        if ($request->get('activate')) {
            Tool\Admin::activateMaintenanceMode(Tool\Session::getSessionId());
        }

        if ($request->get('deactivate')) {
            Tool\Admin::deactivateMaintenanceMode();
        }

        return $this->adminJson([
            'success' => true,
        ]);
    }

    /**
     * @Route("/http-error-log", name="pimcore_admin_misc_httperrorlog", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function httpErrorLogAction(Request $request)
    {
        $this->checkPermission('http_errors');

        $db = Db::get();

        $limit = intval($request->get('limit'));
        $offset = intval($request->get('start'));
        $sort = $request->get('sort');
        $dir = $request->get('dir');
        $filter = $request->get('filter');
        if (!$limit) {
            $limit = 20;
        }
        if (!$offset) {
            $offset = 0;
        }
        if (!$sort || !in_array($sort, ['code', 'uri', 'date', 'count'])) {
            $sort = 'count';
        }
        if (!$dir || !in_array($dir, ['DESC', 'ASC'])) {
            $dir = 'DESC';
        }

        $condition = '';
        if ($filter) {
            $filter = $db->quote('%' . $filter . '%');

            $conditionParts = [];
            foreach (['uri', 'code', 'parametersGet', 'parametersPost', 'serverVars', 'cookies'] as $field) {
                $conditionParts[] = $field . ' LIKE ' . $filter;
            }
            $condition = ' WHERE ' . implode(' OR ', $conditionParts);
        }

        $logs = $db->fetchAll('SELECT code,uri,`count`,date FROM http_error_log ' . $condition . ' ORDER BY ' . $sort . ' ' . $dir . ' LIMIT ' . $offset . ',' . $limit);
        $total = $db->fetchOne('SELECT count(*) FROM http_error_log ' . $condition);

        return $this->adminJson([
            'items' => $logs,
            'total' => $total,
            'success' => true,
        ]);
    }

    /**
     * @Route("/http-error-log-flush", name="pimcore_admin_misc_httperrorlogflush", methods={"DELETE"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function httpErrorLogFlushAction(Request $request)
    {
        $this->checkPermission('http_errors');

        $db = Db::get();
        $db->query('TRUNCATE TABLE http_error_log');

        return $this->adminJson([
            'success' => true,
        ]);
    }

    /**
     * @Route("/http-error-log-detail", name="pimcore_admin_misc_httperrorlogdetail", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function httpErrorLogDetailAction(Request $request)
    {
        $this->checkPermission('http_errors');

        $db = Db::get();
        $data = $db->fetchRow('SELECT * FROM http_error_log WHERE uri = ?', [$request->get('uri')]);

        foreach ($data as $key => &$value) {
            if (in_array($key, ['parametersGet', 'parametersPost', 'serverVars', 'cookies'])) {
                $value = unserialize($value);
            }
        }

        $response = $this->render('PimcoreAdminBundle:Admin/Misc:http-error-log-detail.html.php', ['data' => $data]);

        return $response;
    }

    /**
     * @Route("/country-list", name="pimcore_admin_misc_countrylist", methods={"GET"})
     *
     * @param LocaleServiceInterface $localeService
     *
     * @return JsonResponse
     */
    public function countryListAction(LocaleServiceInterface $localeService)
    {
        $countries = $localeService->getDisplayRegions();
        asort($countries);
        $options = [];

        foreach ($countries as $short => $translation) {
            if (strlen($short) == 2) {
                $options[] = [
                    'name' => $translation,
                    'code' => $short,
                ];
            }
        }

        return $this->adminJson(['data' => $options]);
    }

    /**
     * @Route("/language-list", name="pimcore_admin_misc_languagelist", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function languageListAction(Request $request)
    {
        $locales = Tool::getSupportedLocales();
        $options = [];

        foreach ($locales as $short => $translation) {
            $options[] = [
                'name' => $translation,
                'code' => $short,
            ];
        }

        return $this->adminJson(['data' => $options]);
    }

    /**
     * @Route("/phpinfo", name="pimcore_admin_misc_phpinfo", methods={"GET"})
     *
     * @param Request $request
     * @param Profiler $profiler
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function phpinfoAction(Request $request, ?Profiler $profiler)
    {
        if ($profiler) {
            $profiler->disable();
        }

        if (!$this->getAdminUser()->isAdmin()) {
            throw new \Exception('Permission denied');
        }

        ob_start();
        phpinfo();
        $content = ob_get_clean();

        return new Response($content);
    }

    /**
     * @Route("/get-language-flag", name="pimcore_admin_misc_getlanguageflag", methods={"GET"})
     *
     * @param Request $request
     *
     * @return BinaryFileResponse
     */
    public function getLanguageFlagAction(Request $request)
    {
        $iconPath = Tool::getLanguageFlagFile($request->get('language'));
        $response = new BinaryFileResponse($iconPath);
        $response->headers->set('Content-Type', 'image/svg+xml');

        return $response;
    }

    /**
     * @Route("/icon-list", name="pimcore_admin_misc_iconlist", methods={"GET"})
     * @TemplatePhp()
     *
     * @param Request $request
     * @param Profiler $profiler
     */
    public function iconListAction(Request $request, ?Profiler $profiler)
    {
        if ($profiler) {
            $profiler->disable();
        }
    }

    /**
     * @Route("/test", name="pimcore_admin_misc_test")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function testAction(Request $request)
    {
        return new Response('done');
    }


    /**
     * @Route("/pimcoreInternalScripts", name="pimcore_admin_pimcore_internal_scripts", methods={"GET"})
     *
     * @throws \Exception
     */
    public function pimcoreInternalScriptsAction(Request $request) {

        $bundleManager = $this->get('pimcore.extension.bundle_manager');

        $pluginJsPaths = $bundleManager->getJsPaths();
        $locale = \Pimcore::getContainer()->get('pimcore.locale')->getLocale();

        $scripts = [

            "/bundles/pimcoreadmin/js/lib/ext-plugins/portlet/PortalDropZone.js",
            "/bundles/pimcoreadmin/js/lib/ext-plugins/portlet/Portlet.js",
            "/bundles/pimcoreadmin/js/lib/ext-plugins/portlet/PortalColumn.js",
            "/bundles/pimcoreadmin/js/lib/ext-plugins/portlet/PortalPanel.js",

            "/bundles/pimcoreadmin/js/lib/node_modules/@sencha/ext-classic-locale/overrides/" . $locale . "/ext-locale-" . $locale . ".js",

            // runtime
            "/bundles/pimcoreadmin/js/pimcore/functions.js",
            "/bundles/pimcoreadmin/js/pimcore/common.js",
            "/bundles/pimcoreadmin/js/pimcore/elementservice.js",
            "/bundles/pimcoreadmin/js/pimcore/helpers.js",
            "/bundles/pimcoreadmin/js/pimcore/error.js",

            "/bundles/pimcoreadmin/js/pimcore/treenodelocator.js",
            "/bundles/pimcoreadmin/js/pimcore/helpers/generic-grid.js",
            "/bundles/pimcoreadmin/js/pimcore/helpers/quantityValue.js",
            "/bundles/pimcoreadmin/js/pimcore/overrides.js",

            "/bundles/pimcoreadmin/js/pimcore/perspective.js",
            "/bundles/pimcoreadmin/js/pimcore/user.js",

            // tools
            "/bundles/pimcoreadmin/js/pimcore/tool/paralleljobs.js",
            "/bundles/pimcoreadmin/js/pimcore/tool/genericiframewindow.js",

            // settings
            "/bundles/pimcoreadmin/js/pimcore/settings/user/panels/abstract.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/user/panel.js",

            "/bundles/pimcoreadmin/js/pimcore/settings/user/usertab.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/user/editorSettings.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/user/websiteTranslationSettings.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/user/role/panel.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/user/role/tab.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/user/user/objectrelations.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/user/user/settings.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/user/user/keyBindings.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/user/workspaces.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/user/workspace/asset.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/user/workspace/document.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/user/workspace/object.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/user/workspace/customlayouts.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/user/workspace/language.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/user/workspace/special.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/user/role/settings.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/profile/panel.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/profile/twoFactorSettings.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/thumbnail/item.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/thumbnail/panel.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/videothumbnail/item.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/videothumbnail/panel.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/translations.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/translationEditor.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/translation/website.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/translation/admin.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/translation/translationmerger.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/translation/xliff.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/translation/word.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/metadata/predefined.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/properties/predefined.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/docTypes.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/system.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/web2print.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/website.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/staticroutes.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/redirects.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/glossary.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/recyclebin.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/fileexplorer/file.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/fileexplorer/explorer.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/maintenance.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/robotstxt.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/httpErrorLog.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/email/log.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/email/blacklist.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/targeting/condition/abstract.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/targeting/conditions.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/targeting/action/abstract.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/targeting/actions.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/targeting/rules/panel.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/targeting/rules/item.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/targeting/targetGroups/panel.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/targeting/targetGroups/item.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/targeting_toolbar.js",

            "/bundles/pimcoreadmin/js/pimcore/settings/gdpr/gdprPanel.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/gdpr/dataproviders/assets.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/gdpr/dataproviders/dataObjects.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/gdpr/dataproviders/sentMail.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/gdpr/dataproviders/pimcoreUsers.js",

            // element
            "/bundles/pimcoreadmin/js/pimcore/element/abstract.js",
            "/bundles/pimcoreadmin/js/pimcore/element/selector/abstract.js",
            "/bundles/pimcoreadmin/js/pimcore/element/selector/selector.js",
            "/bundles/pimcoreadmin/js/pimcore/element/selector/document.js",
            "/bundles/pimcoreadmin/js/pimcore/element/selector/asset.js",
            "/bundles/pimcoreadmin/js/pimcore/element/properties.js",
            "/bundles/pimcoreadmin/js/pimcore/element/scheduler.js",
            "/bundles/pimcoreadmin/js/pimcore/element/dependencies.js",
            "/bundles/pimcoreadmin/js/pimcore/element/metainfo.js",
            "/bundles/pimcoreadmin/js/pimcore/element/history.js",
            "/bundles/pimcoreadmin/js/pimcore/element/notes.js",
            "/bundles/pimcoreadmin/js/pimcore/element/note_details.js",
            "/bundles/pimcoreadmin/js/pimcore/element/workflows.js",
            "/bundles/pimcoreadmin/js/pimcore/element/tag/imagecropper.js",
            "/bundles/pimcoreadmin/js/pimcore/element/tag/imagehotspotmarkereditor.js",
            "/bundles/pimcoreadmin/js/pimcore/element/replace_assignments.js",
            "/bundles/pimcoreadmin/js/pimcore/element/permissionchecker.js",
            "/bundles/pimcoreadmin/js/pimcore/element/gridexport/abstract.js",
            "/bundles/pimcoreadmin/js/pimcore/element/helpers/gridColumnConfig.js",
            "/bundles/pimcoreadmin/js/pimcore/element/helpers/gridConfigDialog.js",
            "/bundles/pimcoreadmin/js/pimcore/element/helpers/gridCellEditor.js",
            "/bundles/pimcoreadmin/js/pimcore/element/helpers/gridTabAbstract.js",
            "/bundles/pimcoreadmin/js/pimcore/object/helpers/grid.js",
            "/bundles/pimcoreadmin/js/pimcore/object/helpers/gridConfigDialog.js",
            "/bundles/pimcoreadmin/js/pimcore/object/helpers/import/csvPreviewTab.js",
            "/bundles/pimcoreadmin/js/pimcore/object/helpers/import/columnConfigurationTab.js",
            "/bundles/pimcoreadmin/js/pimcore/object/helpers/import/resolverSettingsTab.js",
            "/bundles/pimcoreadmin/js/pimcore/object/helpers/import/csvSettingsTab.js",
            "/bundles/pimcoreadmin/js/pimcore/object/helpers/import/saveAndShareTab.js",
            "/bundles/pimcoreadmin/js/pimcore/object/helpers/import/configDialog.js",
            "/bundles/pimcoreadmin/js/pimcore/object/helpers/import/reportTab.js",
            "/bundles/pimcoreadmin/js/pimcore/object/helpers/classTree.js",
            "/bundles/pimcoreadmin/js/pimcore/object/helpers/gridTabAbstract.js",
            "/bundles/pimcoreadmin/js/pimcore/object/helpers/metadataMultiselectEditor.js",
            "/bundles/pimcoreadmin/js/pimcore/object/helpers/customLayoutEditor.js",
            "/bundles/pimcoreadmin/js/pimcore/object/helpers/optionEditor.js",
            "/bundles/pimcoreadmin/js/pimcore/object/helpers/imageGalleryDropZone.js",
            "/bundles/pimcoreadmin/js/pimcore/object/helpers/imageGalleryPanel.js",
            "/bundles/pimcoreadmin/js/pimcore/element/selector/object.js",
            "/bundles/pimcoreadmin/js/pimcore/element/tag/configuration.js",
            "/bundles/pimcoreadmin/js/pimcore/element/tag/assignment.js",
            "/bundles/pimcoreadmin/js/pimcore/element/tag/tree.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/helpers/metadataTree.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/helpers/gridConfigDialog.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/helpers/gridTabAbstract.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/helpers/grid.js",

            // documents
            "/bundles/pimcoreadmin/js/pimcore/document/properties.js",
            "/bundles/pimcoreadmin/js/pimcore/document/document.js",
            "/bundles/pimcoreadmin/js/pimcore/document/page_snippet.js",
            "/bundles/pimcoreadmin/js/pimcore/document/edit.js",
            "/bundles/pimcoreadmin/js/pimcore/document/versions.js",
            "/bundles/pimcoreadmin/js/pimcore/document/settings_abstract.js",
            "/bundles/pimcoreadmin/js/pimcore/document/pages/settings.js",
            "/bundles/pimcoreadmin/js/pimcore/document/pages/preview.js",
            "/bundles/pimcoreadmin/js/pimcore/document/snippets/settings.js",
            "/bundles/pimcoreadmin/js/pimcore/document/emails/settings.js",
            "/bundles/pimcoreadmin/js/pimcore/document/newsletters/settings.js",
            "/bundles/pimcoreadmin/js/pimcore/document/newsletters/sendingPanel.js",
            "/bundles/pimcoreadmin/js/pimcore/document/newsletters/plaintextPanel.js",
            "/bundles/pimcoreadmin/js/pimcore/document/newsletters/addressSourceAdapters/default.js",
            "/bundles/pimcoreadmin/js/pimcore/document/newsletters/addressSourceAdapters/csvList.js",
            "/bundles/pimcoreadmin/js/pimcore/document/newsletters/addressSourceAdapters/report.js",
            "/bundles/pimcoreadmin/js/pimcore/document/link.js",
            "/bundles/pimcoreadmin/js/pimcore/document/hardlink.js",
            "/bundles/pimcoreadmin/js/pimcore/document/folder.js",
            "/bundles/pimcoreadmin/js/pimcore/document/tree.js",
            "/bundles/pimcoreadmin/js/pimcore/document/snippet.js",
            "/bundles/pimcoreadmin/js/pimcore/document/email.js",
            "/bundles/pimcoreadmin/js/pimcore/document/newsletter.js",
            "/bundles/pimcoreadmin/js/pimcore/document/page.js",
            "/bundles/pimcoreadmin/js/pimcore/document/printpages/pdf_preview.js",
            "/bundles/pimcoreadmin/js/pimcore/document/printabstract.js",
            "/bundles/pimcoreadmin/js/pimcore/document/printpage.js",
            "/bundles/pimcoreadmin/js/pimcore/document/printcontainer.js",
            "/bundles/pimcoreadmin/js/pimcore/document/seopanel.js",
            "/bundles/pimcoreadmin/js/pimcore/document/document_language_overview.js",
            "/bundles/pimcoreadmin/js/pimcore/document/customviews/tree.js",

            // assets
            "/bundles/pimcoreadmin/js/pimcore/asset/metadata/data/data.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/metadata/data/input.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/metadata/data/textarea.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/metadata/data/asset.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/metadata/data/document.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/metadata/data/object.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/metadata/data/date.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/metadata/data/checkbox.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/metadata/data/select.js",

            "/bundles/pimcoreadmin/js/pimcore/asset/metadata/tags/abstract.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/metadata/tags/checkbox.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/metadata/tags/date.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/metadata/tags/input.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/metadata/tags/manyToOneRelation.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/metadata/tags/asset.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/metadata/tags/document.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/metadata/tags/object.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/metadata/tags/select.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/metadata/tags/textarea.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/asset.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/unknown.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/embedded_meta_data.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/image.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/document.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/video.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/audio.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/text.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/folder.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/listfolder.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/versions.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/metadata/grid.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/tree.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/customviews/tree.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/gridexport/xlsx.js",
            "/bundles/pimcoreadmin/js/pimcore/asset/gridexport/csv.js",

            // object
            "/bundles/pimcoreadmin/js/pimcore/object/helpers/edit.js",
            "/bundles/pimcoreadmin/js/pimcore/object/helpers/layout.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/class.js",
            "/bundles/pimcoreadmin/js/pimcore/object/class.js",
            "/bundles/pimcoreadmin/js/pimcore/object/bulk-base.js",
            "/bundles/pimcoreadmin/js/pimcore/object/bulk-export.js",
            "/bundles/pimcoreadmin/js/pimcore/object/bulk-import.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/data.js",          // THIS MUST BE THE FIRST FILE, DO NOT MOVE THIS DOWN !!!
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/block.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/classificationstore.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/rgbaColor.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/date.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/datetime.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/encryptedField.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/time.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/manyToOneRelation.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/image.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/externalImage.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/hotspotimage.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/imagegallery.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/video.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/input.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/numeric.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/manyToManyObjectRelation.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/advancedManyToManyRelation.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/advancedManyToManyObjectRelation.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/reverseManyToManyObjectRelation.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/booleanSelect.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/select.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/urlSlug.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/user.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/textarea.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/wysiwyg.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/checkbox.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/consent.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/slider.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/manyToManyRelation.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/table.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/structuredTable.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/country.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/geo/abstract.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/geopoint.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/geobounds.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/geopolygon.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/geopolyline.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/language.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/password.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/multiselect.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/link.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/fieldcollections.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/objectbricks.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/localizedfields.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/countrymultiselect.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/languagemultiselect.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/firstname.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/lastname.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/email.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/gender.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/newsletterActive.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/newsletterConfirmed.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/targetGroup.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/targetGroupMultiselect.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/quantityValue.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/inputQuantityValue.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/data/calculatedValue.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/layout/layout.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/layout/accordion.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/layout/fieldset.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/layout/fieldcontainer.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/layout/panel.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/layout/region.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/layout/tabpanel.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/layout/button.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/layout/iframe.js",
            "/bundles/pimcoreadmin/js/pimcore/object/fieldlookup/filterdialog.js",
            "/bundles/pimcoreadmin/js/pimcore/object/fieldlookup/helper.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classes/layout/text.js",
            "/bundles/pimcoreadmin/js/pimcore/object/fieldcollection.js",
            "/bundles/pimcoreadmin/js/pimcore/object/fieldcollections/field.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/Abstract.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/IsEqual.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/Text.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/Anonymizer.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/AnyGetter.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/AssetMetadataGetter.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/Arithmetic.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/Boolean.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/BooleanFormatter.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/CaseConverter.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/CharCounter.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/Concatenator.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/DateFormatter.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/ElementCounter.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/Iterator.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/JSON.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/LocaleSwitcher.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/Merge.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/ObjectFieldGetter.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/PHP.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/PHPCode.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/Base64.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/TranslateValue.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/PropertyGetter.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/RequiredBy.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/StringContains.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/StringReplace.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/Substring.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/LFExpander.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/Trimmer.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/Alias.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/WorkflowState.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/value/Href.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/value/Objects.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/value/DefaultValue.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/GeopointRenderer.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/ImageRenderer.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/HotspotimageRenderer.js",
            "/bundles/pimcoreadmin/js/pimcore/object/importcolumn/Abstract.js",
            "/bundles/pimcoreadmin/js/pimcore/object/importcolumn/operator/Base64.js",
            "/bundles/pimcoreadmin/js/pimcore/object/importcolumn/operator/Ignore.js",
            "/bundles/pimcoreadmin/js/pimcore/object/importcolumn/operator/Iterator.js",
            "/bundles/pimcoreadmin/js/pimcore/object/importcolumn/operator/LocaleSwitcher.js",
            "/bundles/pimcoreadmin/js/pimcore/object/importcolumn/operator/ObjectBrickSetter.js",
            "/bundles/pimcoreadmin/js/pimcore/object/importcolumn/operator/PHPCode.js",
            "/bundles/pimcoreadmin/js/pimcore/object/importcolumn/operator/Published.js",
            "/bundles/pimcoreadmin/js/pimcore/object/importcolumn/operator/Splitter.js",
            "/bundles/pimcoreadmin/js/pimcore/object/importcolumn/operator/Unserialize.js",
            "/bundles/pimcoreadmin/js/pimcore/object/importcolumn/value/DefaultValue.js",
            "/bundles/pimcoreadmin/js/pimcore/object/objectbrick.js",
            "/bundles/pimcoreadmin/js/pimcore/object/objectbricks/field.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/abstract.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/abstractRelations.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/block.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/rgbaColor.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/date.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/datetime.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/time.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/manyToOneRelation.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/image.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/encryptedField.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/externalImage.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/hotspotimage.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/imagegallery.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/video.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/input.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/classificationstore.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/numeric.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/manyToManyObjectRelation.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/advancedManyToManyRelation.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/FieldCollectionGetter.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridcolumn/operator/ObjectBrickGetter.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/advancedManyToManyObjectRelation.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/reverseManyToManyObjectRelation.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/urlSlug.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/booleanSelect.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/select.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/user.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/checkbox.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/consent.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/textarea.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/wysiwyg.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/slider.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/manyToManyRelation.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/table.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/structuredTable.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/country.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/geo/abstract.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/geobounds.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/geopoint.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/geopolygon.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/geopolyline.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/language.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/password.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/multiselect.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/link.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/fieldcollections.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/localizedfields.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/countrymultiselect.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/languagemultiselect.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/objectbricks.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/firstname.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/lastname.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/email.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/gender.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/newsletterActive.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/newsletterConfirmed.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/targetGroup.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/targetGroupMultiselect.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/quantityValue.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/inputQuantityValue.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tags/calculatedValue.js",
            "/bundles/pimcoreadmin/js/pimcore/object/preview.js",
            "/bundles/pimcoreadmin/js/pimcore/object/versions.js",
            "/bundles/pimcoreadmin/js/pimcore/object/variantsTab.js",
            "/bundles/pimcoreadmin/js/pimcore/object/folder/search.js",
            "/bundles/pimcoreadmin/js/pimcore/object/edit.js",
            "/bundles/pimcoreadmin/js/pimcore/object/abstract.js",
            "/bundles/pimcoreadmin/js/pimcore/object/object.js",
            "/bundles/pimcoreadmin/js/pimcore/object/folder.js",
            "/bundles/pimcoreadmin/js/pimcore/object/variant.js",
            "/bundles/pimcoreadmin/js/pimcore/object/tree.js",
            "/bundles/pimcoreadmin/js/pimcore/object/layout/iframe.js",
            "/bundles/pimcoreadmin/js/pimcore/object/customviews/tree.js",
            "/bundles/pimcoreadmin/js/pimcore/object/quantityvalue/unitsettings.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridexport/xlsx.js",
            "/bundles/pimcoreadmin/js/pimcore/object/gridexport/csv.js",

            //plugins
            "/bundles/pimcoreadmin/js/pimcore/plugin/broker.js",
            "/bundles/pimcoreadmin/js/pimcore/plugin/plugin.js",

            "/bundles/pimcoreadmin/js/pimcore/event-dispatcher.js",

            // reports
            "/bundles/pimcoreadmin/js/pimcore/report/panel.js",
            "/bundles/pimcoreadmin/js/pimcore/report/broker.js",
            "/bundles/pimcoreadmin/js/pimcore/report/abstract.js",
            "/bundles/pimcoreadmin/js/pimcore/report/settings.js",
            "/bundles/pimcoreadmin/js/pimcore/report/analytics/settings.js",
            "/bundles/pimcoreadmin/js/pimcore/report/analytics/elementoverview.js",
            "/bundles/pimcoreadmin/js/pimcore/report/analytics/elementexplorer.js",
            "/bundles/pimcoreadmin/js/pimcore/report/webmastertools/settings.js",
            "/bundles/pimcoreadmin/js/pimcore/report/tagmanager/settings.js",
            "/bundles/pimcoreadmin/js/pimcore/report/custom/item.js",
            "/bundles/pimcoreadmin/js/pimcore/report/custom/panel.js",
            "/bundles/pimcoreadmin/js/pimcore/report/custom/settings.js",
            "/bundles/pimcoreadmin/js/pimcore/report/custom/report.js",
            "/bundles/pimcoreadmin/js/pimcore/report/custom/definitions/sql.js",
            "/bundles/pimcoreadmin/js/pimcore/report/custom/definitions/analytics.js",
            "/bundles/pimcoreadmin/js/pimcore/report/custom/toolbarenricher.js",

            "/bundles/pimcoreadmin/js/pimcore/settings/tagmanagement/panel.js",
            "/bundles/pimcoreadmin/js/pimcore/settings/tagmanagement/item.js",

            "/bundles/pimcoreadmin/js/pimcore/report/qrcode/panel.js",
            "/bundles/pimcoreadmin/js/pimcore/report/qrcode/item.js",

            // extension manager
            "/bundles/pimcoreadmin/js/pimcore/extensionmanager/admin.js",

            // application logging
            "/bundles/pimcoreadmin/js/pimcore/log/admin.js",
            "/bundles/pimcoreadmin/js/pimcore/log/detailwindow.js",

            // layout
            "/bundles/pimcoreadmin/js/pimcore/layout/portal.js",
            "/bundles/pimcoreadmin/js/pimcore/layout/portlets/abstract.js",
            "/bundles/pimcoreadmin/js/pimcore/layout/portlets/modifiedDocuments.js",
            "/bundles/pimcoreadmin/js/pimcore/layout/portlets/modifiedObjects.js",
            "/bundles/pimcoreadmin/js/pimcore/layout/portlets/modifiedAssets.js",
            "/bundles/pimcoreadmin/js/pimcore/layout/portlets/modificationStatistic.js",
            "/bundles/pimcoreadmin/js/pimcore/layout/portlets/analytics.js",
            "/bundles/pimcoreadmin/js/pimcore/layout/portlets/piwik.js",
            "/bundles/pimcoreadmin/js/pimcore/layout/portlets/customreports.js",

            "/bundles/pimcoreadmin/js/pimcore/layout/toolbar.js",
            "/bundles/pimcoreadmin/js/pimcore/layout/treepanelmanager.js",
            "/bundles/pimcoreadmin/js/pimcore/document/seemode.js",

            // classification store
            "/bundles/pimcoreadmin/js/pimcore/object/classificationstore/groupsPanel.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classificationstore/propertiesPanel.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classificationstore/collectionsPanel.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classificationstore/keyDefinitionWindow.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classificationstore/keySelectionWindow.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classificationstore/relationSelectionWindow.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classificationstore/storeConfiguration.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classificationstore/storeTree.js",
            "/bundles/pimcoreadmin/js/pimcore/object/classificationstore/columnConfigDialog.js",

            //workflow
            "/bundles/pimcoreadmin/js/pimcore/workflow/transitionPanel.js",
            "/bundles/pimcoreadmin/js/pimcore/workflow/transitions.js",

            // Piwik - this needs to be loaded after treepanel manager as
            // it adds panels in pimcore ready
            "/bundles/pimcoreadmin/js/pimcore/analytics/piwik/widget_store_provider.js",
            "/bundles/pimcoreadmin/js/pimcore/report/piwik/settings.js",
            "/bundles/pimcoreadmin/js/pimcore/report/piwik/dashboard_iframe.js",

            // color picker
            "/bundles/pimcoreadmin/js/pimcore/colorpicker-overrides.js",

            //notification
            "/bundles/pimcoreadmin/js/pimcore/notification/helper.js",
            "/bundles/pimcoreadmin/js/pimcore/notification/panel.js",
            "/bundles/pimcoreadmin/js/pimcore/notification/modal.js",
            "/bundles/pimcoreadmin/js/pimcore/launcher.js",
        ];



        $scriptContents = "";
        foreach ($scripts as $scriptUrl) {
            $fullPath = PIMCORE_WEB_ROOT . $scriptUrl;
            if (is_file($fullPath)) {
                $scriptContents .= "\r\n\r\n// " . $fullPath . "\r\n";
                $scriptContents .= file_get_contents($fullPath) . "\n\n\n";
            } else {
                try {
                    $kernel = $this->container->get('http_kernel');
                    $subRequest = Request::create( $scriptUrl);
                    $response = $kernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST, false);
                    $subResponse = $response->getContent();
                    $scriptContents .= $subResponse;
                } catch (\Exception $e) {
                    Logger::error("could not find file " . $fullPath);
                }
            }
        }

        foreach ($pluginJsPaths as $scriptUrl) {
            $fullPath = PIMCORE_WEB_ROOT .  $scriptUrl;
            if (is_file($fullPath)) {
                $scriptContents .= file_get_contents($fullPath) . "\n\n\n";
            } else {
                try {
                    $kernel = $this->container->get('http_kernel');
                    $subRequest = Request::create($scriptUrl);
                    $response = $kernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST, false);
                    $subResponse = $response->getContent();
                    $scriptContents .= $subResponse;
                } catch (\Exception $e) {
                    Logger::error("could not find file " . $fullPath);
                }
            }
        }

        $lifetime = 86400;

        $response = new Response($scriptContents);
        $response->headers->set('Symfony-Session-NoAutoCacheControl', 'application/javascript');
        $response->headers->set('Cache-Control', 'max-age=' . $lifetime);
        $response->headers->set('Pragma', '');
        $response->headers->set('Expires', gmdate('D, d M Y H:i:s', time() + $lifetime) . ' GMT');
        $response->headers->set('Content-Type', 'application/javascript');
        return $response;

    }


    /**
     * @Route("/extjsEditmodeScriptsMinified.js", name="pimcore_admin_extjs_editmode_scripts_minified", methods={"GET"})
     *
     * @throws \Exception
     */
    public function extjsEditmodeScriptsMinifiedAction(Request $request, Packages $packages, RouterInterface $router) {
        $scriptContents = "";


        $scriptPath = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/minified_extjs.js';

        if (!is_file($scriptPath)) {

            $fosUrl = $packages->getUrl('bundles/fosjsrouting/js/router.js');

            $scriptContents .= file_get_contents(PIMCORE_WEB_ROOT . $fosUrl) . "\n\n\n";


            $bundleManager = $this->get('pimcore.extension.bundle_manager');
            $pluginJsPaths = $bundleManager->getEditmodeJsPaths();

            $kernel = $this->container->get('http_kernel');
            $subRequest = Request::create('/js/routing?callback=fos.Router.setData');
            $response = $kernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST, false);
            $fosResponse = $response->getContent();

            $scriptContents .= $fosResponse;


            $manifest = PIMCORE_WEB_ROOT . "/bundles/pimcoreadmin/js/pimcoreEditmode.json";
            if (is_file($manifest)) {
                $manifestContents = file_get_contents($manifest);
                $manifestContents = json_decode($manifestContents, true);

                $loadOrder = $manifestContents["loadOrder"];

                $count = 0;

                // build dependencies


                $main = $loadOrder[count($loadOrder) - 1];
                $list = [
                    $main["idx"] => $main
                ];

                $this->populate($loadOrder, $list, $main);
                ksort($list);

                // replace this with loadOrder if we want to load the entire list
                foreach ($loadOrder as $loadOrderIdx => $loadOrderItem) {
                    $count++;
                    $relativePath = $loadOrderItem["path"];
                    $fullPath = PIMCORE_WEB_ROOT . $relativePath;
                    if (is_file($fullPath)) {
                        $scriptContents .= "\r\n\r\n// " . $fullPath . "\r\n";
                        $includeContents = file_get_contents($fullPath);

                        $minify = new JS($includeContents);
                        $includeContents = $minify->minify();

                        $scriptContents .= ";\r\n";
                        $scriptContents .= $includeContents;

                    }
                }

            }
            file_put_contents($scriptPath, $scriptContents);

        } else {
            $scriptContents = file_get_contents($scriptPath);
        }


        $lifetime = 86400;


        $response = new Response($scriptContents);
        $response->headers->set('Symfony-Session-NoAutoCacheControl', 'application/javascript');
        $response->headers->set('Cache-Control', 'max-age=' . $lifetime);
        $response->headers->set('Pragma', '');
        $response->headers->set('Expires', gmdate('D, d M Y H:i:s', time() + $lifetime) . ' GMT');
        $response->headers->set('Content-Type', 'application/javascript');

        return $response;

    }

    public function populate($loadOrder, &$list, $item) {

        $depth  = count(debug_backtrace());;
        if ($depth > 100) {
            Logger::error($depth);
        }

        if (is_array($item["requires"])) {
            foreach ($item["requires"] as $itemId) {
                if (isset($list[$itemId])) {
                    continue;
                }
                $subItem = $loadOrder[$itemId];
                $list[$itemId] = $subItem;
                $this->populate($loadOrder, $list, $subItem);
            }
        }


        if (is_array($item["uses"])) {
            foreach ($item["uses"] as $itemId) {
                if (isset($list[$itemId])) {
                    continue;
                }
                $subItem = $loadOrder[$itemId];
                $list[$itemId] = $subItem;
                $this->populate($loadOrder, $list, $subItem);
            }
        }



    }



    /**
     * @Route("/pimcoreEditmodeScripts", name="pimcore_admin_pimcore_editmode_scripts", methods={"GET"})
     *
     * @throws \Exception
     */
    public function pimcoreEditmodeScriptsAction(Request $request, Packages $packages, RouterInterface $router) {
        $scriptContents = "";

        $bundleManager = $this->get('pimcore.extension.bundle_manager');
        $pluginJsPaths = $bundleManager->getEditmodeJsPaths();

        $scripts = [
//            '/bundles/pimcoreadmin/js/pimcore/common.js',
            '/bundles/pimcoreadmin/js/lib/class.js',
            '/bundles/pimcoreadmin/js/lib/ckeditor/ckeditor.js',
            '/bundles/pimcoreadmin/js/pimcore/functions.js',
            '/bundles/pimcoreadmin/js/pimcore/overrides.js',
            '/bundles/pimcoreadmin/js/pimcore/tool/milestoneslider.js',
            '/bundles/pimcoreadmin/js/pimcore/element/tag/imagehotspotmarkereditor.js',
            '/bundles/pimcoreadmin/js/pimcore/element/tag/imagecropper.js',
            '/bundles/pimcoreadmin/js/pimcore/document/edit/helper.js',
            '/bundles/pimcoreadmin/js/pimcore/elementservice.js',
            '/bundles/pimcoreadmin/js/pimcore/document/edit/dnd.js',
            '/bundles/pimcoreadmin/js/pimcore/document/tag.js',
            '/bundles/pimcoreadmin/js/pimcore/document/tags/block.js',
            '/bundles/pimcoreadmin/js/pimcore/document/tags/scheduledblock.js',
            '/bundles/pimcoreadmin/js/pimcore/document/tags/date.js',
            '/bundles/pimcoreadmin/js/pimcore/document/tags/relation.js',
            '/bundles/pimcoreadmin/js/pimcore/document/tags/relations.js',
            '/bundles/pimcoreadmin/js/pimcore/document/tags/checkbox.js',
            '/bundles/pimcoreadmin/js/pimcore/document/tags/image.js',
            '/bundles/pimcoreadmin/js/pimcore/document/tags/input.js',
            '/bundles/pimcoreadmin/js/pimcore/document/tags/link.js',
            '/bundles/pimcoreadmin/js/pimcore/document/tags/select.js',
            '/bundles/pimcoreadmin/js/pimcore/document/tags/snippet.js',
            '/bundles/pimcoreadmin/js/pimcore/document/tags/textarea.js',
            '/bundles/pimcoreadmin/js/pimcore/document/tags/numeric.js',
            '/bundles/pimcoreadmin/js/pimcore/document/tags/wysiwyg.js',
            '/bundles/pimcoreadmin/js/pimcore/document/tags/renderlet.js',
            '/bundles/pimcoreadmin/js/pimcore/document/tags/table.js',
            '/bundles/pimcoreadmin/js/pimcore/document/tags/video.js',
            '/bundles/pimcoreadmin/js/pimcore/document/tags/multiselect.js',
            '/bundles/pimcoreadmin/js/pimcore/document/tags/areablock.js',
            '/bundles/pimcoreadmin/js/pimcore/document/tags/area.js',
            '/bundles/pimcoreadmin/js/pimcore/document/tags/pdf.js',
            '/bundles/pimcoreadmin/js/pimcore/document/tags/embed.js',
            '/bundles/pimcoreadmin/js/pimcore/document/edit/helper.js',


            '/bundles/pimcoreadmin/js/pimcore/launcherEditmode.js',
        ];



        foreach ($scripts as $scriptUrl) {
            if (is_file(PIMCORE_WEB_ROOT . $scriptUrl)) {
                $scriptContents .= file_get_contents(PIMCORE_WEB_ROOT . $scriptUrl) . "\n\n\n";
            } else {
                Logger::error("could not find file " . PIMCORE_WEB_ROOT . $scriptUrl);
            }
        }

        foreach ($pluginJsPaths as $scriptUrl) {
            if (is_file(PIMCORE_WEB_ROOT .  $scriptUrl)) {
                $scriptContents .= file_get_contents(PIMCORE_WEB_ROOT . $scriptUrl) . "\n\n\n";
            } else {
                Logger::error("could not find file " . PIMCORE_WEB_ROOT . $scriptUrl);
            }
        }

        $lifetime = 86400;

        $response = new Response($scriptContents);
        $response->headers->set('Symfony-Session-NoAutoCacheControl', 'application/javascript');
        $response->headers->set('Cache-Control', 'max-age=' . $lifetime);
        $response->headers->set('Pragma', '');
        $response->headers->set('Expires', gmdate('D, d M Y H:i:s', time() + $lifetime) . ' GMT');
        $response->headers->set('Content-Type', 'application/javascript');
        return $response;

    }

}
