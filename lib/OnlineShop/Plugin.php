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
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace OnlineShop;

use Pimcore\Config\Config;
use Pimcore\File;

class Plugin extends \Pimcore\API\Plugin\AbstractPlugin implements \Pimcore\API\Plugin\PluginInterface {

    public static $configFile = "/EcommerceFramework/config/plugin_config.php";

    public function init() {
        parent::init();

        LegacyClassMappingTool::loadMapping();

        \Pimcore::getEventManager()->attach('system.console.init', function (\Zend_EventManager_Event $e) {
            /** @var \Pimcore\Console\Application $application */
            $application = $e->getTarget();

            // add a namespace to autoload commands from
            $application->addAutoloadNamespace(
                'OnlineShop\\Framework\\Console\\Command', __DIR__ . '/Framework/Console/Command'
            );
        });
    }

    public static function getConfig($readonly = true) {
        if (!$readonly) {
            $config = new Config(require PIMCORE_PLUGINS_PATH . self::$configFile, true);
        } else {
            $config = new Config(require PIMCORE_PLUGINS_PATH . self::$configFile);
        }
        return $config;
    }

    public static function setConfig($onlineshopConfigFile) {
        $config = self::getConfig(false);
        $config->onlineshop_config_file = $onlineshopConfigFile;

        // Write the config file
        File::putPhpFile(PIMCORE_PLUGINS_PATH . self::$configFile, to_php_data_file_format($config->toArray()));
    }



    /**
     *  install function
     * @return string $message statusmessage to display in frontend
     */
    public static function install() {

        Installer::install();

        // create status message
        if(self::isInstalled()){
            $statusMessage = "installed"; // $translate->_("plugin_objectassetfolderrelation_installed_successfully");
        } else {
            $statusMessage = "not installed"; // $translate->_("plugin_objectassetfolderrelation_could_not_install");
        }
        return $statusMessage;

    }



    /**
     *
     * @return boolean
     */
    public static function needsReloadAfterInstall() {
        return true;
    }

    /**
     *  indicates wether this plugins is currently installed
     * @return boolean
     */
    public static function isInstalled() {
        $result = null;
        try{
            if(Config::getSystemConfig()) {
                $result = \Pimcore\Db::get()->describeTable("plugin_onlineshop_cartitem");
            }
        } catch(\Exception $e){}
        return !empty($result);
    }

    /**
     * uninstall function
     * @return string $messaget status message to display in frontend
     */
    public static function uninstall() {
        Uninstaller::uninstall();

        // create status message
        if(!self::isInstalled()){
            $statusMessage = "uninstalled successfully"; //  $translate->_("plugin_objectassetfolderrelation_uninstalled_successfully");
        } else {
            $statusMessage = "did not uninstall"; // $translate->_("plugin_objectassetfolderrelation_could_not_uninstall");
        }
        return $statusMessage;

    }


    /**
     * @return string $jsClassName
     */
    public static function getJsClassName() {
    }

    /**
     *
     * @param string $language
     * @return string path to the translation file relative to plugin direcory
     */
    public static function getTranslationFile($language) {
        if ($language == "de") {
            return "/EcommerceFramework/texts/de.csv";
        } else if ($language == "en") {
            return "/EcommerceFramework/texts/en.csv";
        } else {
            return null;
        }
    }

    /**
     * @param \Pimcore\Model\Object\AbstractObject $object
     * @return void
     */
    public function postAddObject(\Pimcore\Model\Object\AbstractObject $object) {
        if ($object instanceof \OnlineShop\Framework\Model\IIndexable) {
            $indexService = \OnlineShop\Framework\Factory::getInstance()->getIndexService();
            $indexService->updateIndex($object);
        }
    }

    /**
     * @param \Pimcore\Model\Object\AbstractObject $object
     * @return void
     */
    public function postUpdateObject(\Pimcore\Model\Object\AbstractObject $object) {
        if ($object instanceof \OnlineShop\Framework\Model\IIndexable) {
            $indexService = \OnlineShop\Framework\Factory::getInstance()->getIndexService();
            $indexService->updateIndex($object);
        }
    }

    public function preDeleteObject(\Pimcore\Model\Object\AbstractObject $object) {
        if ($object instanceof \OnlineShop\Framework\Model\IIndexable) {
            $indexService = \OnlineShop\Framework\Factory::getInstance()->getIndexService();
            $indexService->deleteFromIndex($object);
        }

        // Delete tokens when a a configuration object gets removed.
        if($object instanceof \Pimcore\Model\Object\OnlineShopVoucherSeries){
            $voucherService = \OnlineShop\Framework\Factory::getInstance()->getVoucherService();
            $voucherService->cleanUpVoucherSeries($object);
        }
    }

    /**
     * @var \Zend_Log
     */
    private static $sqlLogger = null;

    /**
     * @return \Zend_Log
     */
    public static function getSQLLogger() {
        if(!self::$sqlLogger) {


            // check for big logfile, empty it if it's bigger than about 200M
            $logfilename = PIMCORE_WEBSITE_PATH . '/var/log/online-shop-sql.log';
            if (is_file($logfilename) && filesize($logfilename) > 200000000) {
                file_put_contents($logfilename, "");
            }

            $prioMapping = array(
                "debug" => \Zend_Log::DEBUG,
                "info" => \Zend_Log::INFO,
                "notice" => \Zend_Log::NOTICE,
                "warning" => \Zend_Log::WARN,
                "error" => \Zend_Log::ERR,
                "critical" => \Zend_Log::CRIT,
                "alert" => \Zend_Log::ALERT,
                "emergency" => \Zend_Log::EMERG
            );

            $prios = array();
            $conf = \Pimcore\Config::getSystemConfig();
            if($conf && $conf->general->debugloglevel) {
                $prioMapping = array_reverse($prioMapping);
                foreach ($prioMapping as $level => $state) {
                    $prios[] = $prioMapping[$level];
                    if($level == $conf->general->debugloglevel) {
                        break;
                    }
                }
            }
            else {
                // log everything if config isn't loaded (eg. at the installer)
                foreach ($prioMapping as $p) {
                    $prios[] = $p;
                }
            }

            $logger = new \Zend_Log();
            $logger->addWriter(new \Zend_Log_Writer_Stream($logfilename));

            foreach($prioMapping as $key => $mapping) {
                if(!array_key_exists($mapping, $prios)) {
                    $logger->addFilter(new \Zend_Log_Filter_Priority($mapping, "!="));
                }
            }

            self::$sqlLogger = $logger;
        }
        return self::$sqlLogger;
    }


    public function maintenance() {
        $checkoutManager = \OnlineShop\Framework\Factory::getInstance()->getCheckoutManager(new \OnlineShop\Framework\CartManager\Cart());
        $checkoutManager->cleanUpPendingOrders();

        \OnlineShop\Framework\Factory::getInstance()->getVoucherService()->cleanUpReservations();
        \OnlineShop\Framework\Factory::getInstance()->getVoucherService()->cleanUpStatistics();
    }
}
