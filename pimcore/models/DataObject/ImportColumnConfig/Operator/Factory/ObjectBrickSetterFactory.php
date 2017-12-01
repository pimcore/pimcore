<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ImportColumnConfig\Operator\Factory;

use Pimcore\Model\DataObject\ImportColumnConfig\Operator\ObjectBrickSetter;
use Pimcore\Model\DataObject\ImportColumnConfig\OperatorInterface;
use Pimcore\Model\FactoryInterface;

class ObjectBrickSetterFactory implements OperatorFactoryInterface
{
    /**
     * @var FactoryInterface
     */
    private $modelFactory;

    public function __construct(FactoryInterface $modelFactory)
    {
        $this->modelFactory = $modelFactory;
    }

    public function build(\stdClass $configElement, $context = null): OperatorInterface
    {
        return new ObjectBrickSetter($this->modelFactory, $configElement, $context);
    }
}
