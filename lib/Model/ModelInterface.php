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
    public function initDao(string $key = null, bool $forceDetection = false): void;

    public function setValues(array $data = []): static;

    public function setValue(string $key, mixed $value, bool $ignoreEmptyValues = false): static;
}
