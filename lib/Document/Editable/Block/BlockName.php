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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Document\Editable\Block;

use Pimcore\Model\Document\Editable;

/**
 * @internal
 *
 * Simple value object containing both name and real name of
 * a block.
 */
final class BlockName implements \JsonSerializable
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $realName;

    /**
     * @param string $name
     * @param string $realName
     */
    public function __construct(string $name, string $realName)
    {
        $this->name = $name;
        $this->realName = $realName;
    }

    /**
     * Factory method to create an instance from strings
     *
     * @param string $name
     * @param string $realName
     *
     * @return BlockName
     */
    public static function createFromNames(string $name, string $realName): BlockName
    {
        return new self($name, $realName);
    }

    /**
     * Create an instance from a document editable
     *
     * @param Editable $editable
     *
     * @return BlockName
     */
    public static function createFromEditable(Editable $editable): BlockName
    {
        return new self($editable->getName(), $editable->getRealName());
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getRealName(): string
    {
        return $this->realName;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'name' => $this->name,
            'realName' => $this->realName,
        ];
    }
}
