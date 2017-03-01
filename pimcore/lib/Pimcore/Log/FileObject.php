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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Log;

use Pimcore\File;

class FileObject
{
    /**
     * @var string
     */
    protected $filename;

    /**
     * @var string
     */
    protected $data;

    /**
     * @param string $data
     * @param string $filename
     */
    public function __construct($data, $filename=null)
    {
        if (!is_dir(PIMCORE_LOG_FILEOBJECT_DIRECTORY)) {
            File::mkdir(PIMCORE_LOG_FILEOBJECT_DIRECTORY);
        }

        $this->data = $data;
        $this->filename = $filename;

        if (empty($this->filename)) {
            $folderpath = PIMCORE_LOG_FILEOBJECT_DIRECTORY . strftime('/%Y/%m/%d');

            if (!is_dir($folderpath)) {
                mkdir($folderpath, 0775, true);
            }
            $this->filename = $folderpath."/".uniqid("fileobject_", true);
        }

        File::put($this->filename, $this->data);
    }

    /**
     * @return string
     */
    public function getSystemPath()
    {
        return $this->filename;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return str_replace(PIMCORE_PROJECT_ROOT."/", "", $this->filename);
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }
}
