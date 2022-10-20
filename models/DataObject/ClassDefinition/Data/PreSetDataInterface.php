<?php
declare(strict_types=1);

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

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model\DataObject\Concrete;

interface PreSetDataInterface
{
    /**
     * @param mixed $container any container type, e.g. Concrete, Localizedfield, AbstractData, etc ...
     * @param mixed $data
     * @param array $params
     *
     * @return mixed
     */
    public function preSetData(mixed $container, /**  mixed */ mixed $data, array $params = []): mixed /*: mixed*/;
}
