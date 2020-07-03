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

namespace Pimcore\Tool;

use Pimcore\Db\ConnectionInterface;
use Pimcore\File;
use Pimcore\Image;
use Pimcore\Tool\Requirements\Check;

class Requirements
{
    /**
     * @return Check[]
     */
    public static function checkFilesystem()
    {
        $checks = [];

        // filesystem checks
        foreach ([PIMCORE_PUBLIC_VAR, PIMCORE_PRIVATE_VAR] as $varDir) {
            $varWritable = true;

            try {
                if (!is_dir($varDir)) {
                    File::mkdir($varDir);
                }

                $files = self::rscandir($varDir);

                foreach ($files as $file) {
                    if (!is_writable($file)) {
                        $varWritable = false;
                    }
                }

                $checks[] = new Check([
                    'name' => str_replace(PIMCORE_PROJECT_ROOT, '', $varDir) . ' writeable',
                    'state' => $varWritable ? Check::STATE_OK : Check::STATE_ERROR,
                    'message' => str_replace(PIMCORE_PROJECT_ROOT, '', $varDir) . ' needs to be writable by PHP',
                ]);
            } catch (\Exception $e) {
                $checks[] = new Check([
                    'name' => str_replace(PIMCORE_PROJECT_ROOT, '', $varDir) . ' (not checked - too many files)',
                    'state' => Check::STATE_WARNING,
                ]);
            }
        }

        return $checks;
    }

