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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\FileExplorerBundle\Controller;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/settings")
 *
 * @internal
 */
class SettingsController extends AdminController
{
    /**
     * @Route("/tree", name="pimcore_bundle_file_explorer_settings_tree", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function treeAction(Request $request): JsonResponse
    {
        $this->checkPermission('fileexplorer');
        $referencePath = $this->getFileExplorerPath($request, 'node');

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
     * @Route("/content", name="pimcore_bundle_file_explorer_settings_content", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function contentAction(Request $request): JsonResponse
    {
        $this->checkPermission('fileexplorer');

        $success = false;
        $writeable = false;
        $file = $this->getFileExplorerPath($request, 'path');
        $content = null;
        if (is_file($file)) {
            if (is_readable($file)) {
                $content = file_get_contents($file);
                $success = true;
                $writeable = is_writable($file);
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
     * @Route("/content-save", name="pimcore_bundle_file_explorer_settings_contentsave", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function contentSaveAction(Request $request): JsonResponse
    {
        $this->checkPermission('fileexplorer');

        $success = false;

        if ($request->get('content') && $request->get('path')) {
            $file = $this->getFileExplorerPath($request, 'path');
            if (is_file($file) && is_writable($file)) {
                File::put($file, $request->get('content'));

                $success = true;
            }
        }

        return $this->adminJson([
            'success' => $success,
        ]);
    }

    /**
     * @Route("/add", name="pimcore_bundle_file_explorer_settings_add", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function addAction(Request $request): JsonResponse
    {
        $this->checkPermission('fileexplorer');

        $success = false;

        if ($request->get('filename') && $request->get('path')) {
            $path = $this->getFileExplorerPath($request, 'path');
            $file = $path . '/' . $request->get('filename');

            $file = resolvePath($file);
            if (strpos($file, PIMCORE_PROJECT_ROOT) !== 0) {
                throw new \Exception('not allowed');
            }

            if (is_writable(dirname($file))) {
                File::put($file, '');

                $success = true;
            }
        }

        return $this->adminJson([
            'success' => $success,
        ]);
    }

    /**
     * @Route("/add-folder", name="pimcore_bundle_file_explorer_settings_addfolder", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function addFolderAction(Request $request): JsonResponse
    {
        $this->checkPermission('fileexplorer');

        $success = false;

        if ($request->get('filename') && $request->get('path')) {
            $path = $this->getFileExplorerPath($request, 'path');
            $file = $path . '/' . $request->get('filename');

            $file = resolvePath($file);
            if (strpos($file, PIMCORE_PROJECT_ROOT) !== 0) {
                throw new \Exception('not allowed');
            }

            if (is_writable(dirname($file))) {
                File::mkdir($file);

                $success = true;
            }
        }

        return $this->adminJson([
            'success' => $success,
        ]);
    }

    /**
     * @Route("/delete", name="pimcore_bundle_file_explorer_settings_delete", methods={"DELETE"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function deleteAction(Request $request): JsonResponse
    {
        $this->checkPermission('fileexplorer');
        $success = false;

        if ($request->get('path')) {
            $file = $this->getFileExplorerPath($request, 'path');
            if (is_writable($file)) {
                unlink($file);
                $success = true;
            }
        }

        return $this->adminJson([
            'success' => $success,
        ]);
    }

    /**
     * @Route("/rename", name="pimcore_bundle_file_explorer_settings_rename", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function renameAction(Request $request): JsonResponse
    {
        $this->checkPermission('fileexplorer');
        $success = false;

        if ($request->get('path') && $request->get('newPath')) {
            $file = $this->getFileExplorerPath($request, 'path');
            $newFile = $this->getFileExplorerPath($request, 'newPath');

            $success = rename($file, $newFile);
        }

        return $this->adminJson([
            'success' => $success,
        ]);
    }

    /**
     * @throws \Exception
     *
     * @psalm-taint-specialize
     */
    private function getFileExplorerPath(Request $request, string $paramName = 'node'): string
    {
        $path = preg_replace("/^\/fileexplorer/", '', $request->get($paramName));
        $path = resolvePath(PIMCORE_PROJECT_ROOT . $path);

        if (strpos($path, PIMCORE_PROJECT_ROOT) !== 0) {
            throw new \Exception('operation permitted, permission denied');
        }

        return $path;
    }
}
