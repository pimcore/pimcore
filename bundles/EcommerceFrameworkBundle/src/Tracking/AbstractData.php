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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Tracking;

abstract class AbstractData implements \JsonSerializable
{
    protected string $id;

    protected array $additionalAttributes = [];

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Merge values into properties
     *
     * @param array $data
     * @param bool $overwrite
     *
     * @return $this
     */
    public function mergeValues(array $data, bool $overwrite = false): static
    {
        foreach ($data as $key => $value) {
            $getter = 'get' . ucfirst($key);
            $setter = 'set' . ucfirst($key);

            if (method_exists($this, $getter) && method_exists($this, $setter)) {
                if (null !== $this->$getter() && !$overwrite) {
                    continue;
                } else {
                    $this->$setter($value);
                }
            }
        }

        return $this;
    }

    /**
     * Add an additional attribute.
     *
     * @param string $attribute
     * @param mixed $value
     *
     * @return $this
     */
    public function addAdditionalAttribute(string $attribute, mixed $value): static
    {
        $this->additionalAttributes[$attribute] = $value;

        return $this;
    }

    /**
     * Get an additional attribute.
     *
     * @param string $attribute
     *
     * @return mixed
     */
    public function getAdditionalAttribute(string $attribute): mixed
    {
        return $this->additionalAttributes[$attribute];
    }

    /**
     * Get all additional attributes.
     *
     * @return array
     */
    public function getAdditionalAttributes(): array
    {
        return $this->additionalAttributes;
    }

    /**
     * Serialize all non-null properties
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        $json = [];
        $vars = get_object_vars($this);
        foreach ($vars as $key => $value) {
            if (null !== $value) {
                $json[$key] = $value;
            }
        }

        return $json;
    }
}
