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

namespace Pimcore\Translate\Adapter;

class Json extends \Zend_Translate_Adapter
{
    /**
     * @var array
     */
    private $_data    = [];

    /**
     * Load translation data
     *
     * @param  string|array $filename Filename and full path to the translation source
     * @param  string $locale Locale/Language to add data for, identical with locale identifier,
     *                                  see Zend_Locale for more information
     * @param array $options
     * @return array
     * @internal param array $option OPTIONAL Options to use
     */
    protected function _loadTranslationData($filename, $locale, array $options = [])
    {
        $this->_data = [];
        $options     = $options + $this->_options;


        if (!isset($this->_data[$locale])) {
            $this->_data[$locale] = [];
        }

        $rawTranslations = json_decode(file_get_contents($filename), true);
        foreach ($rawTranslations as $entry) {
            if (!isset($translations[$entry["term"]])) {
                $this->_data[$locale][$entry["term"]] = $entry["definition"];
            }
        }

        return $this->_data;
    }

    /**
     * returns the adapters name
     *
     * @return string
     */
    public function toString()
    {
        return "Json";
    }
}
