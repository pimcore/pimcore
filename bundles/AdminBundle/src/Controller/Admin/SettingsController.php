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
use Pimcore\Cache;
use Pimcore\Cache\Core\CoreCacheHandler;
use Pimcore\Cache\Symfony\CacheClearer;
use Pimcore\Config;
use Pimcore\Db;
use Pimcore\Event\AdminEvents;
use Pimcore\Event\SystemEvents;
use Pimcore\File;
use Pimcore\Helper\StopMessengerWorkersTrait;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\Element;
use Pimcore\Model\Exception\ConfigWriteException;
use Pimcore\Model\Metadata;
use Pimcore\Model\Property;
use Pimcore\Model\WebsiteSetting;
use Pimcore\Tool;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Yaml\Yaml;

/**
 * @Route("/settings")
 *
 * @internal
 */
class SettingsController extends AdminController
{
    use StopMessengerWorkersTrait;

    private const CUSTOM_LOGO_PATH = 'custom-logo.image';

    /**
     * @Route("/display-custom-logo", name="pimcore_settings_display_custom_logo", methods={"GET"})
     *
     * @param Request $request
     *
     * @return StreamedResponse
     */
    public function displayCustomLogoAction(Request $request): StreamedResponse
    {
        $mime = 'image/svg+xml';
        if ($request->get('white')) {
            $logo = PIMCORE_WEB_ROOT . '/bundles/pimcoreadmin/img/logo-claim-white.svg';
        } else {
            $logo = PIMCORE_WEB_ROOT . '/bundles/pimcoreadmin/img/logo-claim-gray.svg';
        }

        $stream = fopen($logo, 'rb');

        $storage = Tool\Storage::get('admin');
        if ($storage->fileExists(self::CUSTOM_LOGO_PATH)) {
            try {
                $mime = $storage->mimeType(self::CUSTOM_LOGO_PATH);
                $stream = $storage->readStream(self::CUSTOM_LOGO_PATH);
            } catch (\Exception $e) {
                // do nothing
            }
        }

        return new StreamedResponse(function () use ($stream) {
            fpassthru($stream);
        }, 200, [
            'Content-Type' => $mime,
            'Content-Security-Policy' => "script-src 'none'",
        ]);
    }

