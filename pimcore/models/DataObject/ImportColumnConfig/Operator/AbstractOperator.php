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

namespace Pimcore\Model\DataObject\ImportColumnConfig\Operator;

use Pimcore\Model\DataObject\ImportColumnConfig\AbstractConfigElement;
use Pimcore\Model\DataObject\ImportColumnConfig\ConfigElementInterface;
use Pimcore\Model\DataObject\ImportColumnConfig\OperatorInterface;

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
