<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Metadata
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Metadata\Predefined;

class Listing extends \Pimcore\Model\Listing\JsonListing
{

    /**
     * Contains the results of the list. They are all an instance of Metadata\Predefined
     *
     * @var array
     */
    public $definitions = array();

    /**
     * @return array
     */
    public function getDefinitions()
    {
        return $this->definitions;
    }

    /**
     * @param $definitions
     * @return $this
     */
    public function setDefinitions($definitions)
    {
        $this->definitions = $definitions;
        return $this;
    }

    /**
     * @param $type
     * @param $subTypes
     * @return Listing
     * @throws \Exception
     */
    public static function getByTargetType($type, $subTypes)
    {
        if ($type != "asset") {
            throw new \Exception("other types than assets are currently not supported");
        }

        $list = new self();

        if ($subTypes && !is_array($subTypes)) {
            $subTypes = array($subTypes);
        }

        if (is_array($subTypes)) {
            $list->setFilter(function ($row) use ($subTypes) {
                if (empty($row["targetSubtype"])) {
                    return true;
                }

                if (in_array($row["targetSubtype"], $subTypes)) {
                    return true;
                }
                return false;
            });
        }
        $list = $list->load();
        return $list;
    }

    /**
     * @param $key
     * @param $language
     * @return \Pimcore\Model\Metadata\Predefined
     */
    public static function getByKeyAndLanguage($key, $language, $targetSubtype = null)
    {
        $list = new self();

        $list->setFilter(function ($row) use ($key, $language, $targetSubtype) {
            if ($row["name"] != $key) {
                return false;
            }

            if ($language && $language != $row["language"]) {
                return false;
            }

            if ($targetSubtype && $targetSubtype != $row["targetSubtype"]) {
                return false;
            }
        });

        $list = $list->load();
        if ($list) {
            return $list[0];
        }
        return null;
    }
}
