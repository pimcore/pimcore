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

namespace Pimcore\DataObject\Import\ColumnConfig\Operator;

use Pimcore\DataObject\Import\ColumnConfig\AbstractConfigElement;
use Pimcore\DataObject\Import\ColumnConfig\ConfigElementInterface;

/**
 * @deprecated since v6.9 and will be removed in Pimcore 10.
 */
abstract class AbstractOperator extends AbstractConfigElement implements OperatorInterface
{
    /**
     * @var ConfigElementInterface[]
     */
    protected $childs = [];

    public function __construct(\stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        if (is_array($config->childs)) {
            $this->childs = $config->childs;
        }
    }

    /**
     * @return ConfigElementInterface[]
     */
    public function getChilds(): array
    {
        return $this->childs;
    }
}
