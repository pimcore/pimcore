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
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Routing\Annotation\Route;

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

        $scripts = [

            "lib/ext-plugins/portlet/PortalDropZone.js",
            "lib/ext-plugins/portlet/Portlet.js",
            "lib/ext-plugins/portlet/PortalColumn.js",
            "lib/ext-plugins/portlet/PortalPanel.js",

            // runtime
            "pimcore/functions.js",
            "pimcore/common.js",
            "pimcore/elementservice.js",
            "pimcore/helpers.js",
            "pimcore/error.js",

            "pimcore/treenodelocator.js",
            "pimcore/helpers/generic-grid.js",
            "pimcore/helpers/quantityValue.js",
            "pimcore/overrides.js",

            "pimcore/perspective.js",
            "pimcore/user.js",

            // tools
            "pimcore/tool/paralleljobs.js",
            "pimcore/tool/genericiframewindow.js",

            // settings
            "pimcore/settings/user/panels/abstract.js",
            "pimcore/settings/user/panel.js",

            "pimcore/settings/user/usertab.js",
            "pimcore/settings/user/editorSettings.js",
            "pimcore/settings/user/websiteTranslationSettings.js",
            "pimcore/settings/user/role/panel.js",
            "pimcore/settings/user/role/tab.js",
            "pimcore/settings/user/user/objectrelations.js",
            "pimcore/settings/user/user/settings.js",
            "pimcore/settings/user/user/keyBindings.js",
            "pimcore/settings/user/workspaces.js",
            "pimcore/settings/user/workspace/asset.js",
            "pimcore/settings/user/workspace/document.js",
            "pimcore/settings/user/workspace/object.js",
            "pimcore/settings/user/workspace/customlayouts.js",
            "pimcore/settings/user/workspace/language.js",
            "pimcore/settings/user/workspace/special.js",
            "pimcore/settings/user/role/settings.js",
            "pimcore/settings/profile/panel.js",
            "pimcore/settings/profile/twoFactorSettings.js",
            "pimcore/settings/thumbnail/item.js",
            "pimcore/settings/thumbnail/panel.js",
            "pimcore/settings/videothumbnail/item.js",
            "pimcore/settings/videothumbnail/panel.js",
            "pimcore/settings/translations.js",
            "pimcore/settings/translationEditor.js",
            "pimcore/settings/translation/website.js",
            "pimcore/settings/translation/admin.js",
            "pimcore/settings/translation/translationmerger.js",
            "pimcore/settings/translation/xliff.js",
            "pimcore/settings/translation/word.js",
            "pimcore/settings/metadata/predefined.js",
            "pimcore/settings/properties/predefined.js",
            "pimcore/settings/docTypes.js",
            "pimcore/settings/system.js",
            "pimcore/settings/web2print.js",
            "pimcore/settings/website.js",
            "pimcore/settings/staticroutes.js",
            "pimcore/settings/redirects.js",
            "pimcore/settings/glossary.js",
            "pimcore/settings/recyclebin.js",
            "pimcore/settings/fileexplorer/file.js",
            "pimcore/settings/fileexplorer/explorer.js",
            "pimcore/settings/maintenance.js",
            "pimcore/settings/robotstxt.js",
            "pimcore/settings/httpErrorLog.js",
            "pimcore/settings/email/log.js",
            "pimcore/settings/email/blacklist.js",
            "pimcore/settings/targeting/condition/abstract.js",
            "pimcore/settings/targeting/conditions.js",
            "pimcore/settings/targeting/action/abstract.js",
            "pimcore/settings/targeting/actions.js",
            "pimcore/settings/targeting/rules/panel.js",
            "pimcore/settings/targeting/rules/item.js",
            "pimcore/settings/targeting/targetGroups/panel.js",
            "pimcore/settings/targeting/targetGroups/item.js",
            "pimcore/settings/targeting_toolbar.js",

            "pimcore/settings/gdpr/gdprPanel.js",
            "pimcore/settings/gdpr/dataproviders/assets.js",
            "pimcore/settings/gdpr/dataproviders/dataObjects.js",
            "pimcore/settings/gdpr/dataproviders/sentMail.js",
            "pimcore/settings/gdpr/dataproviders/pimcoreUsers.js",

            // element
            "pimcore/element/abstract.js",
            "pimcore/element/selector/abstract.js",
            "pimcore/element/selector/selector.js",
            "pimcore/element/selector/document.js",
            "pimcore/element/selector/asset.js",
            "pimcore/element/properties.js",
            "pimcore/element/scheduler.js",
            "pimcore/element/dependencies.js",
            "pimcore/element/metainfo.js",
            "pimcore/element/history.js",
            "pimcore/element/notes.js",
            "pimcore/element/note_details.js",
            "pimcore/element/workflows.js",
            "pimcore/element/tag/imagecropper.js",
            "pimcore/element/tag/imagehotspotmarkereditor.js",
            "pimcore/element/replace_assignments.js",
            "pimcore/element/permissionchecker.js",
            "pimcore/element/gridexport/abstract.js",
            "pimcore/element/helpers/gridColumnConfig.js",
            "pimcore/element/helpers/gridConfigDialog.js",
            "pimcore/element/helpers/gridCellEditor.js",
            "pimcore/element/helpers/gridTabAbstract.js",
            "pimcore/object/helpers/grid.js",
            "pimcore/object/helpers/gridConfigDialog.js",
            "pimcore/object/helpers/import/csvPreviewTab.js",
            "pimcore/object/helpers/import/columnConfigurationTab.js",
            "pimcore/object/helpers/import/resolverSettingsTab.js",
            "pimcore/object/helpers/import/csvSettingsTab.js",
            "pimcore/object/helpers/import/saveAndShareTab.js",
            "pimcore/object/helpers/import/configDialog.js",
            "pimcore/object/helpers/import/reportTab.js",
            "pimcore/object/helpers/classTree.js",
            "pimcore/object/helpers/gridTabAbstract.js",
            "pimcore/object/helpers/metadataMultiselectEditor.js",
            "pimcore/object/helpers/customLayoutEditor.js",
            "pimcore/object/helpers/optionEditor.js",
            "pimcore/object/helpers/imageGalleryDropZone.js",
            "pimcore/object/helpers/imageGalleryPanel.js",
            "pimcore/element/selector/object.js",
            "pimcore/element/tag/configuration.js",
            "pimcore/element/tag/assignment.js",
            "pimcore/element/tag/tree.js",
            "pimcore/asset/helpers/metadataTree.js",
            "pimcore/asset/helpers/gridConfigDialog.js",
            "pimcore/asset/helpers/gridTabAbstract.js",
            "pimcore/asset/helpers/grid.js",

            // documents
            "pimcore/document/properties.js",
            "pimcore/document/document.js",
            "pimcore/document/page_snippet.js",
            "pimcore/document/edit.js",
            "pimcore/document/versions.js",
            "pimcore/document/settings_abstract.js",
            "pimcore/document/pages/settings.js",
            "pimcore/document/pages/preview.js",
            "pimcore/document/snippets/settings.js",
            "pimcore/document/emails/settings.js",
            "pimcore/document/newsletters/settings.js",
            "pimcore/document/newsletters/sendingPanel.js",
            "pimcore/document/newsletters/plaintextPanel.js",
            "pimcore/document/newsletters/addressSourceAdapters/default.js",
            "pimcore/document/newsletters/addressSourceAdapters/csvList.js",
            "pimcore/document/newsletters/addressSourceAdapters/report.js",
            "pimcore/document/link.js",
            "pimcore/document/hardlink.js",
            "pimcore/document/folder.js",
            "pimcore/document/tree.js",
            "pimcore/document/snippet.js",
            "pimcore/document/email.js",
            "pimcore/document/newsletter.js",
            "pimcore/document/page.js",
            "pimcore/document/printpages/pdf_preview.js",
            "pimcore/document/printabstract.js",
            "pimcore/document/printpage.js",
            "pimcore/document/printcontainer.js",
            "pimcore/document/seopanel.js",
            "pimcore/document/document_language_overview.js",
            "pimcore/document/customviews/tree.js",

            // assets
            "pimcore/asset/metadata/data/data.js",
            "pimcore/asset/metadata/data/input.js",
            "pimcore/asset/metadata/data/textarea.js",
            "pimcore/asset/metadata/data/asset.js",
            "pimcore/asset/metadata/data/document.js",
            "pimcore/asset/metadata/data/object.js",
            "pimcore/asset/metadata/data/date.js",
            "pimcore/asset/metadata/data/checkbox.js",
            "pimcore/asset/metadata/data/select.js",

            "pimcore/asset/metadata/tags/abstract.js",
            "pimcore/asset/metadata/tags/checkbox.js",
            "pimcore/asset/metadata/tags/date.js",
            "pimcore/asset/metadata/tags/input.js",
            "pimcore/asset/metadata/tags/manyToOneRelation.js",
            "pimcore/asset/metadata/tags/asset.js",
            "pimcore/asset/metadata/tags/document.js",
            "pimcore/asset/metadata/tags/object.js",
            "pimcore/asset/metadata/tags/select.js",
            "pimcore/asset/metadata/tags/textarea.js",
            "pimcore/asset/asset.js",
            "pimcore/asset/unknown.js",
            "pimcore/asset/embedded_meta_data.js",
            "pimcore/asset/image.js",
            "pimcore/asset/document.js",
            "pimcore/asset/video.js",
            "pimcore/asset/audio.js",
            "pimcore/asset/text.js",
            "pimcore/asset/folder.js",
            "pimcore/asset/listfolder.js",
            "pimcore/asset/versions.js",
            "pimcore/asset/metadata/grid.js",
            "pimcore/asset/tree.js",
            "pimcore/asset/customviews/tree.js",
            "pimcore/asset/gridexport/xlsx.js",
            "pimcore/asset/gridexport/csv.js",

            // object
            "pimcore/object/helpers/edit.js",
            "pimcore/object/helpers/layout.js",
            "pimcore/object/classes/class.js",
            "pimcore/object/class.js",
            "pimcore/object/bulk-base.js",
            "pimcore/object/bulk-export.js",
            "pimcore/object/bulk-import.js",
            "pimcore/object/classes/data/data.js",          // THIS MUST BE THE FIRST FILE, DO NOT MOVE THIS DOWN !!!
            "pimcore/object/classes/data/block.js",
            "pimcore/object/classes/data/classificationstore.js",
            "pimcore/object/classes/data/rgbaColor.js",
            "pimcore/object/classes/data/date.js",
            "pimcore/object/classes/data/datetime.js",
            "pimcore/object/classes/data/encryptedField.js",
            "pimcore/object/classes/data/time.js",
            "pimcore/object/classes/data/manyToOneRelation.js",
            "pimcore/object/classes/data/image.js",
            "pimcore/object/classes/data/externalImage.js",
            "pimcore/object/classes/data/hotspotimage.js",
            "pimcore/object/classes/data/imagegallery.js",
            "pimcore/object/classes/data/video.js",
            "pimcore/object/classes/data/input.js",
            "pimcore/object/classes/data/numeric.js",
            "pimcore/object/classes/data/manyToManyObjectRelation.js",
            "pimcore/object/classes/data/advancedManyToManyRelation.js",
            "pimcore/object/classes/data/advancedManyToManyObjectRelation.js",
            "pimcore/object/classes/data/reverseManyToManyObjectRelation.js",
            "pimcore/object/classes/data/booleanSelect.js",
            "pimcore/object/classes/data/select.js",
            "pimcore/object/classes/data/urlSlug.js",
            "pimcore/object/classes/data/user.js",
            "pimcore/object/classes/data/textarea.js",
            "pimcore/object/classes/data/wysiwyg.js",
            "pimcore/object/classes/data/checkbox.js",
            "pimcore/object/classes/data/consent.js",
            "pimcore/object/classes/data/slider.js",
            "pimcore/object/classes/data/manyToManyRelation.js",
            "pimcore/object/classes/data/table.js",
            "pimcore/object/classes/data/structuredTable.js",
            "pimcore/object/classes/data/country.js",
            "pimcore/object/classes/data/geo/abstract.js",
            "pimcore/object/classes/data/geopoint.js",
            "pimcore/object/classes/data/geobounds.js",
            "pimcore/object/classes/data/geopolygon.js",
            "pimcore/object/classes/data/geopolyline.js",
            "pimcore/object/classes/data/language.js",
            "pimcore/object/classes/data/password.js",
            "pimcore/object/classes/data/multiselect.js",
            "pimcore/object/classes/data/link.js",
            "pimcore/object/classes/data/fieldcollections.js",
            "pimcore/object/classes/data/objectbricks.js",
            "pimcore/object/classes/data/localizedfields.js",
            "pimcore/object/classes/data/countrymultiselect.js",
            "pimcore/object/classes/data/languagemultiselect.js",
            "pimcore/object/classes/data/firstname.js",
            "pimcore/object/classes/data/lastname.js",
            "pimcore/object/classes/data/email.js",
            "pimcore/object/classes/data/gender.js",
            "pimcore/object/classes/data/newsletterActive.js",
            "pimcore/object/classes/data/newsletterConfirmed.js",
            "pimcore/object/classes/data/targetGroup.js",
            "pimcore/object/classes/data/targetGroupMultiselect.js",
            "pimcore/object/classes/data/quantityValue.js",
            "pimcore/object/classes/data/inputQuantityValue.js",
            "pimcore/object/classes/data/calculatedValue.js",
            "pimcore/object/classes/layout/layout.js",
            "pimcore/object/classes/layout/accordion.js",
            "pimcore/object/classes/layout/fieldset.js",
            "pimcore/object/classes/layout/fieldcontainer.js",
            "pimcore/object/classes/layout/panel.js",
            "pimcore/object/classes/layout/region.js",
            "pimcore/object/classes/layout/tabpanel.js",
            "pimcore/object/classes/layout/button.js",
            "pimcore/object/classes/layout/iframe.js",
            "pimcore/object/fieldlookup/filterdialog.js",
            "pimcore/object/fieldlookup/helper.js",
            "pimcore/object/classes/layout/text.js",
            "pimcore/object/fieldcollection.js",
            "pimcore/object/fieldcollections/field.js",
            "pimcore/object/gridcolumn/Abstract.js",
            "pimcore/object/gridcolumn/operator/IsEqual.js",
            "pimcore/object/gridcolumn/operator/Text.js",
            "pimcore/object/gridcolumn/operator/Anonymizer.js",
            "pimcore/object/gridcolumn/operator/AnyGetter.js",
            "pimcore/object/gridcolumn/operator/AssetMetadataGetter.js",
            "pimcore/object/gridcolumn/operator/Arithmetic.js",
            "pimcore/object/gridcolumn/operator/Boolean.js",
            "pimcore/object/gridcolumn/operator/BooleanFormatter.js",
            "pimcore/object/gridcolumn/operator/CaseConverter.js",
            "pimcore/object/gridcolumn/operator/CharCounter.js",
            "pimcore/object/gridcolumn/operator/Concatenator.js",
            "pimcore/object/gridcolumn/operator/DateFormatter.js",
            "pimcore/object/gridcolumn/operator/ElementCounter.js",
            "pimcore/object/gridcolumn/operator/Iterator.js",
            "pimcore/object/gridcolumn/operator/JSON.js",
            "pimcore/object/gridcolumn/operator/LocaleSwitcher.js",
            "pimcore/object/gridcolumn/operator/Merge.js",
            "pimcore/object/gridcolumn/operator/ObjectFieldGetter.js",
            "pimcore/object/gridcolumn/operator/PHP.js",
            "pimcore/object/gridcolumn/operator/PHPCode.js",
            "pimcore/object/gridcolumn/operator/Base64.js",
            "pimcore/object/gridcolumn/operator/TranslateValue.js",
            "pimcore/object/gridcolumn/operator/PropertyGetter.js",
            "pimcore/object/gridcolumn/operator/RequiredBy.js",
            "pimcore/object/gridcolumn/operator/StringContains.js",
            "pimcore/object/gridcolumn/operator/StringReplace.js",
            "pimcore/object/gridcolumn/operator/Substring.js",
            "pimcore/object/gridcolumn/operator/LFExpander.js",
            "pimcore/object/gridcolumn/operator/Trimmer.js",
            "pimcore/object/gridcolumn/operator/Alias.js",
            "pimcore/object/gridcolumn/operator/WorkflowState.js",
            "pimcore/object/gridcolumn/value/Href.js",
            "pimcore/object/gridcolumn/value/Objects.js",
            "pimcore/object/gridcolumn/value/DefaultValue.js",
            "pimcore/object/gridcolumn/operator/GeopointRenderer.js",
            "pimcore/object/gridcolumn/operator/ImageRenderer.js",
            "pimcore/object/gridcolumn/operator/HotspotimageRenderer.js",
            "pimcore/object/importcolumn/Abstract.js",
            "pimcore/object/importcolumn/operator/Base64.js",
            "pimcore/object/importcolumn/operator/Ignore.js",
            "pimcore/object/importcolumn/operator/Iterator.js",
            "pimcore/object/importcolumn/operator/LocaleSwitcher.js",
            "pimcore/object/importcolumn/operator/ObjectBrickSetter.js",
            "pimcore/object/importcolumn/operator/PHPCode.js",
            "pimcore/object/importcolumn/operator/Published.js",
            "pimcore/object/importcolumn/operator/Splitter.js",
            "pimcore/object/importcolumn/operator/Unserialize.js",
            "pimcore/object/importcolumn/value/DefaultValue.js",
            "pimcore/object/objectbrick.js",
            "pimcore/object/objectbricks/field.js",
            "pimcore/object/tags/abstract.js",
            "pimcore/object/tags/abstractRelations.js",
            "pimcore/object/tags/block.js",
            "pimcore/object/tags/rgbaColor.js",
            "pimcore/object/tags/date.js",
            "pimcore/object/tags/datetime.js",
            "pimcore/object/tags/time.js",
            "pimcore/object/tags/manyToOneRelation.js",
            "pimcore/object/tags/image.js",
            "pimcore/object/tags/encryptedField.js",
            "pimcore/object/tags/externalImage.js",
            "pimcore/object/tags/hotspotimage.js",
            "pimcore/object/tags/imagegallery.js",
            "pimcore/object/tags/video.js",
            "pimcore/object/tags/input.js",
            "pimcore/object/tags/classificationstore.js",
            "pimcore/object/tags/numeric.js",
            "pimcore/object/tags/manyToManyObjectRelation.js",
            "pimcore/object/tags/advancedManyToManyRelation.js",
            "pimcore/object/gridcolumn/operator/FieldCollectionGetter.js",
            "pimcore/object/gridcolumn/operator/ObjectBrickGetter.js",
            "pimcore/object/tags/advancedManyToManyObjectRelation.js",
            "pimcore/object/tags/reverseManyToManyObjectRelation.js",
            "pimcore/object/tags/urlSlug.js",
            "pimcore/object/tags/booleanSelect.js",
            "pimcore/object/tags/select.js",
            "pimcore/object/tags/user.js",
            "pimcore/object/tags/checkbox.js",
            "pimcore/object/tags/consent.js",
            "pimcore/object/tags/textarea.js",
            "pimcore/object/tags/wysiwyg.js",
            "pimcore/object/tags/slider.js",
            "pimcore/object/tags/manyToManyRelation.js",
            "pimcore/object/tags/table.js",
            "pimcore/object/tags/structuredTable.js",
            "pimcore/object/tags/country.js",
            "pimcore/object/tags/geo/abstract.js",
            "pimcore/object/tags/geobounds.js",
            "pimcore/object/tags/geopoint.js",
            "pimcore/object/tags/geopolygon.js",
            "pimcore/object/tags/geopolyline.js",
            "pimcore/object/tags/language.js",
            "pimcore/object/tags/password.js",
            "pimcore/object/tags/multiselect.js",
            "pimcore/object/tags/link.js",
            "pimcore/object/tags/fieldcollections.js",
            "pimcore/object/tags/localizedfields.js",
            "pimcore/object/tags/countrymultiselect.js",
            "pimcore/object/tags/languagemultiselect.js",
            "pimcore/object/tags/objectbricks.js",
            "pimcore/object/tags/firstname.js",
            "pimcore/object/tags/lastname.js",
            "pimcore/object/tags/email.js",
            "pimcore/object/tags/gender.js",
            "pimcore/object/tags/newsletterActive.js",
            "pimcore/object/tags/newsletterConfirmed.js",
            "pimcore/object/tags/targetGroup.js",
            "pimcore/object/tags/targetGroupMultiselect.js",
            "pimcore/object/tags/quantityValue.js",
            "pimcore/object/tags/inputQuantityValue.js",
            "pimcore/object/tags/calculatedValue.js",
            "pimcore/object/preview.js",
            "pimcore/object/versions.js",
            "pimcore/object/variantsTab.js",
            "pimcore/object/folder/search.js",
            "pimcore/object/edit.js",
            "pimcore/object/abstract.js",
            "pimcore/object/object.js",
            "pimcore/object/folder.js",
            "pimcore/object/variant.js",
            "pimcore/object/tree.js",
            "pimcore/object/layout/iframe.js",
            "pimcore/object/customviews/tree.js",
            "pimcore/object/quantityvalue/unitsettings.js",
            "pimcore/object/gridexport/xlsx.js",
            "pimcore/object/gridexport/csv.js",

            //plugins
            "pimcore/plugin/broker.js",
            "pimcore/plugin/plugin.js",

            "pimcore/event-dispatcher.js",

            // reports
            "pimcore/report/panel.js",
            "pimcore/report/broker.js",
            "pimcore/report/abstract.js",
            "pimcore/report/settings.js",
            "pimcore/report/analytics/settings.js",
            "pimcore/report/analytics/elementoverview.js",
            "pimcore/report/analytics/elementexplorer.js",
            "pimcore/report/webmastertools/settings.js",
            "pimcore/report/tagmanager/settings.js",
            "pimcore/report/custom/item.js",
            "pimcore/report/custom/panel.js",
            "pimcore/report/custom/settings.js",
            "pimcore/report/custom/report.js",
            "pimcore/report/custom/definitions/sql.js",
            "pimcore/report/custom/definitions/analytics.js",
            "pimcore/report/custom/toolbarenricher.js",

            "pimcore/settings/tagmanagement/panel.js",
            "pimcore/settings/tagmanagement/item.js",

            "pimcore/report/qrcode/panel.js",
            "pimcore/report/qrcode/item.js",

            // extension manager
            "pimcore/extensionmanager/admin.js",

            // application logging
            "pimcore/log/admin.js",
            "pimcore/log/detailwindow.js",

            // layout
            "pimcore/layout/portal.js",
            "pimcore/layout/portlets/abstract.js",
            "pimcore/layout/portlets/modifiedDocuments.js",
            "pimcore/layout/portlets/modifiedObjects.js",
            "pimcore/layout/portlets/modifiedAssets.js",
            "pimcore/layout/portlets/modificationStatistic.js",
            "pimcore/layout/portlets/analytics.js",
            "pimcore/layout/portlets/piwik.js",
            "pimcore/layout/portlets/customreports.js",

            "pimcore/layout/toolbar.js",
            "pimcore/layout/treepanelmanager.js",
            "pimcore/document/seemode.js",

            // classification store
            "pimcore/object/classificationstore/groupsPanel.js",
            "pimcore/object/classificationstore/propertiesPanel.js",
            "pimcore/object/classificationstore/collectionsPanel.js",
            "pimcore/object/classificationstore/keyDefinitionWindow.js",
            "pimcore/object/classificationstore/keySelectionWindow.js",
            "pimcore/object/classificationstore/relationSelectionWindow.js",
            "pimcore/object/classificationstore/storeConfiguration.js",
            "pimcore/object/classificationstore/storeTree.js",
            "pimcore/object/classificationstore/columnConfigDialog.js",

            //workflow
            "pimcore/workflow/transitionPanel.js",
            "pimcore/workflow/transitions.js",

            // Piwik - this needs to be loaded after treepanel manager as
            // it adds panels in pimcore ready
            "pimcore/analytics/piwik/widget_store_provider.js",
            "pimcore/report/piwik/settings.js",
            "pimcore/report/piwik/dashboard_iframe.js",

            // color picker
            "pimcore/colorpicker-overrides.js",

            //notification
            "pimcore/notification/helper.js",
            "pimcore/notification/panel.js",
            "pimcore/notification/modal.js",
            "pimcore/launcher.js",
        ];



        $scriptContents = "";
        foreach ($scripts as $scriptUrl) {
            if (is_file(PIMCORE_WEB_ROOT . "/bundles/pimcoreadmin/js/" . $scriptUrl)) {
                $scriptContents .= file_get_contents(PIMCORE_WEB_ROOT . "/bundles/pimcoreadmin/js/" . $scriptUrl) . "\n\n\n";
            } else {
                Logger::error("could not find file " . $scriptUrl);
            }
        }

        foreach ($pluginJsPaths as $scriptUrl) {
            if (is_file(PIMCORE_WEB_ROOT .  $scriptUrl)) {
                $scriptContents .= file_get_contents(PIMCORE_WEB_ROOT . $scriptUrl) . "\n\n\n";
            } else {
                Logger::error("could not find file " . $scriptUrl);
            }
        }

        $lifetime = 86400;

        $response = new Response($scriptContents);
        $response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'application/javascript');
        $response->headers->set('Cache-Control', 'max-age=' . $lifetime);
        $response->headers->set('Pragma', '');
        $response->headers->set('Expires', gmdate('D, d M Y H:i:s', time() + $lifetime) . ' GMT');
        $response->headers->set('Content-Type', 'application/javascript');
        return $response;

    }

}