    /**
     * @param ConnectionInterface $db
     *
     * @return Check[]
     */
    public static function checkMysql(ConnectionInterface $db)
    {
        $checks = [];

        // storage engines
        $engines = $db->fetchCol('SHOW ENGINES;');

        // innodb
        $checks[] = new Check([
            'name' => 'InnoDB Support',
            'state' => ($engines && in_arrayi('innodb', $engines)) ? Check::STATE_OK : Check::STATE_ERROR,
        ]);

        // ARCHIVE & MyISAM
        $checks[] = new Check([
            'name' => 'ARCHIVE or MyISAM Support',
            'state' => ($engines && (in_arrayi('archive', $engines) || in_arrayi('myisam', $engines))) ? Check::STATE_OK : Check::STATE_WARNING,
        ]);

        // check database charset =>  utf-8 encoding
        $result = $db->fetchRow('SHOW VARIABLES LIKE "character\_set\_database"');
        $checks[] = new Check([
            'name' => 'Database Charset utf8mb4',
            'state' => ($result && (strtolower($result['Value']) == 'utf8mb4')) ? Check::STATE_OK : Check::STATE_ERROR,
        ]);

        // empty values are provided by MariaDB => 10.3
        $largePrefix = $db->fetchRow("SHOW GLOBAL VARIABLES LIKE 'innodb\_large\_prefix';");
        $checks[] = new Check([
            'name' => 'innodb_large_prefix = ON ',
            'state' => ($largePrefix && !in_arrayi(strtolower((string) $largePrefix['Value']), ['on', '1', ''])) ? Check::STATE_ERROR : Check::STATE_OK,
        ]);

        $fileFormat = $db->fetchRow("SHOW GLOBAL VARIABLES LIKE 'innodb\_file\_format';");
        $checks[] = new Check([
            'name' => 'innodb_file_format = Barracuda',
            'state' => ($fileFormat && (!empty($fileFormat['Value']) && strtolower($fileFormat['Value']) != 'barracuda')) ? Check::STATE_ERROR : Check::STATE_OK,
        ]);

        $fileFilePerTable = $db->fetchRow("SHOW GLOBAL VARIABLES LIKE 'innodb\_file\_per\_table';");
        $checks[] = new Check([
            'name' => 'innodb_file_per_table = ON',
            'state' => ($fileFilePerTable && !in_arrayi(strtolower((string) $fileFilePerTable['Value']), ['on', '1'])) ? Check::STATE_ERROR : Check::STATE_OK,
        ]);

        // create table
        $queryCheck = true;
        try {
            $db->query('CREATE TABLE __pimcore_req_check (
                  id int(11) NOT NULL AUTO_INCREMENT,
                  field varchar(190) DEFAULT NULL,
                  PRIMARY KEY (id)
                ) DEFAULT CHARSET=utf8mb4;');
        } catch (\Exception $e) {
            $queryCheck = false;
        }

        $checks[] = new Check([
            'name' => 'CREATE TABLE',
            'state' => $queryCheck ? Check::STATE_OK : Check::STATE_ERROR,
        ]);

        // alter table
        $queryCheck = true;
        try {
            $db->query('ALTER TABLE __pimcore_req_check ADD COLUMN alter_field varchar(190) NULL DEFAULT NULL');
        } catch (\Exception $e) {
            $queryCheck = false;
        }

        $checks[] = new Check([
            'name' => 'ALTER TABLE',
            'state' => $queryCheck ? Check::STATE_OK : Check::STATE_ERROR,
        ]);

        // Manage indexes
        $queryCheck = true;
        try {
            $db->query('ALTER TABLE __pimcore_req_check
                  CHANGE COLUMN id id int(11) NOT NULL,
                  CHANGE COLUMN field field varchar(190) NULL DEFAULT NULL,
                  CHANGE COLUMN alter_field alter_field varchar(190) NULL DEFAULT NULL,
                  ADD KEY field (field),
                  DROP PRIMARY KEY ,
                 DEFAULT CHARSET=utf8mb4');

            $db->query('ALTER TABLE __pimcore_req_check
                  CHANGE COLUMN id id int(11) NOT NULL AUTO_INCREMENT,
                  CHANGE COLUMN field field varchar(190) NULL DEFAULT NULL,
                  CHANGE COLUMN alter_field alter_field varchar(190) NULL DEFAULT NULL,
                  ADD PRIMARY KEY (id) ,
                 DEFAULT CHARSET=utf8mb4');
        } catch (\Exception $e) {
            $queryCheck = false;
        }

        $checks[] = new Check([
            'name' => 'Manage Indexes',
            'state' => $queryCheck ? Check::STATE_OK : Check::STATE_ERROR,
        ]);

        // Fulltext indexes
        $queryCheck = true;
        try {
            $db->query('ALTER TABLE __pimcore_req_check ADD FULLTEXT INDEX `fulltextFieldIndex` (`field`)');
        } catch (\Exception $e) {
            $queryCheck = false;
        }

        $checks[] = new Check([
            'name' => 'Fulltext Indexes',
            'state' => $queryCheck ? Check::STATE_OK : Check::STATE_ERROR,
        ]);

        // insert data
        $queryCheck = true;
        try {
            $db->insert('__pimcore_req_check', [
                'field' => uniqid(),
                'alter_field' => uniqid(),
            ]);
        } catch (\Exception $e) {
            $queryCheck = false;
        }

        $checks[] = new Check([
            'name' => 'INSERT',
            'state' => $queryCheck ? Check::STATE_OK : Check::STATE_ERROR,
        ]);

        // update
        $queryCheck = true;
        try {
            $db->updateWhere('__pimcore_req_check', [
                'field' => uniqid(),
                'alter_field' => uniqid(),
            ]);
        } catch (\Exception $e) {
            $queryCheck = false;
        }

        $checks[] = new Check([
            'name' => 'UPDATE',
            'state' => $queryCheck ? Check::STATE_OK : Check::STATE_ERROR,
        ]);

        // select
        $queryCheck = true;
        try {
            $db->fetchAll('SELECT * FROM __pimcore_req_check');
        } catch (\Exception $e) {
            $queryCheck = false;
        }

        $checks[] = new Check([
            'name' => 'SELECT',
            'state' => $queryCheck ? Check::STATE_OK : Check::STATE_ERROR,
        ]);

        // create view
        $queryCheck = true;
        try {
            $db->query('CREATE OR REPLACE VIEW __pimcore_req_check_view AS SELECT * FROM __pimcore_req_check');
        } catch (\Exception $e) {
            $queryCheck = false;
        }

        $checks[] = new Check([
            'name' => 'CREATE VIEW',
            'state' => $queryCheck ? Check::STATE_OK : Check::STATE_ERROR,
        ]);

        // select from view
        $queryCheck = true;
        try {
            $db->fetchAll('SELECT * FROM __pimcore_req_check_view');
        } catch (\Exception $e) {
            $queryCheck = false;
        }

        $checks[] = new Check([
            'name' => 'SELECT (from view)',
            'state' => $queryCheck ? Check::STATE_OK : Check::STATE_ERROR,
        ]);

        // delete
        $queryCheck = true;
        try {
            $db->deleteWhere('__pimcore_req_check');
        } catch (\Exception $e) {
            $queryCheck = false;
        }

        $checks[] = new Check([
            'name' => 'DELETE',
            'state' => $queryCheck ? Check::STATE_OK : Check::STATE_ERROR,
        ]);

        // show create view
        $queryCheck = true;
        try {
            $db->query('SHOW CREATE VIEW __pimcore_req_check_view');
        } catch (\Exception $e) {
            $queryCheck = false;
        }

        $checks[] = new Check([
            'name' => 'SHOW CREATE VIEW',
            'state' => $queryCheck ? Check::STATE_OK : Check::STATE_ERROR,
        ]);

        // show create table
        $queryCheck = true;
        try {
            $db->query('SHOW CREATE TABLE __pimcore_req_check');
        } catch (\Exception $e) {
            $queryCheck = false;
        }

        $checks[] = new Check([
            'name' => 'SHOW CREATE TABLE',
            'state' => $queryCheck ? Check::STATE_OK : Check::STATE_ERROR,
        ]);

        // drop view
        $queryCheck = true;
        try {
            $db->query('DROP VIEW __pimcore_req_check_view');
        } catch (\Exception $e) {
            $queryCheck = false;
        }

        $checks[] = new Check([
            'name' => 'DROP VIEW',
            'state' => $queryCheck ? Check::STATE_OK : Check::STATE_ERROR,
        ]);

        // drop table
        $queryCheck = true;
        try {
            $db->query('DROP TABLE __pimcore_req_check');
        } catch (\Exception $e) {
            $queryCheck = false;
        }

        $checks[] = new Check([
            'name' => 'DROP TABLE',
            'state' => $queryCheck ? Check::STATE_OK : Check::STATE_ERROR,
        ]);

        return $checks;
    }

    /**
     * @return Check[]
     */
    public static function checkExternalApplications()
    {
        $checks = [];

        // PHP CLI BIN
        try {
            $phpCliBin = (bool) \Pimcore\Tool\Console::getPhpCli();
        } catch (\Exception $e) {
            $phpCliBin = false;
        }

        $checks[] = new Check([
            'name' => 'PHP',
            'state' => $phpCliBin ? Check::STATE_OK : Check::STATE_ERROR,
        ]);

        // Composer
        $checks[] = new Check([
            'name' => 'Composer',
            'state' => (bool) \Pimcore\Tool\Console::getExecutable('composer') ? Check::STATE_OK : Check::STATE_ERROR,
        ]);

        // FFMPEG BIN
        try {
            $ffmpegBin = (bool) \Pimcore\Video\Adapter\Ffmpeg::getFfmpegCli();
        } catch (\Exception $e) {
            $ffmpegBin = false;
        }

        $checks[] = new Check([
            'name' => 'FFMPEG',
            'state' => $ffmpegBin ? Check::STATE_OK : Check::STATE_WARNING,
        ]);

        // WKHTMLTOIMAGE BIN
        try {
            $wkhtmltopdfBin = (bool) \Pimcore\Image\HtmlToImage::getWkhtmltoimageBinary();
        } catch (\Exception $e) {
            $wkhtmltopdfBin = false;
        }

        $checks[] = new Check([
            'name' => 'wkhtmltoimage',
            'state' => $wkhtmltopdfBin ? Check::STATE_OK : Check::STATE_WARNING,
        ]);

        // ghostscript BIN
        try {
            $ghostscriptBin = (bool) \Pimcore\Document\Adapter\Ghostscript::getGhostscriptCli();
        } catch (\Exception $e) {
            $ghostscriptBin = false;
        }

        $checks[] = new Check([
            'name' => 'Ghostscript',
            'state' => $ghostscriptBin ? Check::STATE_OK : Check::STATE_WARNING,
        ]);

        // LibreOffice BIN
        try {
            $libreofficeBin = (bool) \Pimcore\Document\Adapter\LibreOffice::getLibreOfficeCli();
        } catch (\Exception $e) {
            $libreofficeBin = false;
        }

        $checks[] = new Check([
            'name' => 'LibreOffice',
            'state' => $libreofficeBin ? Check::STATE_OK : Check::STATE_WARNING,
        ]);

        // image optimizer
        foreach (['zopflipng', 'pngcrush', 'jpegoptim', 'pngout', 'advpng', 'cjpeg', 'exiftool'] as $optimizerName) {
            try {
                $optimizerAvailable = \Pimcore\Tool\Console::getExecutable($optimizerName);
            } catch (\Exception $e) {
                $optimizerAvailable = false;
            }

            $checks[] = new Check([
                'name' => $optimizerName,
                'state' => $optimizerAvailable ? Check::STATE_OK : Check::STATE_WARNING,
            ]);
        }

        // timeout binary
        try {
            $timeoutBin = (bool) \Pimcore\Tool\Console::getTimeoutBinary();
        } catch (\Exception $e) {
            $timeoutBin = false;
        }

        $checks[] = new Check([
            'name' => 'timeout - (GNU coreutils)',
            'state' => $timeoutBin ? Check::STATE_OK : Check::STATE_WARNING,
        ]);

        // pdftotext binary
        try {
            $pdftotextBin = (bool) \Pimcore\Document\Adapter\Ghostscript::getPdftotextCli();
        } catch (\Exception $e) {
            $pdftotextBin = false;
        }

        $checks[] = new Check([
            'name' => 'pdftotext - (part of poppler-utils)',
            'state' => $pdftotextBin ? Check::STATE_OK : Check::STATE_WARNING,
        ]);

        try {
            $sqipAvailable = \Pimcore\Tool\Console::getExecutable('sqip');
        } catch (\Exception $e) {
            $sqipAvailable = false;
        }

        $checks[] = new Check([
            'name' => 'SQIP - SVG Placeholder',
            'state' => $sqipAvailable ? Check::STATE_OK : Check::STATE_WARNING,
        ]);

        try {
            $facedetectAvailable = \Pimcore\Tool\Console::getExecutable('facedetect');
        } catch (\Exception $e) {
            $facedetectAvailable = false;
        }

        $checks[] = new Check([
            'name' => 'facedetect',
            'state' => $facedetectAvailable ? Check::STATE_OK : Check::STATE_WARNING,
        ]);

        try {
            $graphvizAvailable = \Pimcore\Tool\Console::getExecutable('dot');
        } catch (\Exception $e) {
            $graphvizAvailable = false;
        }

        $checks[] = new Check([
            'name' => 'Graphviz',
            'state' => $graphvizAvailable ? Check::STATE_OK : Check::STATE_WARNING,
        ]);

        return $checks;
    }

    /**
     * @return Check[]
     */
    public static function checkPhp()
    {
        $checks = [];

        // check for memory limit
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitState = Check::STATE_OK;
        $memoryLimitMessage = '';

        // check bytes of memory limit if it's not set to unlimited ('-1')
        // http://php.net/manual/en/ini.core.php#ini.memory-limit
        if ($memoryLimit !== '-1') {
            $memoryLimit = filesize2bytes($memoryLimit . 'B');
            if ($memoryLimit < 67108000) {
                $memoryLimitState = Check::STATE_ERROR;
                $memoryLimitMessage = 'Your memory limit is by far too low. Set `memory_limit` in your php.ini at least to `150M`.';
            } elseif ($memoryLimit < 134217000) {
                $memoryLimitState = Check::STATE_WARNING;
                $memoryLimitMessage = 'Your memory limit is probably too low. Set `memory_limit` in your php.ini to `150M` or higher to avoid issues.';
            }
        }

        $checks[] = new Check([
            'name' => 'memory_limit (in php.ini)',
            'link' => 'http://www.php.net/memory_limit',
            'state' => $memoryLimitState,
            'message' => $memoryLimitMessage,
        ]);

        // pdo_mysql
        $checks[] = new Check([
            'name' => 'PDO MySQL',
            'link' => 'http://www.php.net/pdo_mysql',
            'state' => @constant('PDO::MYSQL_ATTR_FOUND_ROWS') ? Check::STATE_OK : Check::STATE_ERROR,
        ]);

        // Mysqli
        $checks[] = new Check([
            'name' => 'Mysqli',
            'link' => 'http://www.php.net/mysqli',
            'state' => class_exists('mysqli') ? Check::STATE_OK : Check::STATE_WARNING,
            'message' => "Mysqli can be used instead of PDO MySQL, though it isn't a requirement.",
        ]);

        // iconv
        $checks[] = new Check([
            'name' => 'iconv',
            'link' => 'http://www.php.net/iconv',
            'state' => function_exists('iconv') ? Check::STATE_OK : Check::STATE_ERROR,
        ]);

        // dom
        $checks[] = new Check([
            'name' => 'Document Object Model (DOM)',
            'link' => 'http://www.php.net/dom',
            'state' => class_exists('DOMDocument') ? Check::STATE_OK : Check::STATE_ERROR,
        ]);

        // simplexml
        $checks[] = new Check([
            'name' => 'SimpleXML',
            'link' => 'http://www.php.net/simplexml',
            'state' => class_exists('SimpleXMLElement') ? Check::STATE_OK : Check::STATE_ERROR,
        ]);

        // gd
        $checks[] = new Check([
            'name' => 'GD',
            'link' => 'http://www.php.net/gd',
            'state' => function_exists('gd_info') ? Check::STATE_OK : Check::STATE_ERROR,
        ]);

        // exif
        $checks[] = new Check([
            'name' => 'EXIF',
            'link' => 'http://www.php.net/exif',
            'state' => function_exists('exif_read_data') ? Check::STATE_OK : Check::STATE_ERROR,
        ]);

        // multibyte support
        $checks[] = new Check([
            'name' => 'Multibyte String (mbstring)',
            'link' => 'http://www.php.net/mbstring',
            'state' => function_exists('mb_strcut') ? Check::STATE_OK : Check::STATE_ERROR,
        ]);

        // file_info support
        $checks[] = new Check([
            'name' => 'File Information (file_info)',
            'link' => 'http://www.php.net/file_info',
            'state' => function_exists('finfo_open') ? Check::STATE_OK : Check::STATE_ERROR,
        ]);

        // zip
        $checks[] = new Check([
            'name' => 'zip',
            'link' => 'http://www.php.net/zip',
            'state' => class_exists('ZipArchive') ? Check::STATE_OK : Check::STATE_ERROR,
        ]);

        // gzip
        $checks[] = new Check([
            'name' => 'zlib / gzip',
            'link' => 'http://www.php.net/zlib',
            'state' => function_exists('gzcompress') ? Check::STATE_OK : Check::STATE_ERROR,
        ]);

        // Intl
        $checks[] = new Check([
            'name' => 'Intl',
            'link' => 'http://www.php.net/intl',
            'state' => extension_loaded('intl') ? Check::STATE_OK : Check::STATE_ERROR,
        ]);

        // Locales
        if (extension_loaded('intl')) {
            $fmt = new \IntlDateFormatter('de', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL, 'Europe/Vienna', \IntlDateFormatter::GREGORIAN, 'EEEE');
            $checks[] = new Check([
                'name' => 'locales-all',
                'link' => 'https://packages.debian.org/en/stable/locales-all',
                'state' => ($fmt->format(new \DateTime('next tuesday')) == 'Dienstag') ? Check::STATE_OK : Check::STATE_WARNING,
                'message' => "It's recommended to have the GNU C Library locale data installed (eg. apt-get install locales-all).",
            ]);
        }

        // Imagick
        $checks[] = new Check([
            'name' => 'Imagick',
            'link' => 'http://www.php.net/imagick',
            'state' => class_exists('Imagick') ? Check::STATE_OK : Check::STATE_WARNING,
        ]);

        // APCu
        $checks[] = new Check([
            'name' => 'APCu',
            'link' => 'http://www.php.net/apcu',
            'state' => (function_exists('apcu_fetch') && ini_get('apc.enabled')) ? Check::STATE_OK : Check::STATE_WARNING,
            'message' => "It's highly recommended to have the APCu extension installed and enabled.",
        ]);

        // OPcache
        $checks[] = new Check([
            'name' => 'OPcache',
            'link' => 'http://www.php.net/opcache',
            'state' => function_exists('opcache_reset') ? Check::STATE_OK : Check::STATE_WARNING,
            'message' => "It's highly recommended to have the OPCache extension installed and enabled.",
        ]);

        // Redis
        $checks[] = new Check([
            'name' => 'Redis',
            'link' => 'https://pecl.php.net/package/redis',
            'state' => class_exists('Redis') ? Check::STATE_OK : Check::STATE_WARNING,
        ]);

        // curl for google api sdk
        $checks[] = new Check([
            'name' => 'curl',
            'link' => 'http://www.php.net/curl',
            'state' => function_exists('curl_init') ? Check::STATE_OK : Check::STATE_ERROR,
        ]);

        // WebP for active image adapter
        $imageAdapter = Image::getInstance();
        $reflect = new \ReflectionClass($imageAdapter);
        $imageAdapterType = $reflect->getShortName();
        $checks[] = new Check([
            'name' => 'WebP (via ' . $imageAdapterType . ')',
            // we use the force flag here, because during the installer the cache is not available
            'state' => $imageAdapter->supportsFormat('webp', true) ? Check::STATE_OK : Check::STATE_WARNING,
        ]);

        return $checks;
    }

    /**
     * @param string $base
     * @param array $data
     *
     * @return array
     *
     * @throws \Exception
     */
    protected static function rscandir($base = '', &$data = [])
    {
        if (substr($base, -1, 1) != DIRECTORY_SEPARATOR) { //add trailing slash if it doesn't exists
            $base .= DIRECTORY_SEPARATOR;
        }

        if (count($data) > 2000) {
            throw new \Exception('limit of 2000 files reached');
        }

        $array = array_diff(scandir($base), ['.', '..', '.svn']);
        foreach ($array as $value) {
            if (is_dir($base . $value)) {
                $data[] = $base . $value . DIRECTORY_SEPARATOR;
                $data = self::rscandir($base . $value . DIRECTORY_SEPARATOR, $data);
            } elseif (is_file($base . $value)) {
                $data[] = $base . $value;
            }
        }

        return $data;
    }

    /**
     * @param ConnectionInterface $db
     *
     * @return array
     */
    public static function checkAll(ConnectionInterface $db): array
    {
        return [
            'checksPHP' => static::checkPhp(),
            'checksFS' => static::checkFilesystem(),
            'checksApps' => static::checkExternalApplications(),
            'checksMySQL' => static::checkMysql($db),
        ];
    }
}
