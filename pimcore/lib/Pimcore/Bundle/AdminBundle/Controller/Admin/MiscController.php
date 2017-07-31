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
use Pimcore\Db;
use Pimcore\File;
use Pimcore\Tool;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/misc")
 */
class MiscController extends AdminController
{
    /**
     * @Route("/get-available-modules")
     *
     * @return JsonResponse
     */
    public function getAvailableModulesAction()
    {
        // TODO define admin controllers as services and use action injection
        $provider = $this->get('pimcore.controller.config.controller_data_provider');

        // convert to normal array
        $bundles = array_values($provider->getBundles());

        $result = array_map(function (BundleInterface $bundle) {
            return [
                'name' => $bundle->getName()
            ];
        }, $bundles);

        return $this->json([
            'data' => $result
        ]);
    }

    /**
     * @Route("/get-available-controllers")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getAvailableControllersAction(Request $request)
    {
        $provider = $this->get('pimcore.controller.config.controller_data_provider');

        $bundle      = $request->get('moduleName');
        $controllers = $provider->getControllers($bundle, 'AppBundle');

        $result = array_map(function ($controller) {
            return [
                'name' => $controller
            ];
        }, $controllers);

        return $this->json([
            'data' => $result
        ]);
    }

    /**
     * @Route("/get-available-actions")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getAvailableActionsAction(Request $request)
    {
        $provider = $this->get('pimcore.controller.config.controller_data_provider');

        $bundle = $request->get('moduleName');
        if (empty($bundle)) {
            $bundle = 'AppBundle';
        }

        $controller = $request->get('controllerName');
        $actions    = $provider->getActions($controller, $bundle);

        $result = array_map(function ($action) {
            return [
                'name' => $action
            ];
        }, $actions);

        return $this->json([
            'data' => $result
        ]);
    }

    /**
     * @Route("/get-available-templates")
     *
     * @return JsonResponse
     */
    public function getAvailableTemplatesAction()
    {
        $provider = $this->get('pimcore.controller.config.controller_data_provider');

        $templates = $provider->getTemplates();

        $result = array_map(function ($template) {
            return [
                'path' => $template
            ];
        }, $templates);

        return $this->json([
            'data' => $result
        ]);
    }

    /**
     * @Route("/json-translations-system")
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

        $response = new Response('pimcore.system_i18n = ' . $this->encodeJson($translations) . ';');
        $response->headers->set('Content-Type', 'text/javascript');

        return $response;
    }

    /**
     * @Route("/script-proxy")
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
    }

    /**
     * @Route("/admin-css")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function adminCssAction(Request $request)
    {
        // customviews config
        $cvData = Tool::getCustomViewConfig();

        $response = $this->render('PimcoreAdminBundle:Admin/Misc:admin-css.html.php', ['customviews' => $cvData]);
        $response->headers->set('Content-Type', 'text/css; charset=UTF-8');

        return $response;
    }

    /**
     * @Route("/ping")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function pingAction(Request $request)
    {
        $response = [
            'success' => true
        ];

        return $this->json($response);
    }

    /**
     * @Route("/available-languages")
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
     * @Route("/get-valid-filename")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getValidFilenameAction(Request $request)
    {
        return $this->json([
            'filename' => \Pimcore\Model\Element\Service::getValidKey($request->get('value'), $request->get('type'))
        ]);
    }

    /* FILEEXPLORER */

    /**
     * @Route("/fileexplorer-tree")
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
                    'writeable' => is_writable($file)
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

        return $this->json($contents);
    }

    /**
     * @Route("/fileexplorer-content")
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
        if (is_file($file)) {
            if (is_readable($file)) {
                $content = file_get_contents($file);
                $success = true;
                $writeable = is_writeable($file);
            }
        }

        return $this->json([
            'success' => $success,
            'content' => $content,
            'writeable' => $writeable,
            'path' => preg_replace('@^' . preg_quote(PIMCORE_PROJECT_ROOT) . '@', '', $file)
        ]);
    }

    /**
     * @Route("/fileexplorer-content-save")
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

        return $this->json([
            'success' => $success
        ]);
    }

    /**
     * @Route("/fileexplorer-add")
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

        return $this->json([
            'success' => $success
        ]);
    }

    /**
     * @Route("/fileexplorer-add-folder")
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

        return $this->json([
            'success' => $success
        ]);
    }

    /**
     * @Route("/fileexplorer-delete")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function fileexplorerDeleteAction(Request $request)
    {
        $this->checkPermission('fileexplorer');

        if ($request->get('path')) {
            $file = $this->getFileexplorerPath($request, 'path');
            if (is_writeable($file)) {
                unlink($file);
                $success = true;
            }
        }

        return $this->json([
            'success' => $success
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
     * @Route("/maintenance")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function maintenanceAction(Request $request)
    {
        $this->checkPermission('maintenance_mode');

        if ($request->get('activate')) {
            Tool\Admin::activateMaintenanceMode($request->getSession()->getId());
        }

        if ($request->get('deactivate')) {
            Tool\Admin::deactivateMaintenanceMode();
        }

        return $this->json([
            'success' => true
        ]);
    }

    /**
     * @Route("/http-error-log")
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

        return $this->json([
            'items' => $logs,
            'total' => $total,
            'success' => true
        ]);
    }

    /**
     * @Route("/http-error-log-flush")
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

        return $this->json([
            'success' => true
        ]);
    }

    /**
     * @Route("/http-error-log-detail")
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
     * @Route("/country-list")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function countryListAction(Request $request)
    {
        $countries = \Pimcore::getContainer()->get('pimcore.locale')->getDisplayRegions();
        asort($countries);
        $options = [];

        foreach ($countries as $short => $translation) {
            if (strlen($short) == 2) {
                $options[] = [
                    'name' => $translation,
                    'code' => $short
                ];
            }
        }

        return $this->json(['data' => $options]);
    }

    /**
     * @Route("/language-list")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function languageListAction(Request $request)
    {
        $locales = Tool::getSupportedLocales();

        foreach ($locales as $short => $translation) {
            $options[] = [
                'name' => $translation,
                'code' => $short
            ];
        }

        return $this->json(['data' => $options]);
    }

    /**
     * @Route("/phpinfo")
     *
     * @param Request $request
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function phpinfoAction(Request $request)
    {
        if (!$this->getUser()->isAdmin()) {
            throw new \Exception('Permission denied');
        }

        ob_start();
        phpinfo();
        $content = ob_get_clean();

        return new Response($content);
    }

    /**
     * @Route("/get-language-flag")
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
     * @Route("/test")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function testAction(Request $request)
    {
        return new Response('done');
    }
}
