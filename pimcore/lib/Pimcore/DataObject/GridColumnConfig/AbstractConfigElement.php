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

namespace Pimcore\DataObject\GridColumnConfig;

abstract class AbstractConfigElement implements ConfigElementInterface
{
    /**
     * @var
     */
    protected $attribute;
    /**
     * @var
     */
    protected $label;

    /**
     * @var null
     */
    protected $context;

    /**
     * AbstractConfigElement constructor.
     *
     * @param $config
     * @param null $context
     */
    public function __construct($config, $context = null)
    {
        $this->attribute = $config->attribute;
        $this->label = $config->label;

        $this->context = $context;
    }

    /**
     * @return mixed
     */
    public function getLabel()
    {
        return $this->label;
    }
}
