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
 * @package    Metadata
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Metadata\Predefined;

/**
 * @method \Pimcore\Model\Metadata\Predefined\Listing\Dao getDao()
 */
class Listing extends \Pimcore\Model\Listing\JsonListing
{
    /**
     * Contains the results of the list. They are all an instance of Metadata\Predefined
     *
     * @var array
     */
    public $definitions = [];

    /**
     * @return array
     */
    public function getDefinitions()
    {
        return $this->definitions;
    }

    /**
     * @param $definitions
     *
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
     *
     * @return Listing
     *
     * @throws \Exception
     */
    public static function getByTargetType($type, $subTypes)
    {
        if ($type != 'asset') {
            throw new \Exception('other types than assets are currently not supported');
        }

        $list = new self();

        if ($subTypes && !is_array($subTypes)) {
            $subTypes = [$subTypes];
        }

        if (is_array($subTypes)) {
            $list->setFilter(function ($row) use ($subTypes) {
                if (empty($row['targetSubtype'])) {
                    return true;
                }

                if (in_array($row['targetSubtype'], $subTypes)) {
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
     * @param null $targetSubtype
     *
     * @return \Pimcore\Model\Metadata\Predefined
     */
    public static function getByKeyAndLanguage($key, $language, $targetSubtype = null)
    {
        $list = new self();

        $list->setFilter(function ($row) use ($key, $language, $targetSubtype) {
            if ($row['name'] != $key) {
                return false;
            }

            if ($language && $language != $row['language']) {
                return false;
            }

            if ($targetSubtype && $targetSubtype != $row['targetSubtype']) {
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
