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

namespace Pimcore\Model\Metadata\Predefined;

/**
 * @internal
 *
 * @method \Pimcore\Model\Metadata\Predefined\Listing\Dao getDao()
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
            $this->getDao()->loadList();
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
     * @param array|string|null $subTypes
     *
     * @return \Pimcore\Model\Metadata\Predefined[]|null
     *
     * @throws \Exception
     */
    public static function getByTargetType($type, $subTypes = null)
    {
        if ($type !== 'asset') {
            throw new \Exception('other types than assets are currently not supported');
        }

        $list = new self();

        if ($subTypes && !is_array($subTypes)) {
            $subTypes = [$subTypes];
        }

        if (is_array($subTypes)) {
            return array_filter($list->load(), function ($item) use ($subTypes) {
                if (empty($item->getTargetSubtype())) {
                    return true;
                }

                if (in_array($item->getTargetSubtype(), $subTypes)) {
                    return true;
                }

                return false;
            });
        }

        return $list->load();
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

        $definitions = array_filter($list->load(), function ($item) use ($key, $language, $targetSubtype) {
            if ($item->getName() != $key) {
                return false;
            }

            if ($language && $language != $item->getLanguage()) {
                return false;
            }

            if ($targetSubtype && $targetSubtype != $item->getTargetSubtype()) {
                return false;
            }

            return true;
        });

        return $definitions[0] ?? null;
    }

    /**
     * @return \Pimcore\Model\Metadata\Predefined[]
     */
    public function load()
    {
        return $this->getDefinitions();
    }
}
