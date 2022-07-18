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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http:/ /www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\AdminBundle\Helper;

use Pimcore\Localization\LocaleService;
use Pimcore\Bundle\AdminBundle\Security\ContentSecurityPolicyHandler;
use Pimcore\Extension\Bundle\PimcoreBundleManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Pimcore\Version;

/**
 * @internal
 */
class AdminJsHelperService
{
    /**
     * @var string[]
     */
    protected array $libScriptPaths;

    /**
     * @var string[]
     */
    protected array $internalScriptPaths;

    /**
     * @var string[]
     */
    protected array $bundleScriptPaths;

    /**
     * @var string
     */
    protected $jsCacheDir;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @array
     */
    protected const WEBROOTPATHS = [
        PIMCORE_WEB_ROOT,
        PIMCORE_WEB_ROOT . '/bundles/pimcoreadmin/js/'
    ];
    protected const MINIFIED_SCRIPT_FILENAME = '_minified_javascript_core.js';
    protected const EXTRA_SCRIPT_FILENAME = 'extra_script_paths.txt';
    protected const SCRIPT_INTERNAL_PREFIX = 'internal';
    protected const SCRIPT_BUNDLE_PREFIX = 'bundle';
    protected const SCRIPT_PATH = '/bundles/pimcoreadmin/js/';

    /**
     * @param PimcoreBundleManager $pimcoreBundleManager
     * @param ContentSecurityPolicyHandler $contentSecurityPolicyHandler
     * @param LocaleService $localeService
     */
    public function __construct (
        private PimcoreBundleManager $pimcoreBundleManager,
        private ContentSecurityPolicyHandler $contentSecurityPolicyHandler,
        private LocaleService $localeService,
        array $libScriptPaths = [],
        array $internalScriptPaths = []
    ) {
        $this->libScriptPaths = $libScriptPaths;
        $this->internalScriptPaths = $internalScriptPaths;
        $this->initLibScriptPaths();

        $this->filesystem = new Filesystem();
        $this->jsCacheDir = \Pimcore::getKernel()->getCacheDir() . '/minifiedJs/';

    }

    public function initLibScriptPaths()
    {
        // Add the suffix 'debug' to ext-all.js script path if the environment is not dev
        $debugSuffix = '';
        if (\Pimcore::disableMinifyJs()) {
            $debugSuffix = "-debug";
        }
        $this->libScriptPaths = str_replace ("../extjs/js/ext-all.js", "../extjs/js/ext-all$debugSuffix.js", $this->libScriptPaths);

        // Include the js file according to the locale
        $language = $this->localeService->getLocale();
        if (file_exists (PIMCORE_WEB_ROOT . '/bundles/pimcoreadmin/js/lib/ext-locale/locale-' . $language . '.js')) {
            array_push ($this->libScriptPaths, 'lib/ext-locale/locale-' . $language . '.js');
        }
    }

    /**
     * @return string[]
     */
    public function getBundleJsPaths(): array
    {
        if (!isset($this->bundleScriptPaths)) {
            $this->bundleScriptPaths = $this->pimcoreBundleManager->getJsPaths();
        }

        return $this->bundleScriptPaths;
    }

    /**
     * @param bool $setDcVersion
     *
     * @return array
     */
    public function getLibScriptPaths(bool $setDcVersion = true): array
    {
        $setDcVersionText = ($setDcVersion) ? $this->getDcText() : '';

        return $this->getFormattedScripts($setDcVersionText . $this->getNonceText(), $this->libScriptPaths, self::SCRIPT_PATH);
    }

    /**
     * @param bool $setDcVersion
     *
     * @return array
     */
    public function getBundleScriptPaths(bool $setDcVersion = true): array
    {
        if (\Pimcore::disableMinifyJs()) {
            $setDcVersionText = ($setDcVersion) ? $this->getDcText('1') : '';

            return $this->getFormattedScripts($setDcVersionText . $this->getNoncetext(), $this->getBundleJsPaths());
        }

        return $this->getMinifiedScriptPaths(self::SCRIPT_BUNDLE_PREFIX, $this->getBundleJsPaths());
    }

    /**
     * @param bool $setDcVersion
     *
     * @return array
     */
    public function getInternalScriptPaths(bool $setDcVersion = true): array
    {
        if (\Pimcore::disableMinifyJs()) {
            $setDcVersionText = ($setDcVersion) ? $this->getDcText('1') : '';

            return $this->getFormattedScripts($setDcVersionText, $this->internalScriptPaths, self::SCRIPT_PATH);
        }

        return $this->getMinifiedScriptPaths(self::SCRIPT_INTERNAL_PREFIX, $this->internalScriptPaths);
    }

