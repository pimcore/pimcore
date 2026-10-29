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

namespace Pimcore\Model\DataObject\Classificationstore\Traits;

use Pimcore\Model\Exception\InvalidConfigException;
use Symfony\Component\Uid\Ulid;

trait IdentifierTrait
{
    private const IDENTIFIER_PATTERN = '/^[A-Za-z0-9][A-Za-z0-9_-]*$/';

    private const IDENTIFIER_MAX_LENGTH = 190;

    protected ?string $identifier = null;

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    /**
     * @throws InvalidConfigException
     */
    public function setIdentifier(string $identifier): void
    {
        if (preg_match(self::IDENTIFIER_PATTERN, $identifier) !== 1
            || strlen($identifier) > self::IDENTIFIER_MAX_LENGTH) {
            throw new InvalidConfigException(
                sprintf('"%s" is not a valid classification store identifier.', $identifier)
            );
        }

        if ($this->identifier === $identifier) {
            return;
        }

        if ($this->identifier !== null) {
            throw new InvalidConfigException(
                sprintf('The identifier "%s" cannot be changed to "%s".', $this->identifier, $identifier)
            );
        }

        $this->identifier = $identifier;
    }

    /**
     * Assigns an opaque identifier to an entity that does not carry one yet.
     */
    protected function generateIdentifier(): void
    {
        if ($this->identifier === null) {
            $this->identifier = Ulid::generate();
        }
    }
}
