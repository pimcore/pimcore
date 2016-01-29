<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\File\Transfer\Adapter;

use Pimcore\File;

class Http extends \Zend_File_Transfer_Adapter_Http
{

    use \Pimcore\File\Transfer\Adapter\AdapterTrait;
    /**
     * @var null
     */
    protected $httpClient = null;

    /**
     * @param \Zend_Http_Client $httpClient
     */
    public function setHttpClient(\Zend_Http_Client $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @return null
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * @param null $options
     * @return bool|void
     * @throws \Exception
     * @throws \Zend_Http_Client_Exception
     */
    public function send($options = null)
    {
        $sourceFile = $this->getSourceFile();
        $destinationFile = $this->getDestinationFile();

        if (!$sourceFile) {
            throw new \Exception("No sourceFile provided.");
        }

        if (!$destinationFile) {
            throw new \Exception("No destinationFile provided.");
        }

        if (is_array($options)) {
            if ($options['overwrite'] == false && file_exists($destinationFile)) {
                throw new \Exception("Destination file : '" . $destinationFile ."' already exists.");
            }
        }

        if (!$this->getHttpClient()) {
            $httpClient = \Pimcore\Tool::getHttpClient(null, array('timeout' => 3600*60));
        } else {
            $httpClient = $this->getHttpClient();
        }

        $httpClient->setUri($this->getSourceFile());
        $response = $httpClient->request();
        if ($response->isSuccessful()) {
            $data = $response->getBody();
            File::mkdir(dirname($destinationFile));
            $result = File::put($destinationFile, $data);
            if ($result === false) {
                throw new \Exception("Couldn't write destination file:" . $destinationFile);
            }
        } else {
            throw new \Exception("Couldn't download file:" . $sourceFile);
        }
        return true;
    }
}