    /**
     * @Route("/upload-custom-logo", name="pimcore_admin_settings_uploadcustomlogo", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function uploadCustomLogoAction(Request $request): JsonResponse
    {
        $logoFile = $request->files->get('Filedata');

        if (!$logoFile instanceof UploadedFile
            || !in_array($logoFile->guessExtension(), ['svg', 'png', 'jpg'])
        ) {
            throw new \Exception('Unsupported file format.');
        }

        $storage = Tool\Storage::get('admin');
        $storage->writeStream(self::CUSTOM_LOGO_PATH, fopen($logoFile->getPathname(), 'rb'));

        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed

        $response = $this->adminJson(['success' => true]);
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    /**
     * @Route("/delete-custom-logo", name="pimcore_admin_settings_deletecustomlogo", methods={"DELETE"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function deleteCustomLogoAction(Request $request): JsonResponse
    {
        if (Tool\Storage::get('admin')->fileExists(self::CUSTOM_LOGO_PATH)) {
            Tool\Storage::get('admin')->delete(self::CUSTOM_LOGO_PATH);
        }

        return $this->adminJson(['success' => true]);
    }

    /**
     * Used by the predefined metadata grid
     *
     * @Route("/predefined-metadata", name="pimcore_admin_settings_metadata", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function metadataAction(Request $request): JsonResponse
    {
        $this->checkPermission('asset_metadata');

        if ($request->get('data')) {
            if ($request->get('xaction') == 'destroy') {
                $data = $this->decodeJson($request->get('data'));
                $id = $data['id'];
                $metadata = Metadata\Predefined::getById($id);
                if (!$metadata->isWriteable()) {
                    throw new ConfigWriteException();
                }
                $metadata->delete();

                return $this->adminJson(['success' => true, 'data' => []]);
            } elseif ($request->get('xaction') == 'update') {
                $data = $this->decodeJson($request->get('data'));

                // save type
                $metadata = Metadata\Predefined::getById($data['id']);
                if (!$metadata->isWriteable()) {
                    throw new ConfigWriteException();
                }
                $metadata->setValues($data);

                $existingItem = Metadata\Predefined\Listing::getByKeyAndLanguage($metadata->getName(), $metadata->getLanguage(), $metadata->getTargetSubtype());
                if ($existingItem && $existingItem->getId() != $metadata->getId()) {
                    return $this->adminJson(['message' => 'rule_violation', 'success' => false]);
                }

                $metadata->minimize();
                $metadata->save();
                $metadata->expand();

                $responseData = $metadata->getObjectVars();
                $responseData['writeable'] = $metadata->isWriteable();

                return $this->adminJson(['data' => $responseData, 'success' => true]);
            } elseif ($request->get('xaction') == 'create') {
                if (!(new Metadata\Predefined())->isWriteable()) {
                    throw new ConfigWriteException();
                }
                $data = $this->decodeJson($request->get('data'));
                unset($data['id']);

                // save type
                $metadata = Metadata\Predefined::create();

                $metadata->setValues($data);

                $existingItem = Metadata\Predefined\Listing::getByKeyAndLanguage($metadata->getName(), $metadata->getLanguage(), $metadata->getTargetSubtype());
                if ($existingItem) {
                    return $this->adminJson(['message' => 'rule_violation', 'success' => false]);
                }

                $metadata->save();

                $responseData = $metadata->getObjectVars();
                $responseData['writeable'] = $metadata->isWriteable();

                return $this->adminJson(['data' => $responseData, 'success' => true]);
            }
        } else {
            // get list of types
            $list = new Metadata\Predefined\Listing();

            if ($filter = $request->get('filter')) {
                $list->setFilter(function (Metadata\Predefined $predefined) use ($filter) {
                    foreach ($predefined->getObjectVars() as $value) {
                        if (stripos($value, $filter) !== false) {
                            return true;
                        }
                    }

                    return false;
                });
            }

            $properties = [];
            foreach ($list->getDefinitions() as $metadata) {
                $metadata->expand();
                $data = $metadata->getObjectVars();
                $data['writeable'] = $metadata->isWriteable();
                $properties[] = $data;
            }

            return $this->adminJson(['data' => $properties, 'success' => true, 'total' => $list->getTotalCount()]);
        }

        return $this->adminJson(['success' => false]);
    }

    /**
     * @Route("/get-predefined-metadata", name="pimcore_admin_settings_getpredefinedmetadata", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getPredefinedMetadataAction(Request $request): JsonResponse
    {
        $type = $request->get('type');
        $subType = $request->get('subType');
        $group = $request->get('group');
        $list = Metadata\Predefined\Listing::getByTargetType($type, [$subType]);
        $result = [];
        foreach ($list as $item) {
            $itemGroup = $item->getGroup() ?? '';
            if ($group === 'default' || $group === $itemGroup) {
                $item->expand();
                $data = $item->getObjectVars();
                $data['writeable'] = $item->isWriteable();
                $result[] = $data;
            }
        }

        return $this->adminJson(['data' => $result, 'success' => true]);
    }

    /**
     * @Route("/properties", name="pimcore_admin_settings_properties", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function propertiesAction(Request $request): JsonResponse
    {
        if ($request->get('data')) {
            $this->checkPermission('predefined_properties');

            if ($request->get('xaction') == 'destroy') {
                $data = $this->decodeJson($request->get('data'));
                $id = $data['id'];
                $property = Property\Predefined::getById($id);
                if (!$property->isWriteable()) {
                    throw new ConfigWriteException();
                }
                $property->delete();

                return $this->adminJson(['success' => true, 'data' => []]);
            } elseif ($request->get('xaction') == 'update') {
                $data = $this->decodeJson($request->get('data'));

                // save type
                $property = Property\Predefined::getById($data['id']);
                if (!$property->isWriteable()) {
                    throw new ConfigWriteException();
                }
                if (is_array($data['ctype'])) {
                    $data['ctype'] = implode(',', $data['ctype']);
                }
                $property->setValues($data);

                $property->save();

                $responseData = $property->getObjectVars();
                $responseData['writeable'] = $property->isWriteable();

                return $this->adminJson(['data' => $responseData, 'success' => true]);
            } elseif ($request->get('xaction') == 'create') {
                if (!(new Property\Predefined())->isWriteable()) {
                    throw new ConfigWriteException();
                }
                $data = $this->decodeJson($request->get('data'));
                unset($data['id']);

                // save type
                $property = Property\Predefined::create();
                $property->setValues($data);

                $property->save();

                $responseData = $property->getObjectVars();
                $responseData['writeable'] = $property->isWriteable();

                return $this->adminJson(['data' => $responseData, 'success' => true]);
            }
        } else {
            // get list of types
            $list = new Property\Predefined\Listing();

            if ($filter = $request->get('filter')) {
                $list->setFilter(function (Property\Predefined $predefined) use ($filter) {
                    foreach ($predefined->getObjectVars() as $value) {
                        if ($value) {
                            $cellValues = is_array($value) ? $value : [$value];

                            foreach ($cellValues as $cellValue) {
                                if (stripos($cellValue, $filter) !== false) {
                                    return true;
                                }
                            }
                        }
                    }

                    return false;
                });
            }

            $properties = [];
            foreach ($list->getProperties() as $property) {
                $data = $property->getObjectVars();
                $data['writeable'] = $property->isWriteable();
                $properties[] = $data;
            }

            return $this->adminJson(['data' => $properties, 'success' => true, 'total' => $list->getTotalCount()]);
        }

        return $this->adminJson(['success' => false]);
    }

    /**
     * @Route("/get-system", name="pimcore_admin_settings_getsystem", methods={"GET"})
     *
     * @param Request $request
     * @param Config $config
     *
     * @return JsonResponse
     */
    public function getSystemAction(Request $request, Config $config): JsonResponse
    {
        $this->checkPermission('system_settings');

        $valueArray = [
            'general' => $config['general'],
            'documents' => $config['documents'],
            'assets' => $config['assets'],
            'objects' => $config['objects'],
            'branding' => $config['branding'],
            'email' => $config['email'],
        ];

        $locales = Tool::getSupportedLocales();
        $languageOptions = [];
        $validLanguages = [];
        foreach ($locales as $short => $translation) {
            if (!empty($short)) {
                $languageOptions[] = [
                    'language' => $short,
                    'display' => $translation . " ($short)",
                ];
                $validLanguages[] = $short;
            }
        }

        $valueArray['general']['valid_language'] = explode(',', $valueArray['general']['valid_languages']);

        //for "wrong" legacy values
        foreach ($valueArray['general']['valid_language'] as $existingValue) {
            if (!in_array($existingValue, $validLanguages)) {
                $languageOptions[] = [
                    'language' => $existingValue,
                    'display' => $existingValue,
                ];
            }
        }

        $response = [
            'values' => $valueArray,
            'config' => [
                'languages' => $languageOptions,
            ],
        ];

        return $this->adminJson($response);
    }

