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

namespace Pimcore;

use Symfony\Component\Process\Process;

class Update
{
    /**
     * @var string
     */
    private static $updateHost = 'liveupdate.pimcore.org';

    /**
     * @var bool
     */
    public static $dryRun = false;

    /**
     * @var string
     */
    public static $tmpTable = '_tmp_update';

    /**
     * @return bool
     */
    public static function isWriteable()
    {
        if (self::$dryRun) {
            return true;
        }

        // check permissions
        $files = rscandir(PIMCORE_PATH . '/');

        foreach ($files as $file) {
            if (!is_writable($file)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param null $currentRev
     *
     * @return array
     *
     * @throws \Exception
     */
    public static function getAvailableUpdates($currentRev = null)
    {
        if (!$currentRev) {
            $currentRev = Version::$revision;
        }

        self::cleanup();

        $updateInfoUrl = 'https://' . self::$updateHost . '/get-update-info?revision=' . $currentRev;
        if (PIMCORE_DEVMODE) {
            $updateInfoUrl .= '&devmode=1';
        }

        $xmlRaw = Tool::getHttpData($updateInfoUrl);

        $xml = simplexml_load_string($xmlRaw, null, LIBXML_NOCDATA);

        $revisions = [];
        $releases = [];
        if ($xml instanceof \SimpleXMLElement) {
            if (isset($xml->revision)) {
                foreach ($xml->revision as $r) {
                    $date = new \DateTime();
                    $date->setTimestamp((int) $r->date);

                    if (strlen(strval($r->version)) > 0) {
                        $releases[] = [
                            'id' => strval($r->id),
                            'date' => strval($r->date),
                            'version' => strval($r->version),
                            'text' => strval($r->id) . ' - ' . $date->format('Y-m-d H:i')
                        ];
                    } else {
                        $revisions[] = [
                            'id' => strval($r->id),
                            'date' => strval($r->date),
                            'text' => strval($r->id) . ' - ' . $date->format('Y-m-d H:i')
                        ];
                    }
                }
            }
        } else {
            throw new \Exception('Unable to retrieve response from update server. Please ensure that your server is allowed to connect to ' . self::$updateHost . ':443');
        }

        return [
            'revisions' => $revisions,
            'releases' => $releases
        ];
    }

    /**
     * @param $toRevision
     * @param null $currentRev
     *
     * @return array
     */
    public static function getJobs($toRevision, $currentRev = null)
    {
        if (!$currentRev) {
            $currentRev = Version::$revision;
        }

        $xmlRaw = Tool::getHttpData('https://' . self::$updateHost . '/get-downloads?from=' . $currentRev . '&to=' . $toRevision);
        $xml = simplexml_load_string($xmlRaw, null, LIBXML_NOCDATA);

        $downloadJobs = [];
        $updateJobs = [];
        $updateScripts = [];
        $composerUpdateRevisions = [];
        $revisions = [];

        if (isset($xml->download)) {
            foreach ($xml->download as $download) {
                if ($download->type == 'script') {
                    $updateScripts[(string) $download->revision]['preupdate'] = [
                        'type' => 'preupdate',
                        'revision' => (string) $download->revision
                    ];
                    $updateScripts[(string) $download->revision]['postupdate'] = [
                        'type' => 'postupdate',
                        'revision' => (string) $download->revision
                    ];
                } elseif ((string) $download->composer === 'true') {
                    $composerUpdateRevisions[(string) $download->revision] = (string) $download->revision;
                }
            }
        }

        if (isset($xml->download)) {
            foreach ($xml->download as $download) {
                $downloadJobs[] = [
                    'type' => 'download',
                    'revision' => (string) $download->revision,
                    'url' => (string) $download->url
                ];

                $revisions[] = (int) $download->revision;
            }
        }

        $revisions = array_unique($revisions);

        $updateJobs[] = [
            'type' => 'composer-invalidate-classmaps'
        ];

        foreach ($revisions as $revision) {
            if ($updateScripts[$revision]['preupdate']) {
                $updateJobs[] = $updateScripts[$revision]['preupdate'];
            }

            $updateJobs[] = [
                'type' => 'files',
                'revision' => $revision,
                'updateScript' => json_encode(isset($updateScripts[$revision]['update']))
            ];

            if ($updateScripts[$revision]['postupdate']) {
                $updateJobs[] = $updateScripts[$revision]['postupdate'];
            }
        }

        $updateJobs[] = [
            'type' => 'clearcache'
        ];

        $updateJobs[] = [
            'type' => 'cleanup'
        ];

        $updateJobs[] = [
            'type' => 'composer-update'
        ];

        return [
            'update' => $updateJobs,
            'download' => $downloadJobs
        ];
    }

    /**
     * @param $revision
     * @param $url
     *
     * @throws \Exception
     */
    public static function downloadData($revision, $url)
    {
        $db = Db::get();

        $db->query('CREATE TABLE IF NOT EXISTS `' . self::$tmpTable . '` (
          `id` int(11) NULL DEFAULT NULL,
          `revision` int(11) NULL DEFAULT NULL,
          `path` varchar(255) NULL DEFAULT NULL,
          `action` varchar(50) NULL DEFAULT NULL
        );');

        $downloadDir = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/update/'.$revision;
        if (!is_dir($downloadDir)) {
            File::mkdir($downloadDir);
        }

        $filesDir = $downloadDir . '/files';
        if (!is_dir($filesDir)) {
            File::mkdir($filesDir);
        }

        $scriptsDir = $downloadDir . '/scripts';
        if (!is_dir($scriptsDir)) {
            File::mkdir($scriptsDir);
        }

        $xml = Tool::getHttpData($url);
        if ($xml) {
            $parserOptions = LIBXML_NOCDATA;
            if (defined('LIBXML_PARSEHUGE')) {
                $parserOptions = LIBXML_NOCDATA | LIBXML_PARSEHUGE;
            }

            $updateFiles = simplexml_load_string($xml, null, $parserOptions);

            foreach ($updateFiles->file as $file) {
                if ($file->type == 'file') {
                    if ($file->action == 'update' || $file->action == 'add') {
                        $newFile = $filesDir . '/' . $file->id . '-' . $file->revision;
                        File::put($newFile, base64_decode((string) $file->content));
                    }

                    $db->insert(self::$tmpTable, [
                        'id' => $file->id,
                        'revision' => $revision,
                        'path' => (string) $file->path,
                        'action' => (string)$file->action
                    ]);
                } elseif ($file->type == 'script') {
                    $newScript = $scriptsDir. $file->path;
                    File::put($newScript, base64_decode((string) $file->content));
                }
            }
        }
    }

    /**
     * @param $revision
     */
    public static function installData($revision, $updateScript)
    {
        $db = Db::get();
        $files = $db->fetchAll('SELECT * FROM `' . self::$tmpTable . '` WHERE revision = ?', [$revision]);

        foreach ($files as $file) {
            if ($file['action'] == 'update' || $file['action'] == 'add') {
                if (!is_dir(dirname(PIMCORE_PROJECT_ROOT . $file['path']))) {
                    if (!self::$dryRun) {
                        File::mkdir(dirname(PIMCORE_PROJECT_ROOT . $file['path']));
                    }
                }

                if (array_key_exists('id', $file) && $file['id']) {
                    // this is the new style
                    $srcFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/update/'.$revision.'/files/' . $file['id'] . '-' . $file['revision'];
                } else {
                    // this is the old style, which we still have to support here, otherwise there's the risk that the
                    // running update cannot be finished
                    $srcFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/update/'.$revision.'/files/' . str_replace('/', '~~~', $file['path']);
                }

                $destFile = PIMCORE_PROJECT_ROOT . $file['path'];

                if (!self::$dryRun) {
                    if ($file['path'] == '/composer.json') {
                        // composer.json needs some special processing
                        self::installComposerJson($srcFile, PIMCORE_COMPOSER_FILE_PATH . $file['path']);
                    } else {
                        copy($srcFile, $destFile);
                    }
                }

                // set the timestamp 10s to the future, to ensure the container refreshes if there's any relevant change
                touch($destFile, time() + 10);
            } elseif ($file['action'] == 'delete') {
                if (!self::$dryRun) {
                    if (file_exists(PIMCORE_PROJECT_ROOT . $file['path'])) {
                        unlink(PIMCORE_PROJECT_ROOT . $file['path']);
                    }

                    clearstatcache();

                    // remove also directory if its empty
                    if (count(glob(dirname(PIMCORE_PROJECT_ROOT . $file['path']) . '/*')) === 0) {
                        recursiveDelete(dirname(PIMCORE_PROJECT_ROOT . $file['path']), true);
                    }
                }
            }
        }

        // run update script
        if ($updateScript == 'true') {
            self::executeScript($revision, 'update');
        }

        self::clearOPCaches();
    }

    /**
     * @param $revision
     * @param $type
     *
     * @return array
     */
    public static function executeScript($revision, $type)
    {
        $script = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/update/'.$revision . '/scripts/' . $type . '.php';

        $maxExecutionTime = 900;
        @ini_set('max_execution_time', $maxExecutionTime);
        set_time_limit($maxExecutionTime);

        Cache::disable(); // it's important to disable the cache here eg. db-schemas, ...

        $outputMessage = '';
        if (is_file($script)) {
            ob_start();
            try {
                if (!self::$dryRun) {
                    include($script);
                }
            } catch (\Exception $e) {
                Logger::error($e);
                $outputMessage .= 'EXCEPTION: ' . $e->getMessage();
                $outputMessage .= '<br>For details please have a look into debug.log<br>';
            }
            $outputMessage .= ob_get_clean();
        }

        self::clearOPCaches();

        return [
            'message' => $outputMessage,
            'success' => true
        ];
    }

    /**
     * @param $newFile
     * @param $oldFile
     */
    public static function installComposerJson($newFile, $oldFile)
    {
        $existingContents = file_get_contents($oldFile);
        $newContents = file_get_contents($newFile);

        $existingContents = json_decode($existingContents, true);
        $newContents = json_decode($newContents, true);

        if ($existingContents && $newContents) {
            $mergeResult = array_replace_recursive($existingContents, $newContents);
            $newJson = json_encode($mergeResult);
            $newJson = \Pimcore\Helper\JsonFormatter::format($newJson, true, true);
            File::put($oldFile, $newJson);
        }

        self::composerUpdate(['--no-scripts']);
    }

    public static function clearOPCaches()
    {
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }

    public static function cleanup()
    {

        // remove database tmp table
        $db = Db::get();
        $db->query('DROP TABLE IF EXISTS `' . self::$tmpTable . '`');

        //delete tmp data
        recursiveDelete(PIMCORE_SYSTEM_TEMP_DIRECTORY . '/update', true);
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public static function composerUpdate($options = [])
    {
        $composerLock = PIMCORE_COMPOSER_FILE_PATH . '/composer.lock';
        if (file_exists($composerLock)) {
            @unlink($composerLock);
        }

        $outputMessage = '';

        try {
            $composerPath = \Pimcore\Tool\Console::getExecutable('composer');

            $composerOptions = array_merge(['-n'], $options);

            $process = new Process($composerPath . ' update ' . implode(' ', $composerOptions) . ' -d ' . PIMCORE_COMPOSER_FILE_PATH);
            $process->setTimeout(900);
            $process->mustRun();
        } catch (\Exception $e) {
            Logger::error($e);
            $outputMessage = "<b style='color:red;'>Important</b>: Failed running <pre>composer update</pre> Please run it manually on commandline!";
        }

        return [
            'message' => $outputMessage,
            'success' => true
        ];
    }

    /**
     * @return array
     */
    public static function composerDumpAutoload()
    {
        $outputMessage = '';

        try {
            $composerPath = \Pimcore\Tool\Console::getExecutable('composer');
            $process = new Process($composerPath . ' dumpautoload -d ' . PIMCORE_COMPOSER_FILE_PATH);
            $process->setTimeout(300);
            $process->mustRun();
        } catch (\Exception $e) {
            Logger::error($e);
            $outputMessage = "<b style='color:red;'>Important</b>: Failed running <pre>composer dumpautoload</pre> Please run it manually on commandline!";
        }

        return [
            'message' => $outputMessage,
            'success' => true
        ];
    }

    /**
     * @return array
     */
    public static function invalidateComposerAutoloadClassmap()
    {

        // unfortunately \Composer\Autoload\ClassLoader has no method setClassMap()
        // so we need to invalidate the existing classmap by replacing all mappings beginning with 'Pimcore'

        $prefix = PIMCORE_COMPOSER_PATH . '/composer/';
        $files = [
            $prefix . 'autoload_classmap.php',
            $prefix . 'autoload_static.php',
        ];

        foreach ($files as $file) {
            $newContent = file_get_contents($file);
            $newContent = preg_replace("@'Pimcore([^']+)?(?<!\\\\)'@", "'xxxDisabledByUpdater$1'", $newContent);
            file_put_contents($file, $newContent);
        }

        return [
            'success' => true
        ];
    }

    /**
     * @return bool
     */
    public static function isComposerAvailable()
    {
        return (bool) \Pimcore\Tool\Console::getExecutable('composer');
    }

    public static function updateMaxmindDb()
    {
        $downloadUrl = 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz';
        $geoDbFile = PIMCORE_CONFIGURATION_DIRECTORY . '/GeoLite2-City.mmdb';
        $geoDbFileGz = $geoDbFile . '.gz';

        $firstTuesdayOfMonth = strtotime(date('F') . ' 2013 tuesday');
        $filemtime = 0;
        if (file_exists($geoDbFile)) {
            $filemtime = filemtime($geoDbFile);
        }

        // update if file is older than 30 days, or if it is the first tuesday of the month
        if ($filemtime < (time() - 30 * 86400) || (date('m/d/Y') == date('m/d/Y', $firstTuesdayOfMonth) && $filemtime < time() - 86400)) {
            $data = Tool::getHttpData($downloadUrl);
            if (strlen($data) > 1000000) {
                File::put($geoDbFileGz, $data);

                @unlink($geoDbFile);

                $sfp = gzopen($geoDbFileGz, 'rb');
                $fp = fopen($geoDbFile, 'w');

                while ($string = gzread($sfp, 4096)) {
                    fwrite($fp, $string, strlen($string));
                }
                gzclose($sfp);
                fclose($fp);

                unlink($geoDbFileGz);

                Logger::info('Updated MaxMind GeoIP2 Database in: ' . $geoDbFile);
            } else {
                Logger::err('Failed to update MaxMind GeoIP2, size is under about 1M');
            }
        } else {
            Logger::debug('MayMind GeoIP2 Download skipped, everything up to date, last update: ' . date('m/d/Y H:i', $filemtime));
        }
    }
}
