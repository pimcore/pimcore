<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Log;

class FileObject {

    protected $filename;
    protected $data;

    /**
     * @param string $data
     * @param string $filename
     */
    public function __construct($data, $filename=null) {

        if(!is_dir(PIMCORE_LOG_FILEOBJECT_DIRECTORY))  {
            mkdir(PIMCORE_LOG_FILEOBJECT_DIRECTORY, 0755, true);
        }

        $this->data = $data;
        $this->filename = $filename;

        if(empty($this->filename)) {
            $folderpath = PIMCORE_LOG_FILEOBJECT_DIRECTORY . strftime('/%Y/%m/%d');

            if(!is_dir($folderpath)) {
                mkdir($folderpath, 0775, true);
            }
            $this->filename = $folderpath."/".uniqid("fileobject_",true);

        }

        file_put_contents($this->filename, $this->data);
    }

    /**
     * @return string
     */
    public function getSystemPath() {
        return $this->filename;
    }

    /**
     * @return string
     */
    public function getFilename() {
        return str_replace(PIMCORE_DOCUMENT_ROOT."/", "", $this->filename);
    }

    /**
     * @return string
     */
    public function getData() {
        return $this->data;
    }
}
