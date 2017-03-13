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
 * @package    Webservice
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Webservice;

class Tool
{

    /**
     * @static
     * @return array
     */
    public static function createClassMappings()
    {
        $modelsDir = PIMCORE_PATH."/models/";
        $files = rscandir($modelsDir);
        $includePatterns = [
            "/Webservice\/Data/"
        ];

        foreach ($files as $file) {
            if (is_file($file)) {
                $file = str_replace($modelsDir, "", $file);
                $file = str_replace(".php", "", $file);
                $class = str_replace(DIRECTORY_SEPARATOR, "_", $file);

                if (\Pimcore\Tool::classExists($class)) {
                    $match = false;
                    foreach ($includePatterns as $pattern) {
                        if (preg_match($pattern, $file)) {
                            $match = true;
                            break;
                        }
                    }

                    if (strpos($file, "Webservice".DIRECTORY_SEPARATOR."Data") !== false) {
                        $match = true;
                    }

                    if (!$match) {
                        continue;
                    }

                    $classMap[str_replace("\\Pimcore\\Model\\Webservice\\Data\\", "", $class)] = $class;
                }
            }
        }

        return $classMap;
    }

    /**
     * @param $data
     * @return array
     */
    public static function keyValueReverseMapping($data)
    {
        if (is_array($data)) {
            $values = [];
            foreach ($data as $k=>$d) {
                $values[$k] = self::keyValueReverseMapping($d);
            }

            return $values;
        } elseif ($data instanceof \stdClass) {
            if ($data->key) {
                return [$data->key => self::keyValueReverseMapping($data->value)];
            }
            if ($data->item) {
                $values = [];
                foreach ($data->item as $item) {
                    $values = array_merge($values, self::keyValueReverseMapping($item));
                }

                return $values;
            }
        } else {
            return $data;
        }
    }
}
