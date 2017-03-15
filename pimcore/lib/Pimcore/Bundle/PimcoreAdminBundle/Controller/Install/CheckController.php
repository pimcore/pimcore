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

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller\Install;

use Pimcore\Bundle\PimcoreBundle\Controller\EventedControllerInterface;
use Pimcore\Model\User;
use Pimcore\Update;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

class CheckController extends Controller implements EventedControllerInterface
{

    /**
     * @Route("/check")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $checksPHP = [];
        $checksMySQL = [];
        $checksFS = [];
        $checksApps = [];

        // check for memory limit
        $memoryLimit = ini_get("memory_limit");
        $memoryLimit = filesize2bytes($memoryLimit . "B");
        $state = "ok";

        if ($memoryLimit < 67108000) {
            $state = "error";
        } elseif ($memoryLimit < 134217000) {
            $state = "warning";
        }

        $checksPHP[] = [
            "name" => "memory_limit (in php.ini)",
            "link" => "http://www.php.net/memory_limit",
            "state" => $state
        ];

        // pdo_mysql
        $checksPHP[] = [
            "name" => "PDO_Mysql",
            "link" => "http://www.php.net/pdo_mysql",
            "state" => @constant("PDO::MYSQL_ATTR_FOUND_ROWS") ? "ok" : "error"
        ];

        // Mysqli
        $checksPHP[] = [
            "name" => "Mysqli",
            "link" => "http://www.php.net/mysqli",
            "state" => class_exists("mysqli") ? "ok" : "error"
        ];

        // iconv
        $checksPHP[] = [
            "name" => "iconv",
            "link" => "http://www.php.net/iconv",
            "state" => function_exists("iconv") ? "ok" : "error"
        ];

        // dom
        $checksPHP[] = [
            "name" => "dom",
            "link" => "http://www.php.net/dom",
            "state" => class_exists("DOMDocument") ? "ok" : "error"
        ];

        // simplexml
        $checksPHP[] = [
            "name" => "SimpleXML",
            "link" => "http://www.php.net/simplexml",
            "state" => class_exists("SimpleXMLElement") ? "ok" : "error"
        ];

        // gd
        $checksPHP[] = [
            "name" => "GD",
            "link" => "http://www.php.net/gd",
            "state" => function_exists("gd_info") ? "ok" : "error"
        ];

        // exif
        $checksPHP[] = [
            "name" => "EXIF",
            "link" => "http://www.php.net/exif",
            "state" => function_exists("exif_read_data") ? "ok" : "error"
        ];

        // multibyte support
        $checksPHP[] = [
            "name" => "Multibyte String (mbstring)",
            "link" => "http://www.php.net/mbstring",
            "state" => function_exists("mb_get_info") ? "ok" : "error"
        ];

        // file_info support
        $checksPHP[] = [
            "name" => "File Information (file_info)",
            "link" => "http://www.php.net/file_info",
            "state" => function_exists("finfo_open") ? "ok" : "error"
        ];

        // zip
        $checksPHP[] = [
            "name" => "zip",
            "link" => "http://www.php.net/zip",
            "state" => class_exists("ZipArchive") ? "ok" : "error"
        ];

        // gzip
        $checksPHP[] = [
            "name" => "zlib / gzip",
            "link" => "http://www.php.net/zlib",
            "state" => function_exists("gzcompress") ? "ok" : "error"
        ];

        // bzip
        $checksPHP[] = [
            "name" => "Bzip2",
            "link" => "http://www.php.net/bzip2",
            "state" => function_exists("bzcompress") ? "ok" : "error"
        ];

        // openssl
        $checksPHP[] = [
            "name" => "OpenSSL",
            "link" => "http://www.php.net/openssl",
            "state" => function_exists("openssl_open") ? "ok" : "error"
        ];

        // Intl
        $checksPHP[] = [
            "name" => "Intl",
            "link" => "http://www.php.net/intl",
            "state" => class_exists("Locale") ? "ok" : "error"
        ];

        // Locales
        $fmt = new \IntlDateFormatter( "de" , \IntlDateFormatter::FULL, \IntlDateFormatter::FULL, "Europe/Vienna", \IntlDateFormatter::GREGORIAN, "EEEE");
        $checksPHP[] = [
            "name" => "locales-all",
            "link" => "https://packages.debian.org/en/stable/locales-all",
            "state" => ($fmt->format(new \DateTime("next tuesday")) == "Dienstag") ? "ok" : "warning"
        ];

        // Imagick
        $checksPHP[] = [
            "name" => "Imagick",
            "link" => "http://www.php.net/imagick",
            "state" => class_exists("Imagick") ? "ok" : "warning"
        ];

        // OPcache
        $checksPHP[] = [
            "name" => "OPcache",
            "link" => "http://www.php.net/opcache",
            "state" => function_exists("opcache_reset") ? "ok" : "warning"
        ];

        // Redis
        $checksPHP[] = [
            "name" => "Redis",
            "link" => "https://pecl.php.net/package/redis",
            "state" => class_exists("Redis") ? "ok" : "warning"
        ];

        // curl for google api sdk
        $checksPHP[] = [
            "name" => "curl",
            "link" => "http://www.php.net/curl",
            "state" => function_exists("curl_init") ? "ok" : "warning"
        ];


        $db = null;

        if ($request->get("mysql_username")) {
            // this is before installing
            try {
                $dbConfig = [
                    'user' => $request->get("mysql_username"),
                    'password' => $request->get("mysql_password"),
                    'dbname' => $request->get("mysql_database"),
                    'driver' => "pdo_mysql",
                    'wrapperClass' => 'Pimcore\Db\Connection',
                ];

                $hostSocketValue = $request->get("mysql_host_socket");
                if (file_exists($hostSocketValue)) {
                    $dbConfig["unix_socket"] = $hostSocketValue;
                } else {
                    $dbConfig["host"] = $hostSocketValue;
                    $dbConfig["port"] = $request->get("mysql_port");
                }

                $config = new \Doctrine\DBAL\Configuration();
                $db = \Doctrine\DBAL\DriverManager::getConnection($dbConfig, $config);

            } catch (\Exception $e) {
                $db = null;
            }
        } else {
            // this is after installing, eg. after a migration, ...
            $db = $this->get("database_connection");
        }

        if ($db) {

            // storage engines
            $engines = [];
            $enginesRaw = $db->fetchAll("SHOW ENGINES;");
            foreach ($enginesRaw as $engineRaw) {
                $engines[] = strtolower($engineRaw["Engine"]);
            }

            // innodb
            $checksMySQL[] = [
                "name" => "InnoDB Support",
                "state" => in_array("innodb", $engines) ? "ok" : "error"
            ];

            // myisam
            $checksMySQL[] = [
                "name" => "MyISAM Support",
                "state" => in_array("myisam", $engines) ? "ok" : "error"
            ];

            // memory
            $checksMySQL[] = [
                "name" => "MEMORY Support",
                "state" => in_array("memory", $engines) ? "ok" : "error"
            ];

            // check database charset =>  utf-8 encoding
            $result = $db->fetchRow('SHOW VARIABLES LIKE "character\_set\_database"');
            $checksMySQL[] = [
                "name" => "Database Charset UTF8",
                "state" => (in_array($result['Value'], ["utf8", "utf8mb4"])) ? "ok" : "error"
            ];

            // create table
            $queryCheck = true;
            try {
                $db->query("CREATE TABLE __pimcore_req_check (
                  id int(11) NOT NULL AUTO_INCREMENT,
                  field varchar(190) DEFAULT NULL,
                  PRIMARY KEY (id)
                ) DEFAULT CHARSET=utf8mb4;");
            } catch (\Exception $e) {
                $queryCheck = false;
            }

            $checksMySQL[] = [
                "name" => "CREATE TABLE",
                "state" => $queryCheck ? "ok" : "error"
            ];

            // alter table
            $queryCheck = true;
            try {
                $db->query("ALTER TABLE __pimcore_req_check ADD COLUMN alter_field varchar(190) NULL DEFAULT NULL");
            } catch (\Exception $e) {
                $queryCheck = false;
            }

            $checksMySQL[] = [
                "name" => "ALTER TABLE",
                "state" => $queryCheck ? "ok" : "error"
            ];

            // Manage indexes
            $queryCheck = true;
            try {
                $db->query("ALTER TABLE __pimcore_req_check
                  CHANGE COLUMN id id int(11) NOT NULL,
                  CHANGE COLUMN field field varchar(190) NULL DEFAULT NULL,
                  CHANGE COLUMN alter_field alter_field varchar(190) NULL DEFAULT NULL,
                  ADD KEY field (field),
                  DROP PRIMARY KEY ,
                 DEFAULT CHARSET=utf8mb4");

                $db->query("ALTER TABLE __pimcore_req_check
                  CHANGE COLUMN id id int(11) NOT NULL AUTO_INCREMENT,
                  CHANGE COLUMN field field varchar(190) NULL DEFAULT NULL,
                  CHANGE COLUMN alter_field alter_field varchar(190) NULL DEFAULT NULL,
                  ADD PRIMARY KEY (id) ,
                 DEFAULT CHARSET=utf8mb4");
            } catch (\Exception $e) {
                $queryCheck = false;
            }

            $checksMySQL[] = [
                "name" => "Manage Indexes",
                "state" => $queryCheck ? "ok" : "error"
            ];

            // insert data
            $queryCheck = true;
            try {
                $db->insert("__pimcore_req_check", [
                    "field" => uniqid(),
                    "alter_field" => uniqid()
                ]);
            } catch (\Exception $e) {
                $queryCheck = false;
            }

            $checksMySQL[] = [
                "name" => "INSERT",
                "state" => $queryCheck ? "ok" : "error"
            ];

            // update
            $queryCheck = true;
            try {
                $db->updateWhere("__pimcore_req_check", [
                    "field" => uniqid(),
                    "alter_field" => uniqid()
                ]);
            } catch (\Exception $e) {
                $queryCheck = false;
            }

            $checksMySQL[] = [
                "name" => "UPDATE",
                "state" => $queryCheck ? "ok" : "error"
            ];

            // select
            $queryCheck = true;
            try {
                $db->fetchAll("SELECT * FROM __pimcore_req_check");
            } catch (\Exception $e) {
                $queryCheck = false;
            }

            $checksMySQL[] = [
                "name" => "SELECT",
                "state" => $queryCheck ? "ok" : "error"
            ];


            // create view
            $queryCheck = true;
            try {
                $db->query("CREATE OR REPLACE VIEW __pimcore_req_check_view AS SELECT * FROM __pimcore_req_check");
            } catch (\Exception $e) {
                $queryCheck = false;
            }

            $checksMySQL[] = [
                "name" => "CREATE VIEW",
                "state" => $queryCheck ? "ok" : "error"
            ];

            // select from view
            $queryCheck = true;
            try {
                $db->fetchAll("SELECT * FROM __pimcore_req_check_view");
            } catch (\Exception $e) {
                $queryCheck = false;
            }

            $checksMySQL[] = [
                "name" => "SELECT (from view)",
                "state" => $queryCheck ? "ok" : "error"
            ];


            // delete
            $queryCheck = true;
            try {
                $db->deleteWhere("__pimcore_req_check");
            } catch (\Exception $e) {
                $queryCheck = false;
            }

            $checksMySQL[] = [
                "name" => "DELETE",
                "state" => $queryCheck ? "ok" : "error"
            ];

            // show create view
            $queryCheck = true;
            try {
                $db->query("SHOW CREATE VIEW __pimcore_req_check_view");
            } catch (\Exception $e) {
                $queryCheck = false;
            }

            $checksMySQL[] = [
                "name" => "SHOW CREATE VIEW",
                "state" => $queryCheck ? "ok" : "error"
            ];

            // show create table
            $queryCheck = true;
            try {
                $db->query("SHOW CREATE TABLE __pimcore_req_check");
            } catch (\Exception $e) {
                $queryCheck = false;
            }

            $checksMySQL[] = [
                "name" => "SHOW CREATE TABLE",
                "state" => $queryCheck ? "ok" : "error"
            ];

            // drop view
            $queryCheck = true;
            try {
                $db->query("DROP VIEW __pimcore_req_check_view");
            } catch (\Exception $e) {
                $queryCheck = false;
            }

            $checksMySQL[] = [
                "name" => "DROP VIEW",
                "state" => $queryCheck ? "ok" : "error"
            ];

            // drop table
            $queryCheck = true;
            try {
                $db->query("DROP TABLE __pimcore_req_check");
            } catch (\Exception $e) {
                $queryCheck = false;
            }

            $checksMySQL[] = [
                "name" => "DROP TABLE",
                "state" => $queryCheck ? "ok" : "error"
            ];
        } else {
            return new Response("Not possible... no or wrong database settings given.<br />Please fill out the MySQL Settings in the install form an click again on `Check Requirements´");
        }


        // filesystem checks
        foreach ([PIMCORE_PUBLIC_VAR, PIMCORE_PRIVATE_VAR] as $varDir) {
            $varWritable = true;

            try {
                $files = $this->rscandir($varDir);

                foreach ($files as $file) {
                    if (!is_writable($file)) {
                        $varWritable = false;
                    }
                }

                $checksFS[] = [
                    "name" => str_replace(PIMCORE_PROJECT_ROOT, "", $varDir) . " writeable",
                    "state" => $varWritable ? "ok" : "error"
                ];
            } catch (\Exception $e) {
                $checksFS[] = [
                    "name" => str_replace(PIMCORE_PROJECT_ROOT, "", $varDir) . " (not checked - too many files)",
                    "state" => "warning"
                ];
            }
        }

        // pimcore writeable
        $checksFS[] = [
            "name" => "/pimcore/ writeable",
            "state" => \Pimcore\Update::isWriteable() ? "ok" : "warning"
        ];


        // system & application checks

        // PHP CLI BIN
        try {
            $phpCliBin = (bool) \Pimcore\Tool\Console::getPhpCli();
        } catch (\Exception $e) {
            $phpCliBin = false;
        }

        $checksApps[] = [
            "name" => "PHP",
            "state" => $phpCliBin ? "ok" : "error"
        ];

        // Composer
        $checksApps[] = [
            "name" => "Composer",
            "state" => Update::isComposerAvailable() ? "ok" : "error"
        ];


        // FFMPEG BIN
        try {
            $ffmpegBin = (bool) \Pimcore\Video\Adapter\Ffmpeg::getFfmpegCli();
        } catch (\Exception $e) {
            $ffmpegBin = false;
        }

        $checksApps[] = [
            "name" => "FFMPEG",
            "state" => $ffmpegBin ? "ok" : "warning"
        ];

        // WKHTMLTOIMAGE BIN
        try {
            $wkhtmltopdfBin = (bool) \Pimcore\Image\HtmlToImage::getWkhtmltoimageBinary();
        } catch (\Exception $e) {
            $wkhtmltopdfBin = false;
        }

        $checksApps[] = [
            "name" => "wkhtmltoimage",
            "state" => $wkhtmltopdfBin ? "ok" : "warning"
        ];

        // HTML2TEXT BIN
        try {
            $html2textBin = (bool) \Pimcore\Mail::determineHtml2TextIsInstalled();
        } catch (\Exception $e) {
            $html2textBin = false;
        }

        $checksApps[] = [
            "name" => "html2text (mbayer)",
            "state" => $html2textBin ? "ok" : "warning"
        ];

        // ghostscript BIN
        try {
            $ghostscriptBin = (bool) \Pimcore\Document\Adapter\Ghostscript::getGhostscriptCli();
        } catch (\Exception $e) {
            $ghostscriptBin = false;
        }

        $checksApps[] = [
            "name" => "Ghostscript",
            "state" => $ghostscriptBin ? "ok" : "warning"
        ];

        // LibreOffice BIN
        try {
            $libreofficeBin = (bool) \Pimcore\Document\Adapter\LibreOffice::getLibreOfficeCli();
        } catch (\Exception $e) {
            $libreofficeBin = false;
        }

        $checksApps[] = [
            "name" => "LibreOffice",
            "state" => $libreofficeBin ? "ok" : "warning"
        ];

        // image optimizer
        foreach (["zopflipng", "pngcrush", "jpegoptim", "pngout", "advpng", "cjpeg", "exiftool"] as $optimizerName) {
            try {
                $optimizerAvailable = \Pimcore\Tool\Console::getExecutable($optimizerName);
            } catch (\Exception $e) {
                $optimizerAvailable = false;
            }

            $checksApps[] = [
                "name" => $optimizerName,
                "state" => $optimizerAvailable ? "ok" : "warning"
            ];
        }

        // timeout binary
        try {
            $timeoutBin = (bool) \Pimcore\Tool\Console::getTimeoutBinary();
        } catch (\Exception $e) {
            $timeoutBin = false;
        }

        $checksApps[] = [
            "name" => "timeout - (GNU coreutils)",
            "state" => $timeoutBin ? "ok" : "warning"
        ];

        // pdftotext binary
        try {
            $pdftotextBin = (bool) \Pimcore\Document\Adapter\Ghostscript::getPdftotextCli();
        } catch (\Exception $e) {
            $pdftotextBin = false;
        }

        $checksApps[] = [
            "name" => "pdftotext - (part of poppler-utils)",
            "state" => $pdftotextBin ? "ok" : "warning"
        ];

        $viewParams = [
            "checksApps" => $checksApps,
            "checksPHP"  => $checksPHP,
            "checksMySQL" => $checksMySQL,
            "checksFS" => $checksFS
        ];

        return $this->render("PimcoreAdminBundle:Install/Check:index.html.php", $viewParams);
    }

    /**
     * @param string $base
     * @param array $data
     * @return array
     * @throws \Exception
     */
    protected function rscandir($base = '', &$data = [])
    {
        if (substr($base, -1, 1) != DIRECTORY_SEPARATOR) { //add trailing slash if it doesn't exists
            $base .= DIRECTORY_SEPARATOR;
        }

        if (count($data) > 20) {
            throw new \Exception("limit of 2000 files reached");
        }

        $array = array_diff(scandir($base), ['.', '..', '.svn']);
        foreach ($array as $value) {
            if (is_dir($base . $value)) {
                $data[] = $base . $value . DIRECTORY_SEPARATOR;
                $data = $this->rscandir($base . $value . DIRECTORY_SEPARATOR, $data);
            } elseif (is_file($base . $value)) {
                $data[] = $base . $value;
            }
        }

        return $data;
    }

    /**
     * @param FilterControllerEvent $event
     * @return Response|void
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $isMasterRequest = $event->isMasterRequest();
        if (!$isMasterRequest) {
            return;
        }

        $request = $event->getRequest();

        if (is_file(\Pimcore\Config::locateConfigFile("system.php"))) {
            // session authentication, only possible if user is logged in
            $user = \Pimcore\Tool\Authentication::authenticateSession();
            if (!$user instanceof User) {
                throw new AccessDeniedHttpException("Authentication failed!<br />If you don't have access to the admin interface any more, and you want to find out if the server configuration matches the requirements you have to rename the the system.php for the time of the check.");
            }
        } elseif ($request->get("mysql_username")) {
        } else {
            throw new AccessDeniedHttpException("Not possible... no database settings given.<br />Parameters: mysql_host,mysql_username,mysql_password,mysql_database");
        }
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // nothing to do
    }
}
