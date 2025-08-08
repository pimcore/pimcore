<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model;

use Exception;
use Pimcore\Model\Dao\DaoInterface;

interface ModelInterface
{
    public function getDao(): DaoInterface;

    public function setDao(Dao\AbstractDao $dao): static;

    /**
     * @throws Exception
     */
    public function initDao(?string $key = null, bool $forceDetection = false): void;

    public function setValues(array $data = []): static;

    public function setValue(string $key, mixed $value, bool $ignoreEmptyValues = false): static;
}
