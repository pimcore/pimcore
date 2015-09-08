<?php

/**
 * Copyright 2012 Klarna AB
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * File containing the Klarna_Checkout_UserAgent class
 *
 * PHP version 5.3
 *
 * @category  Payment
 * @package   Klarna_Checkout
 * @author    Klarna <support@klarna.com>
 * @copyright 2012 Klarna AB
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link      http://developers.klarna.com/
 */

/**
 * UserAgent string builder
 *
 * @category  Payment
 * @package   Klarna_Checkout
 * @author    David K. <david.keijser@klarna.com>
 * @copyright 2012 Klarna AB
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache license v2.0
 * @link      http://developers.klarna.com/
 */
class Klarna_Checkout_UserAgent
{
    /**
     * Components of the user-agent
     *
     * @var array
     */
    private $_fields;

    /**
     * Initialise user-agent with default fields
     */
    public function __construct()
    {
        $this->_fields = array(
            'Library' => array(
                'name' => 'Klarna.ApiWrapper',
                'version' => '1.2.0',
            ),
            'OS' => array(
                'name' => php_uname('s'),
                'version' => php_uname('r')
            ),
            'Language' => array(
                'name' => 'PHP',
                'version' => phpversion()
            )
        );
    }

    /**
     * Add a new field to the user agent
     *
     * @param string $field Name of field
     * @param array  $data  data array with name, version and possibly options
     *
     * @return void
     */
    public function addField($field, array $data)
    {
        if (array_key_exists($field, $this->_fields)) {
            throw new Klarna_Checkout_Exception(
                "Unable to redefine field {$field}"
            );
        }
        $this->_fields[$field] = $data;
    }

    /**
     * Serialise fields to a user agent string
     *
     * @return string
     */
    public function __toString()
    {
        $parts = array();
        foreach ($this->_fields as $key => $value) {
            $parts[] = "$key/{$value['name']}_{$value['version']}";
            if (array_key_exists('options', $value)) {
                $parts[] = '(' . implode(' ; ', $value['options']) . ')';
            }
        }
        return implode(' ', $parts);
    }
}
