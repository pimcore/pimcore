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

namespace Pimcore\Document\Tag\NamingStrategy\Migration\Analyze\Exception;

class BuildEditableException extends \RuntimeException
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var \LogicException[]
     */
    private $errors = [];

    /**
     * @var mixed
     */
    private $elementData;

    /**
     * @var bool
     */
    private $ignoreElement = false;

    public static function create(string $name, string $type, string $message, array $errors, $elementData = null, self $previous = null): self
    {
        $exception = new static($message, 1, $previous);
        $exception->setName($name);
        $exception->setType($type);
        $exception->setErrors($errors);
        $exception->setElementData($elementData);

        return $exception;
    }

    public static function fromPrevious(self $previous, string $message = null): self
    {
        if (null === $message) {
            $message = $previous->getMessage();
        }

        return self::create(
            $previous->getName(),
            $previous->getType(),
            $message,
            $previous->getErrors(),
            $previous->getElementData(),
            $previous
        );
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $type
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param \LogicException[] $errors
     */
    public function setErrors(array $errors)
    {
        $this->errors = $errors;
    }

    /**
     * @return \LogicException[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return mixed
     */
    public function getElementData()
    {
        return $this->elementData;
    }

    /**
     * @param mixed $elementData
     */
    public function setElementData($elementData)
    {
        $this->elementData = $elementData;
    }

    /**
     * @return bool
     */
    public function getIgnoreElement(): bool
    {
        return $this->ignoreElement;
    }

    /**
     * @param bool $ignoreElement
     */
    public function setIgnoreElement(bool $ignoreElement)
    {
        $this->ignoreElement = $ignoreElement;
    }
}
