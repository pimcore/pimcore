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

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Config;
use Pimcore\Controller\Config\ControllerDataProvider;
use Pimcore\File;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Tool;
use Pimcore\Tool\Storage;
use Pimcore\Translation\Translator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/misc")
 *
 * @internal
 */
class MiscController extends AdminController
{
    /**
     * @Route("/get-available-controller-references", name="pimcore_admin_misc_getavailablecontroller_references", methods={"GET"})
     *
     * @param Request $request
     * @param ControllerDataProvider $provider
     *
     * @return JsonResponse
     */
    public function getAvailableControllerReferencesAction(Request $request, ControllerDataProvider $provider): JsonResponse
    {
        $controllerReferences = $provider->getControllerReferences();

        $result = array_map(function ($controller) {
            return [
                'name' => $controller,
            ];
        }, $controllerReferences);

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
    public function getAvailableTemplatesAction(ControllerDataProvider $provider): JsonResponse
    {
        $templates = $provider->getTemplates();

        sort($templates, SORT_NATURAL | SORT_FLAG_CASE);

        $result = array_map(static function ($template) {
            return [
                'path' => $template,
            ];
        }, $templates);

        return $this->adminJson([
            'data' => $result,
        ]);
    }

    /**
     * @Route("/json-translations-system", name="pimcore_admin_misc_jsontranslationssystem", methods={"GET"})
     *
     *
     */
    public function jsonTranslationsSystemAction(Request $request, TranslatorInterface $translator): Response
    {
        $language = $request->get('language');

        /** @var Translator $translator */
        $translator->lazyInitialize('admin', $language);

        $translations = [];

        foreach (['admin', 'admin_ext'] as $domain) {
            $translations = array_merge($translations, $translator->getCatalogue($language)->all($domain));
            if ($language != 'en') {
                // add en as a fallback
                $translator->lazyInitialize($domain, 'en');
                foreach ($translator->getCatalogue('en')->all($domain) as $key => $value) {
                    if (empty($translations[$key])) {
                        $translations[$key] = $value;
                    }
                }
            }
        }

        $response = new Response('pimcore.system_i18n = ' . $this->encodeJson($translations) . ';');
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
    public function scriptProxyAction(Request $request): Response
    {
        if ($storageFile = $request->get('storageFile')) {
            $fileExtension = \Pimcore\File::getFileExtension($storageFile);
            $storage = Storage::get('admin');
            $scriptsContent = $storage->read($storageFile);
        } else {
            trigger_deprecation('pimcore/pimcore', '10.1', 'Calling /admin/misc/script-proxy without the parameter storageFile is deprecated and will not work in Pimcore 11.');
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
        }

        if (!empty($scriptsContent)) {
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
    public function adminCssAction(Request $request, Config $config): Response
    {
        // customviews config
        $cvData = \Pimcore\CustomView\Config::get();

        // languages
        $languages = \Pimcore\Tool::getValidLanguages();
        $adminLanguages = \Pimcore\Tool\Admin::getLanguages();
        $languages = array_unique(array_merge($languages, $adminLanguages));

        $response = $this->render('@PimcoreAdmin/admin/misc/admin_css.html.twig', [
            'customviews' => $cvData,
            'config' => $config,
            'languages' => $languages,
        ]);
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
    public function pingAction(Request $request): JsonResponse
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
    public function availableLanguagesAction(Request $request): Response
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
    public function getValidFilenameAction(Request $request): JsonResponse
    {
        return $this->adminJson([
            'filename' => \Pimcore\Model\Element\Service::getValidKey($request->get('value'), $request->get('type')),
        ]);
    }


    /**
     * @Route("/maintenance", name="pimcore_admin_misc_maintenance", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function maintenanceAction(Request $request): JsonResponse
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
     * @Route("/country-list", name="pimcore_admin_misc_countrylist", methods={"GET"})
     *
     * @param LocaleServiceInterface $localeService
     *
     * @return JsonResponse
     */
    public function countryListAction(LocaleServiceInterface $localeService): JsonResponse
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
    public function languageListAction(Request $request): JsonResponse
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
     * @Route("/get-language-flag", name="pimcore_admin_misc_getlanguageflag", methods={"GET"})
     *
     * @param Request $request
     *
     * @return BinaryFileResponse
     */
    public function getLanguageFlagAction(Request $request): BinaryFileResponse
    {
        $iconPath = Tool::getLanguageFlagFile($request->get('language'));
        $response = new BinaryFileResponse($iconPath);
        $response->headers->set('Content-Type', 'image/svg+xml');

        return $response;
    }

    /**
     * @Route("/icon-list", name="pimcore_admin_misc_iconlist", methods={"GET"})
     *
     * @param Request $request
     * @param Profiler|null $profiler
     *
     * @return Response
     */
    public function iconListAction(Request $request, ?Profiler $profiler): Response
    {
        if ($profiler) {
            $profiler->disable();
        }

        $publicDir = PIMCORE_WEB_ROOT . '/bundles/pimcoreadmin';
        $iconDir = $publicDir . '/img';
        $colorIcons = rscandir($iconDir . '/flat-color-icons/');
        $whiteIcons = rscandir($iconDir . '/flat-white-icons/');
        $twemoji = rscandir($iconDir . '/twemoji/');

        //flag icons for locales
        $locales = Tool::getSupportedLocales();
        $languageOptions = [];
        foreach ($locales as $short => $translation) {
            if (!empty($short)) {
                $languageOptions[] = [
                    'language' => $short,
                    'display' => $translation . " ($short)",
                    'flag' => \Pimcore\Tool::getLanguageFlagFile($short, true),
                ];
            }
        }

        $iconsCss = file_get_contents($publicDir . '/css/icons.css');

        return $this->render('@PimcoreAdmin/admin/misc/icon_list.html.twig', [
            'colorIcons' => $colorIcons,
            'whiteIcons' => $whiteIcons,
            'twemoji' => $twemoji,
            'languageOptions' => $languageOptions,
            'iconsCss' => $iconsCss,
        ]);
    }

    /**
     * @Route("/test", name="pimcore_admin_misc_test")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function testAction(Request $request): Response
    {
        return new Response('done');
    }
}
