<?php

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

namespace Pimcore\Tool;

/**
 * @deprecated
 */
class Mime
{
    /**
     * @param string $file
     * @param string|null $filename
     *
     * @return mixed|string
     *
     * @throws \Exception
     */
    public static function detect($file, $filename = null)
    {
        @trigger_error(
            sprintf('Method %s is deprecated and will be removed in Pimcore 10, use Symfony\Component\Mime\MimeTypes instead', __METHOD__),
            E_USER_DEPRECATED
        );

        if (!file_exists($file)) {
            throw new \Exception('File ' . $file . " doesn't exist");
        }

        if (filesize($file) !== 0) {
            if (!$filename) {
                $filename = basename($file);
            }

            $extensionMapping = \Pimcore::getContainer()->getParameter('pimcore.mime.extensions');

            // check for an extension mapping first
            if ($filename) {
                $extension = \Pimcore\File::getFileExtension($filename);
                if (array_key_exists($extension, $extensionMapping)) {
                    return $extensionMapping[$extension];
                }
            }

            // check with fileinfo, if there's no extension mapping
            $finfo = finfo_open(FILEINFO_MIME);
            $type = finfo_file($finfo, $file);
            finfo_close($finfo);

            if ($type !== false && !empty($type)) {
                if (strstr($type, ';')) {
                    $type = substr($type, 0, strpos($type, ';'));
                }

                return $type;
            }
        }

        // return default mime-type if we're unable to detect it
        return 'application/octet-stream';
    }
}
