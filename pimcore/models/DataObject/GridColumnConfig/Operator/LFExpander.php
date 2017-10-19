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
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\GridColumnConfig\Operator;

use Pimcore\Tool;

class LFExpander extends AbstractOperator
{
    private $prefix;

    public function __construct($config, $context = null)
    {
        parent::__construct($config, $context);

        $this->prefix = $config->prefix;
        $this->locales = $config->locales;
    }

    public function getLabeledValue($element)
    {
        $childs = $this->getChilds();
        if ($childs[0]) {
            $value = $childs[0]->getLabeledValue($element);

            return $value;
        }

        return null;
    }

    /**
     * @return mixed
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param mixed $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * @return bool
     */
    public function expandLocales()
    {
        return true;
    }

    /**
     * @return string[]
     */
    public function getValidLanguages()
    {
        if ($this->locales) {
            $validLanguages = $this->locales;
        } else {
            $validLanguages = Tool::getValidLanguages();
        }

        return $validLanguages;
    }
}