    /**
     * @Route("/set-system", name="pimcore_admin_settings_setsystem", methods={"PUT"})
     *
     *
     */
    public function setSystemAction(
        LocaleServiceInterface $localeService,
        Request $request,
        KernelInterface $kernel,
        EventDispatcherInterface $eventDispatcher,
        CoreCacheHandler $cache,
        Filesystem $filesystem,
        CacheClearer $symfonyCacheClearer
    ): JsonResponse {
        $this->checkPermission('system_settings');

        $values = $this->decodeJson($request->get('data'));
        $existingValues = [];

        try {
            $file = Config::locateConfigFile('system.yaml');
            $existingValues = Config::getConfigInstance($file);
        } catch (\Exception $e) {
            // nothing to do
        }

        // localized error pages
        $localizedErrorPages = [];

        // fallback languages
        $fallbackLanguages = [];
        $languages = explode(',', $values['general.validLanguages']);
        $filteredLanguages = [];

        foreach ($languages as $language) {
            if (isset($values['general.fallbackLanguages.' . $language])) {
                $fallbackLanguages[$language] = str_replace(' ', '', $values['general.fallbackLanguages.' . $language]);
            }

            // localized error pages
            if (isset($values['documents.error_pages.localized.' . $language])) {
                $localizedErrorPages[$language] = $values['documents.error_pages.localized.' . $language];
            }

            if ($localeService->isLocale($language)) {
                $filteredLanguages[] = $language;
            }
        }

        // check if there's a fallback language endless loop
        foreach ($fallbackLanguages as $sourceLang => $targetLang) {
            $this->checkFallbackLanguageLoop($sourceLang, $fallbackLanguages);
        }

        $settings['pimcore'] = [
            'general' => [
                'domain' => $values['general.domain'],
                'redirect_to_maindomain' => $values['general.redirect_to_maindomain'],
                'language' => $values['general.language'],
                'valid_languages' => implode(',', $filteredLanguages),
                'fallback_languages' => $fallbackLanguages,
                'default_language' => $values['general.defaultLanguage'],
                'debug_admin_translations' => $values['general.debug_admin_translations'],
            ],
            'documents' => [
                'versions' => [
                    'days' => $values['documents.versions.days'] ?? null,
                    'steps' => $values['documents.versions.steps'] ?? null,
                ],
                'error_pages' => [
                    'default' => $values['documents.error_pages.default'],
                    'localized' => $localizedErrorPages,
                ],
            ],
            'objects' => [
                'versions' => [
                    'days' => $values['objects.versions.days'] ?? null,
                    'steps' => $values['objects.versions.steps'] ?? null,
                ],
            ],
            'assets' => [
                'versions' => [
                    'days' => $values['assets.versions.days'] ?? null,
                    'steps' => $values['assets.versions.steps'] ?? null,
                ],
                'hide_edit_image' => $values['assets.hide_edit_image'],
                'disable_tree_preview' => $values['assets.disable_tree_preview'],
            ],
        ];

        //branding
        $settings['pimcore_admin'] = [
            'branding' =>
                [
                    'login_screen_invert_colors' => $values['branding.login_screen_invert_colors'],
                    'color_login_screen' => $values['branding.color_login_screen'],
                    'color_admin_interface' => $values['branding.color_admin_interface'],
                    'color_admin_interface_background' => $values['branding.color_admin_interface_background'],
                    'login_screen_custom_image' => str_replace('%', '%%', $values['branding.login_screen_custom_image']),
                ],
        ];

        if (array_key_exists('email.debug.emailAddresses', $values) && $values['email.debug.emailAddresses']) {
            $settings['pimcore']['email']['debug']['email_addresses'] = $values['email.debug.emailAddresses'];
        }

        if ($existingValues) {
            $saveSettingsEvent = new GenericEvent(null, [
                'settings' => $settings,
                'existingValues' => $existingValues,
                'values' => $values,
            ]);
            $eventDispatcher->dispatch($saveSettingsEvent, AdminEvents::SAVE_ACTION_SYSTEM_SETTINGS);
            $settings = $saveSettingsEvent->getArgument('settings');
        }

        $settingsYaml = Yaml::dump($settings, 5);
        $configFile = Config::locateConfigFile('system.yaml');
        File::put($configFile, $settingsYaml);

        // clear all caches
        $this->clearSymfonyCache($request, $kernel, $eventDispatcher, $symfonyCacheClearer);
        $this->stopMessengerWorkers();

        $eventDispatcher->addListener(KernelEvents::TERMINATE, function (TerminateEvent $event) use (
            $cache, $eventDispatcher, $filesystem
        ) {
            // we need to clear the cache with a delay, because the cache is used by messenger:stop-workers
            // to send the stop signal to all worker processes
            sleep(2);
            $this->clearPimcoreCache($cache, $eventDispatcher, $filesystem);
        });

        return $this->adminJson(['success' => true]);
    }

