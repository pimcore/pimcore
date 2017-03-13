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

namespace Pimcore\Controller\Plugin;

use Pimcore\Logger;

class HttpErrorLog extends \Zend_Controller_Plugin_Abstract
{

    /**
     * @var null
     */
    protected $cacheKey = null;

    /**
     *
     */
    public function dispatchLoopShutdown()
    {
        $code = (string) $this->getResponse()->getHttpResponseCode();
        if ($code && ($code[0] == "4" || $code[0] == "5")) {
            $this->writeLog();

            // put the response into the cache, this is read in Pimcore\Controller\Action\Frontend::checkForErrors()
            $responseData = $this->getResponse()->getBody();
            if (strlen($responseData) > 20 && !session_id()) {
                // do not cache if there's no data or an active session

                if ($this->cacheKey) {
                    \Pimcore\Cache::save($responseData, $this->cacheKey, ["output"], 900, 9992);
                }
            }
        }
    }

    /**
     * @param $cacheKey
     */
    public function setCacheKey($cacheKey)
    {
        $this->cacheKey = $cacheKey;
    }

    /**
     *
     */
    public function writeLog()
    {
        $code = (string) $this->getResponse()->getHttpResponseCode();
        $db = \Pimcore\Db::get();

        try {
            $uri = $this->getRequest()->getScheme() . "://" . $this->getRequest()->getHttpHost() . $this->getRequest()->getRequestUri();

            $exists = $db->fetchOne("SELECT date FROM http_error_log WHERE uri = ?", $uri);
            if ($exists) {
                $db->query("UPDATE http_error_log SET `count` = `count` + 1, date = ? WHERE uri = ?", [time(), $uri]);
            } else {
                $db->insert("http_error_log", [
                    "uri" => $uri,
                    "code" => (int) $code,
                    "parametersGet" => serialize($_GET),
                    "parametersPost" => serialize($_POST),
                    "cookies" => serialize($_COOKIE),
                    "serverVars" => serialize($_SERVER),
                    "date" => time(),
                    "count" => 1
                ]);
            }
        } catch (\Exception $e) {
            Logger::error("Unable to log http error");
            Logger::error($e);
        }
    }
}
