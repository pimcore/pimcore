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
 * @method \Pimcore\Model\Metadata\Predefined[] load()
 * @method int getTotalCount()
 */
class Listing extends \Pimcore\Model\Listing\JsonListing
{
    /**
     * @var \Pimcore\Model\Metadata\Predefined[]|null
     */
    protected $definitions = null;

    /**
     * @return \Pimcore\Model\Metadata\Predefined[]
     */
    public function getDefinitions()
    {
        if ($this->definitions === null) {
            $this->getDao()->load();
        }

        return $this->definitions;
    }

    /**
     * @param \Pimcore\Model\Metadata\Predefined[]|null $definitions
     *
     * @return $this
     */
    public function setDefinitions($definitions)
    {
        $this->definitions = $definitions;

        return $this;
    }

    /**
     * @param string $type
     * @param array $subTypes
     *
     * @return \Pimcore\Model\Metadata\Predefined[]
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
     * @param string $key
     * @param string $language
     * @param string|null $targetSubtype
     *
     * @return \Pimcore\Model\Metadata\Predefined|null
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