    /**
     * @param string $source
     * @param array $definitions
     * @param array $fallbacks
     *
     * @throws \Exception
     */
    protected function checkFallbackLanguageLoop(string $source, array $definitions, array $fallbacks = []): void
    {
        if (isset($definitions[$source])) {
            $targets = explode(',', $definitions[$source]);
            foreach ($targets as $l) {
                $target = trim($l);
                if ($target) {
                    if (in_array($target, $fallbacks)) {
                        throw new \Exception("Language `$source` | `$target` causes an infinte loop.");
                    }
                    $fallbacks[] = $target;

                    $this->checkFallbackLanguageLoop($target, $definitions, $fallbacks);
                }
            }
        } else {
            throw new \Exception("Language `$source` doesn't exist");
        }
    }

    /**
     * @Route("/get-web2print", name="pimcore_admin_settings_getweb2print", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getWeb2printAction(Request $request): JsonResponse
    {
        $this->checkPermission('web2print_settings');

        $valueArray = Config::getWeb2PrintConfig();

        $response = [
            'values' => $valueArray,
        ];

        return $this->adminJson($response);
    }

    /**
     * @Route("/set-web2print", name="pimcore_admin_settings_setweb2print", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function setWeb2printAction(Request $request): JsonResponse
    {
        $this->checkPermission('web2print_settings');

        $values = $this->decodeJson($request->get('data'));

        unset($values['documentation']);
        unset($values['additions']);
        unset($values['json_converter']);

        \Pimcore\Web2Print\Config::save($values);

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/clear-cache", name="pimcore_admin_settings_clearcache", methods={"DELETE"})
     *
     * @param Request $request
     * @param KernelInterface $kernel
     * @param EventDispatcherInterface $eventDispatcher
     * @param CoreCacheHandler $cache
     * @param Filesystem $filesystem
     * @param CacheClearer $symfonyCacheClearer
     *
     * @return JsonResponse
     */
    public function clearCacheAction(
        Request $request,
        KernelInterface $kernel,
        EventDispatcherInterface $eventDispatcher,
        CoreCacheHandler $cache,
        Filesystem $filesystem,
        CacheClearer $symfonyCacheClearer
    ): JsonResponse {
        $this->checkPermissionsHasOneOf(['clear_cache', 'system_settings']);

        $result = [
            'success' => true,
        ];

        $clearPimcoreCache = !(bool)$request->get('only_symfony_cache');
        $clearSymfonyCache = !(bool)$request->get('only_pimcore_cache');

        if ($clearPimcoreCache) {
            $this->clearPimcoreCache($cache, $eventDispatcher, $filesystem);
        }

        if ($clearSymfonyCache) {
            $this->clearSymfonyCache($request, $kernel, $eventDispatcher, $symfonyCacheClearer);
        }

        $response = new JsonResponse($result);

        if ($clearSymfonyCache) {
            // we send the response directly here and exit to make sure no code depending on the stale container
            // is running after this
            $response->sendHeaders();
            $response->sendContent();
            exit;
        }

        return $response;
    }

    private function clearPimcoreCache(
        CoreCacheHandler $cache,
        EventDispatcherInterface $eventDispatcher,
        Filesystem $filesystem,
    ): void {
        // empty document cache
        $cache->clearAll();

        if ($filesystem->exists(PIMCORE_CACHE_DIRECTORY)) {
            $filesystem->remove(PIMCORE_CACHE_DIRECTORY);
        }

        // PIMCORE-1854 - recreate .dummy file => should remain
        File::put(PIMCORE_CACHE_DIRECTORY . '/.gitkeep', '');

        $eventDispatcher->dispatch(new GenericEvent(), SystemEvents::CACHE_CLEAR);
    }

    private function clearSymfonyCache(
        Request $request,
        KernelInterface $kernel,
        EventDispatcherInterface $eventDispatcher,
        CacheClearer $symfonyCacheClearer,
    ): void {
        // pass one or move env parameters to clear multiple envs
        // if no env is passed it will use the current one
        $environments = $request->get('env', $kernel->getEnvironment());

        if (!is_array($environments)) {
            $environments = trim((string)$environments);

            if (empty($environments)) {
                $environments = [];
            } else {
                $environments = [$environments];
            }
        }

        if (empty($environments)) {
            $environments = [$kernel->getEnvironment()];
        }

        $result['environments'] = $environments;

        if (in_array($kernel->getEnvironment(), $environments)) {
            // remove terminate and exception event listeners for the current env as they break with a
            // cleared container - see #2434
            foreach ($eventDispatcher->getListeners(KernelEvents::TERMINATE) as $listener) {
                $eventDispatcher->removeListener(KernelEvents::TERMINATE, $listener);
            }

            foreach ($eventDispatcher->getListeners(KernelEvents::EXCEPTION) as $listener) {
                $eventDispatcher->removeListener(KernelEvents::EXCEPTION, $listener);
            }
        }

        foreach ($environments as $environment) {
            try {
                $symfonyCacheClearer->clear($environment);
            } catch (\Throwable $e) {
                $errors = $result['errors'] ?? [];
                $errors[] = $e->getMessage();

                $result = array_merge($result, [
                    'success' => false,
                    'errors' => $errors,
                ]);
            }
        }
    }

