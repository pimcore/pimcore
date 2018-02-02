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

namespace Pimcore\Document\Tag\Block;

use Pimcore\Model\Document\Tag;

/**
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
        $this->name     = $name;
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
     * Create an instance from a document tag
     *
     * @param Tag $tag
     *
     * @return BlockName
     */
    public static function createFromTag(Tag $tag): BlockName
    {
        return new self($tag->getName(), $tag->getRealName());
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
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'name'     => $this->name,
            'realName' => $this->realName
        ];
    }
}
