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

namespace Pimcore\Model\DataObject\Traits;

use Exception;
use Pimcore\Model\AbstractModel;
use Pimcore\Model\DataObject\OwnerAwareFieldInterface;

/**
 * @internal
 */
trait ObjectVarTrait
{
    /**
     * returns object values without the dao
     *
     */
    public function getObjectVars(): array
    {
        $data = get_object_vars($this);

        if ($this instanceof AbstractModel && isset($data['dao'])) {
            unset($data['dao']);
        }

        if ($this instanceof OwnerAwareFieldInterface && isset($data['_owner'])) {
            unset($data['_owner']);
        }

        return $data;
    }

    public function getObjectVar(?string $var): mixed
    {
        if (!$var || !property_exists($this, $var)) {
            return null;
        }

        return $this->{$var};
    }

    /**
     *
     * @return $this
     *
     * @throws Exception
     */
    public function setObjectVar(string $var, mixed $value, bool $silent = false): static
    {
        if (!property_exists($this, $var)) {
            if ($silent) {
                return $this;
            }

            throw new Exception('property ' . $var . ' does not exist');
        }
        $this->$var = $value;

        return $this;
    }
}