    /**
     * @Route("/clear-output-cache", name="pimcore_admin_settings_clearoutputcache", methods={"DELETE"})
     *
     * @param EventDispatcherInterface $eventDispatcher
     *
     * @return JsonResponse
     */
    public function clearOutputCacheAction(EventDispatcherInterface $eventDispatcher): JsonResponse
    {
        $this->checkPermission('clear_fullpage_cache');

        // remove "output" out of the ignored tags, if a cache lifetime is specified
        Cache::removeIgnoredTagOnClear('output');

        // empty document cache
        Cache::clearTags(['output', 'output_lifetime']);

        $eventDispatcher->dispatch(new GenericEvent(), SystemEvents::CACHE_CLEAR_FULLPAGE_CACHE);

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/clear-temporary-files", name="pimcore_admin_settings_cleartemporaryfiles", methods={"DELETE"})
     *
     * @param EventDispatcherInterface $eventDispatcher
     *
     * @return JsonResponse
     */
    public function clearTemporaryFilesAction(EventDispatcherInterface $eventDispatcher): JsonResponse
    {
        $this->checkPermission('clear_temp_files');

        // public files
        Tool\Storage::get('thumbnail')->deleteDirectory('/');
        Db::get()->executeQuery('TRUNCATE TABLE assets_image_thumbnail_cache');

        Tool\Storage::get('asset_cache')->deleteDirectory('/');

        // system files
        recursiveDelete(PIMCORE_SYSTEM_TEMP_DIRECTORY, false);

        $eventDispatcher->dispatch(new GenericEvent(), SystemEvents::CACHE_CLEAR_TEMPORARY_FILES);

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/get-available-admin-languages", name="pimcore_admin_settings_getavailableadminlanguages", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getAvailableAdminLanguagesAction(Request $request): JsonResponse
    {
        $langs = [];
        $availableLanguages = Tool\Admin::getLanguages();
        $locales = Tool::getSupportedLocales();

        foreach ($availableLanguages as $lang) {
            if (array_key_exists($lang, $locales)) {
                $langs[] = [
                    'language' => $lang,
                    'display' => $locales[$lang],
                ];
            }
        }

        usort($langs, function ($a, $b) {
            return strcmp($a['display'], $b['display']);
        });

        return $this->adminJson($langs);
    }

    /**
     * @Route("/get-available-sites", name="pimcore_admin_settings_getavailablesites", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getAvailableSitesAction(Request $request): JsonResponse
    {
        $excludeMainSite = $request->get('excludeMainSite');

        $sitesList = new Model\Site\Listing();
        $sitesObjects = $sitesList->load();
        $sites = [];
        if (!$excludeMainSite) {
            $sites[] = [
                'id' => 0,
                'rootId' => 1,
                'domains' => '',
                'rootPath' => '/',
                'domain' => $this->trans('main_site'),
            ];
        }

        foreach ($sitesObjects as $site) {
            if ($site->getRootDocument()) {
                if ($site->getMainDomain()) {
                    $sites[] = [
                        'id' => $site->getId(),
                        'rootId' => $site->getRootId(),
                        'domains' => implode(',', $site->getDomains()),
                        'rootPath' => $site->getRootPath(),
                        'domain' => $site->getMainDomain(),
                    ];
                }
            } else {
                // site is useless, parent doesn't exist anymore
                $site->delete();
            }
        }

        return $this->adminJson($sites);
    }

    /**
     * @Route("/get-available-countries", name="pimcore_admin_settings_getavailablecountries", methods={"GET"})
     *
     * @param LocaleServiceInterface $localeService
     *
     * @return JsonResponse
     */
    public function getAvailableCountriesAction(LocaleServiceInterface $localeService): JsonResponse
    {
        $countries = $localeService->getDisplayRegions();
        asort($countries);

        $options = [];

        foreach ($countries as $short => $translation) {
            if (strlen($short) == 2) {
                $options[] = [
                    'key' => $translation . ' (' . $short . ')',
                    'value' => $short,
                ];
            }
        }

        $result = ['data' => $options, 'success' => true, 'total' => count($options)];

        return $this->adminJson($result);
    }

    /**
     * @Route("/thumbnail-adapter-check", name="pimcore_admin_settings_thumbnailadaptercheck", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function thumbnailAdapterCheckAction(Request $request): Response
    {
        $content = '';

        $instance = \Pimcore\Image::getInstance();
        if ($instance instanceof \Pimcore\Image\Adapter\GD) {
            $content = '<span style="color: red; font-weight: bold;padding: 10px;margin:0 0 20px 0;border:1px solid red;display:block;">' .
                $this->trans('important_use_imagick_pecl_extensions_for_best_results_gd_is_just_a_fallback_with_less_quality') .
                '</span>';
        }

        return new Response($content);
    }

    /**
     * @Route("/thumbnail-tree", name="pimcore_admin_settings_thumbnailtree", methods={"GET", "POST"})
     *
     * @return JsonResponse
     */
    public function thumbnailTreeAction(): JsonResponse
    {
        $this->checkPermission('thumbnails');

        $thumbnails = [];

        $list = new Asset\Image\Thumbnail\Config\Listing();

        $groups = [];
        foreach ($list->getThumbnails() as $item) {
            if ($item->getGroup()) {
                if (empty($groups[$item->getGroup()])) {
                    $groups[$item->getGroup()] = [
                        'id' => 'group_' . $item->getName(),
                        'text' => htmlspecialchars($item->getGroup()),
                        'expandable' => true,
                        'leaf' => false,
                        'allowChildren' => true,
                        'iconCls' => 'pimcore_icon_folder',
                        'group' => $item->getGroup(),
                        'children' => [],
                    ];
                }
                $groups[$item->getGroup()]['children'][] =
                    [
                        'id' => $item->getName(),
                        'text' => $item->getName(),
                        'leaf' => true,
                        'iconCls' => 'pimcore_icon_thumbnails',
                        'cls' => 'pimcore_treenode_disabled',
                        'writeable' => $item->isWriteable(),
                    ];
            } else {
                $thumbnails[] = [
                    'id' => $item->getName(),
                    'text' => $item->getName(),
                    'leaf' => true,
                    'iconCls' => 'pimcore_icon_thumbnails',
                    'cls' => 'pimcore_treenode_disabled',
                    'writeable' => $item->isWriteable(),
                ];
            }
        }

        foreach ($groups as $group) {
            $thumbnails[] = $group;
        }

        return $this->adminJson($thumbnails);
    }

    /**
     * @Route("/thumbnail-downloadable", name="pimcore_admin_settings_thumbnaildownloadable", methods={"GET"})
     *
     * @return JsonResponse
     */
    public function thumbnailDownloadableAction(): JsonResponse
    {
        $thumbnails = [];

        $list = new Asset\Image\Thumbnail\Config\Listing();
        $list->setFilter(function (Asset\Image\Thumbnail\Config $config) {
            return $config->isDownloadable();
        });

        foreach ($list->getThumbnails() as $item) {
            $thumbnails[] = [
                'id' => $item->getName(),
                'text' => $item->getName(),
            ];
        }

        return $this->adminJson($thumbnails);
    }

    /**
     * @Route("/thumbnail-add", name="pimcore_admin_settings_thumbnailadd", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function thumbnailAddAction(Request $request): JsonResponse
    {
        $this->checkPermission('thumbnails');

        $success = false;

        $pipe = Asset\Image\Thumbnail\Config::getByName($request->get('name'));

        if (!$pipe) {
            $pipe = new Asset\Image\Thumbnail\Config();
            if (!$pipe->isWriteable()) {
                throw new ConfigWriteException();
            }

            $pipe->setName($request->get('name'));
            $pipe->save();

            $success = true;
        } else {
            if (!$pipe->isWriteable()) {
                throw new ConfigWriteException();
            }
        }

        return $this->adminJson(['success' => $success, 'id' => $pipe->getName()]);
    }

    /**
     * @Route("/thumbnail-delete", name="pimcore_admin_settings_thumbnaildelete", methods={"DELETE"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function thumbnailDeleteAction(Request $request): JsonResponse
    {
        $this->checkPermission('thumbnails');

        $pipe = Asset\Image\Thumbnail\Config::getByName($request->get('name'));

        if (!$pipe->isWriteable()) {
            throw new ConfigWriteException();
        }

        $pipe->delete();

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/thumbnail-get", name="pimcore_admin_settings_thumbnailget", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function thumbnailGetAction(Request $request): JsonResponse
    {
        $this->checkPermission('thumbnails');

        $pipe = Asset\Image\Thumbnail\Config::getByName($request->get('name'));
        $data = $pipe->getObjectVars();
        $data['writeable'] = $pipe->isWriteable();

        return $this->adminJson($data);
    }

    /**
     * @Route("/thumbnail-update", name="pimcore_admin_settings_thumbnailupdate", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function thumbnailUpdateAction(Request $request): JsonResponse
    {
        $this->checkPermission('thumbnails');

        $pipe = Asset\Image\Thumbnail\Config::getByName($request->get('name'));

        if (!$pipe->isWriteable()) {
            throw new ConfigWriteException();
        }

        $settingsData = $this->decodeJson($request->get('settings'));
        $mediaData = $this->decodeJson($request->get('medias'));
        $mediaOrder = $this->decodeJson($request->get('mediaOrder'));

        foreach ($settingsData as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($pipe, $setter)) {
                $pipe->$setter($value);
            }
        }

        $pipe->resetItems();

        uksort($mediaData, function ($a, $b) use ($mediaOrder) {
            if ($a === 'default') {
                return -1;
            }

            return ($mediaOrder[$a] < $mediaOrder[$b]) ? -1 : 1;
        });

        foreach ($mediaData as $mediaName => $items) {
            if (preg_match('/["<>]/', $mediaName)) {
                throw new \Exception('Invalid media query name');
            }

            foreach ($items as $item) {
                $type = $item['type'];
                unset($item['type']);

                $pipe->addItem($type, $item, $mediaName);
            }
        }

        $pipe->save();

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/video-thumbnail-adapter-check", name="pimcore_admin_settings_videothumbnailadaptercheck", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function videoThumbnailAdapterCheckAction(Request $request): Response
    {
        $content = '';

        if (!\Pimcore\Video::isAvailable()) {
            $content = '<span style="color: red; font-weight: bold;padding: 10px;margin:0 0 20px 0;border:1px solid red;display:block;">' .
                $this->trans('php_cli_binary_and_or_ffmpeg_binary_setting_is_missing') .
                '</span>';
        }

        return new Response($content);
    }

    /**
     * @Route("/video-thumbnail-tree", name="pimcore_admin_settings_videothumbnailtree", methods={"GET", "POST"})
     *
     * @return JsonResponse
     */
    public function videoThumbnailTreeAction(): JsonResponse
    {
        $this->checkPermission('thumbnails');

        $thumbnails = [];

        $list = new Asset\Video\Thumbnail\Config\Listing();

        $groups = [];
        foreach ($list->getThumbnails() as $item) {
            if ($item->getGroup()) {
                if (empty($groups[$item->getGroup()])) {
                    $groups[$item->getGroup()] = [
                        'id' => 'group_' . $item->getName(),
                        'text' => htmlspecialchars($item->getGroup()),
                        'expandable' => true,
                        'leaf' => false,
                        'allowChildren' => true,
                        'iconCls' => 'pimcore_icon_folder',
                        'group' => $item->getGroup(),
                        'children' => [],
                    ];
                }
                $groups[$item->getGroup()]['children'][] =
                    [
                        'id' => $item->getName(),
                        'text' => $item->getName(),
                        'leaf' => true,
                        'iconCls' => 'pimcore_icon_videothumbnails',
                        'cls' => 'pimcore_treenode_disabled',
                        'writeable' => $item->isWriteable(),
                    ];
            } else {
                $thumbnails[] = [
                    'id' => $item->getName(),
                    'text' => $item->getName(),
                    'leaf' => true,
                    'iconCls' => 'pimcore_icon_videothumbnails',
                    'cls' => 'pimcore_treenode_disabled',
                    'writeable' => $item->isWriteable(),
                ];
            }
        }

        foreach ($groups as $group) {
            $thumbnails[] = $group;
        }

        return $this->adminJson($thumbnails);
    }

    /**
     * @Route("/video-thumbnail-list", name="pimcore_admin_settings_videothumbnail_list", methods={"GET"})
     *
     * @return JsonResponse
     */
    public function videoThumbnailListAction(): JsonResponse
    {
        $thumbnails = [
            ['id' => 'pimcore-system-treepreview', 'text' => 'original'],
        ];
        $list = new Asset\Video\Thumbnail\Config\Listing();

        foreach ($list->getThumbnails() as $item) {
            $thumbnails[] = [
                'id'   => $item->getName(),
                'text' => $item->getName(),
            ];
        }

        return $this->adminJson($thumbnails);
    }

    /**
     * @Route("/video-thumbnail-add", name="pimcore_admin_settings_videothumbnailadd", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function videoThumbnailAddAction(Request $request): JsonResponse
    {
        $this->checkPermission('thumbnails');

        $success = false;

        $pipe = Asset\Video\Thumbnail\Config::getByName($request->get('name'));

        if (!$pipe) {
            $pipe = new Asset\Video\Thumbnail\Config();
            if (!$pipe->isWriteable()) {
                throw new ConfigWriteException();
            }

            $pipe->setName($request->get('name'));
            $pipe->save();

            $success = true;
        } else {
            if (!$pipe->isWriteable()) {
                throw new ConfigWriteException();
            }
        }

        return $this->adminJson(['success' => $success, 'id' => $pipe->getName()]);
    }

    /**
     * @Route("/video-thumbnail-delete", name="pimcore_admin_settings_videothumbnaildelete", methods={"DELETE"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function videoThumbnailDeleteAction(Request $request): JsonResponse
    {
        $this->checkPermission('thumbnails');

        $pipe = Asset\Video\Thumbnail\Config::getByName($request->get('name'));

        if (!$pipe->isWriteable()) {
            throw new ConfigWriteException();
        }

        $pipe->delete();

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/video-thumbnail-get", name="pimcore_admin_settings_videothumbnailget", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function videoThumbnailGetAction(Request $request): JsonResponse
    {
        $this->checkPermission('thumbnails');

        $pipe = Asset\Video\Thumbnail\Config::getByName($request->get('name'));

        $data = $pipe->getObjectVars();
        $data['writeable'] = $pipe->isWriteable();

        return $this->adminJson($data);
    }

    /**
     * @Route("/video-thumbnail-update", name="pimcore_admin_settings_videothumbnailupdate", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function videoThumbnailUpdateAction(Request $request): JsonResponse
    {
        $this->checkPermission('thumbnails');

        $pipe = Asset\Video\Thumbnail\Config::getByName($request->get('name'));

        if (!$pipe->isWriteable()) {
            throw new ConfigWriteException();
        }

        $settingsData = $this->decodeJson($request->get('settings'));
        $mediaData = $this->decodeJson($request->get('medias'));
        $mediaOrder = $this->decodeJson($request->get('mediaOrder'));

        foreach ($settingsData as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (method_exists($pipe, $setter)) {
                $pipe->$setter($value);
            }
        }

        $pipe->resetItems();

        uksort($mediaData, function ($a, $b) use ($mediaOrder) {
            if ($a === 'default') {
                return -1;
            }

            return ($mediaOrder[$a] < $mediaOrder[$b]) ? -1 : 1;
        });

        foreach ($mediaData as $mediaName => $items) {
            foreach ($items as $item) {
                $type = $item['type'];
                unset($item['type']);

                $pipe->addItem($type, $item, $mediaName);
            }
        }

        $pipe->save();

        return $this->adminJson(['success' => true]);
    }

    /**
     * @Route("/website-settings", name="pimcore_admin_settings_websitesettings", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function websiteSettingsAction(Request $request): JsonResponse
    {
        $this->checkPermission('website_settings');

        if ($request->get('data')) {
            $data = $this->decodeJson($request->get('data'));

            if (is_array($data)) {
                foreach ($data as &$value) {
                    if (is_string($value)) {
                        $value = trim($value);
                    }
                }
            }

            if ($request->get('xaction') == 'destroy') {
                $id = $data['id'];
                $setting = WebsiteSetting::getById($id);
                if ($setting instanceof WebsiteSetting) {
                    $setting->delete();

                    return $this->adminJson(['success' => true, 'data' => []]);
                }
            } elseif ($request->get('xaction') == 'update') {
                // save routes
                $setting = WebsiteSetting::getById($data['id']);
                if ($setting instanceof WebsiteSetting) {
                    switch ($setting->getType()) {
                        case 'document':
                        case 'asset':
                        case 'object':
                            if (isset($data['data'])) {
                                $element = Element\Service::getElementByPath($setting->getType(), $data['data']);
                                $data['data'] = $element;
                            }

                            break;
                    }

                    $setting->setValues($data);
                    $setting->save();

                    $data = $this->getWebsiteSettingForEditMode($setting);

                    return $this->adminJson(['data' => $data, 'success' => true]);
                }
            } elseif ($request->get('xaction') == 'create') {
                unset($data['id']);

                // save route
                $setting = new WebsiteSetting();
                $setting->setValues($data);

                $setting->save();

                return $this->adminJson(['data' => $setting->getObjectVars(), 'success' => true]);
            }
        } else {
            $list = new WebsiteSetting\Listing();

            $list->setLimit((int) $request->get('limit', 50));
            $list->setOffset((int) $request->get('start', 0));

            $sortingSettings = \Pimcore\Bundle\AdminBundle\Helper\QueryParams::extractSortingSettings(array_merge($request->request->all(), $request->query->all()));
            if ($sortingSettings['orderKey']) {
                $list->setOrderKey($sortingSettings['orderKey']);
                $list->setOrder($sortingSettings['order']);
            } else {
                $list->setOrderKey('name');
                $list->setOrder('asc');
            }

            if ($request->get('filter')) {
                $list->setCondition('`name` LIKE ' . $list->quote('%'.$request->get('filter').'%'));
            }

            $totalCount = $list->getTotalCount();
            $list = $list->load();

            $settings = [];
            foreach ($list as $item) {
                $resultItem = $this->getWebsiteSettingForEditMode($item);
                $settings[] = $resultItem;
            }

            return $this->adminJson(['data' => $settings, 'success' => true, 'total' => $totalCount]);
        }

        return $this->adminJson(['success' => false]);
    }

    /**
     * @return array{id: ?int, name: string, language: string, type: string, data: mixed, siteId: ?int, creationDate: ?int, modificationDate: ?int}
     */
    private function getWebsiteSettingForEditMode(WebsiteSetting $item): array
    {
        $resultItem = [
            'id' => $item->getId(),
            'name' => $item->getName(),
            'language' => $item->getLanguage(),
            'type' => $item->getType(),
            'data' => null,
            'siteId' => $item->getSiteId(),
            'creationDate' => $item->getCreationDate(),
            'modificationDate' => $item->getModificationDate(),
        ];

        switch ($item->getType()) {
            case 'document':
            case 'asset':
            case 'object':
                $element = $item->getData();
                if ($element) {
                    $resultItem['data'] = $element->getRealFullPath();
                }

                break;
            default:
                $resultItem['data'] = $item->getData();

                break;
        }

        return $resultItem;
    }

    /**
     * @Route("/get-available-algorithms", name="pimcore_admin_settings_getavailablealgorithms", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getAvailableAlgorithmsAction(Request $request): JsonResponse
    {
        $options = [
            [
                'key' => 'password_hash',
                'value' => 'password_hash',
            ],
        ];

        $algorithms = hash_algos();
        foreach ($algorithms as $algorithm) {
            $options[] = [
                'key' => $algorithm,
                'value' => $algorithm,
            ];
        }

        $result = ['data' => $options, 'success' => true, 'total' => count($options)];

        return $this->adminJson($result);
    }

    /**
     * deleteViews
     * delete views for localized fields when languages are removed to
     * prevent mysql errors
     *
     * @param string $language
     * @param string $dbName
     */
    protected function deleteViews(string $language, string $dbName): void
    {
        $db = \Pimcore\Db::get();
        $views = $db->fetchAllAssociative('SHOW FULL TABLES IN ' . $db->quoteIdentifier($dbName) . " WHERE TABLE_TYPE LIKE 'VIEW'");

        foreach ($views as $view) {
            if (preg_match('/^object_localized_[0-9]+_' . $language . '$/', $view['Tables_in_' . $dbName])) {
                $sql = 'DROP VIEW ' . $db->quoteIdentifier($view['Tables_in_' . $dbName]);
                $db->executeQuery($sql);
            }
        }
    }

    /**
     * @Route("/test-web2print", name="pimcore_admin_settings_testweb2print", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function testWeb2printAction(Request $request): Response
    {
        $this->checkPermission('web2print_settings');

        $response = $this->render('@PimcoreAdmin/admin/settings/test_web2print.html.twig');
        $html = $response->getContent();

        $adapter = \Pimcore\Web2Print\Processor::getInstance();
        $params = [];

        if ($adapter instanceof \Pimcore\Web2Print\Processor\PdfReactor) {
            $params['adapterConfig'] = [
                'javaScriptMode' => 0,
                'addLinks' => true,
                'appendLog' => true,
                'enableDebugMode' => true,
            ];
        } elseif ($adapter instanceof \Pimcore\Web2Print\Processor\HeadlessChrome) {
            $params = Config::getWeb2PrintConfig();
            $params = json_decode($params['headlessChromeSettings'], true) ?: [];
        }

        $responseOptions = [
            'Content-Type' => 'application/pdf',
        ];

        $pdfData = $adapter->getPdfFromString($html, $params);

        return new \Symfony\Component\HttpFoundation\Response(
            $pdfData,
            200,
            $responseOptions

        );
    }
}