    /**
     * Get the scripts/files that doesn't exists in the WEBROOTPATHS from the file
     *
     * @return array
     */
    public function getExtraScriptPaths (): array
    {
        if (!\Pimcore::disableMinifyJs ()) {
            if ($this->filesystem->exists ($this->jsCacheDir . self::EXTRA_SCRIPT_FILENAME)) {
                $extraScriptPaths = trim(file_get_contents ($this->jsCacheDir . self::EXTRA_SCRIPT_FILENAME));
                return explode (PHP_EOL, $extraScriptPaths);
            }
        }
        return [];
    }

    /**
     * Get either pre-generated or runtime generated minified JS script paths
     *
     * @param string $prefix
     * @param array $scriptPaths
     *
     * @return array
     */
    public function getMinifiedScriptPaths(string $prefix, array $scriptPaths): array
    {
        $storageFile = $this->getMinifiedScriptFileName($prefix);

        if (!$this->isMinifiedScriptExists($storageFile)) {
            $storageFile = $this->minifyAndSaveJs($scriptPaths, $storageFile);
        }

        return [
            'storageFile' => basename ($storageFile),
            '_dc' => Version::getRevision()
        ];
    }

    /**
     * @param string $prefix
     *
     * @return string
     */
    public function getMinifiedScriptFileName(string $prefix): string
    {
        return $prefix . self::MINIFIED_SCRIPT_FILENAME;
    }

    /**
     * @param $storageFile
     *
     * returns false when script doesn't exist and path when exist
     *
     * @return bool|string
     */
    public function isMinifiedScriptExists($storageFile): bool|string
    {
        if ($this->filesystem->exists($this->jsCacheDir . $storageFile)) {
            return $this->jsCacheDir . $storageFile;
        }

        return false;
    }

    /**
     * Add prefixes like the path from root and postfixes like dc_version, nonce etc to the script paths
     *
     * @param string $postFixText
     * @param array $scripts
     * @param string $setPrefixText
     *
     * @return array
     */
    public function getFormattedScripts(string $postFixText, array $scripts, string $setPrefixText = ''): array
    {
        return array_map(function ($eachScriptPath) use ($setPrefixText, $postFixText) {
            return $setPrefixText . $eachScriptPath . $postFixText;
        }, $scripts);
    }

    /**
     * Minify all the script files passed to a single js script file
     *
     * @param array $jsScripts
     * @param string $storageFile
     *
     * @return string
     */
    protected function minifyAndSaveJs(array $jsScripts, string $storageFile): string
    {
        $scriptContents = '';
        foreach ($jsScripts as $scriptPath) {
            $found = false;
            foreach (self::WEBROOTPATHS as $webRootPath) {
                $fullPath = $webRootPath . $scriptPath;
                if (file_exists ($fullPath)) {
                    $scriptContents .= file_get_contents ($fullPath) . "\n\n\n";
                    $found = true;
                }
            }
            // Write additional script paths like `settings-json`, `config/js-config` to an extra file
            if(!$found) {
                $this->writeToFile ($this->jsCacheDir, self::EXTRA_SCRIPT_FILENAME, $scriptPath. PHP_EOL,true);
            }
        }

        if ($this->writeToFile($this->jsCacheDir, $storageFile, $scriptContents)) {
            return $storageFile;
        }

        return '';
    }

    /**
     * @param string $dirPath
     * @param string $fileName
     * @param string $scriptContent
     * @param bool $append
     *
     * @return bool
     */
    private function writeToFile(string $dirPath, string $fileName,string $scriptContent, bool $append = false): bool
    {
        try {
            $fileName = $dirPath . $fileName;
            if($append)
                $this->filesystem->appendToFile ($fileName, $scriptContent);
            else
                $this->filesystem->dumpFile($fileName, $scriptContent);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @param string $dcVal
     *
     * @return string
     */
    protected function getDcText(string $dcVal = ''): string
    {
        if (!$dcVal) {
            $dcVal = Version::getRevision();
        }

        return '?_dc=' . $dcVal . '"';
    }

    /**
     * @return string
     */
    private function getNonceText(): string
    {
        return $this->contentSecurityPolicyHandler->getNonceHtmlAttribute();
    }

    /**
     * Todo: Check if this is possible as Extension\Config service is synthetic
     *
     * Warm up the js_cache folder on cache:warmup command
     *
     * @param string $cacheDir
     *
     * @return array|string[]
     */
    public function warmUp (string $cacheDir): array
    {
        if (!\Pimcore::disableMinifyJs()) {
            $storagePaths = [];

            foreach ([
                         $this->getMinifiedScriptFileName(self::SCRIPT_INTERNAL_PREFIX) => $this->internalScriptPaths,
                         $this->getMinifiedScriptFileName(self::SCRIPT_BUNDLE_PREFIX) => $this->getBundleJsPaths()
                     ] as $filename => $scripts) {
                $minifiedPaths = $this->minifyAndSaveJs($scripts, $filename);
                $storagePaths[] = $this->jsCacheDir . $minifiedPaths;
            }

            return $storagePaths;
        }

        return [];
    }
}


