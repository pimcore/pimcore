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

namespace Pimcore\Model\Element;

use Exception;
use function is_scalar;
use function is_string;
use function mb_strlen;
use function mb_substr;

class ValidationException extends Exception
{
    private const MAX_PARAMETER_LENGTH = 100;

    protected array $contextStack = [];

    /** @var Exception[] */
    protected array $subItems = [];

    private ?string $translationKey = null;

    /** @var array<string, scalar|null> */
    private array $translationParameters = [];

    private ?string $fieldName = null;

    private ?string $fieldTitle = null;

    /** @var list<ValidationPathSegment> */
    private array $path = [];

    /** @var list<ValidationException> */
    private array $violations = [];

    /**
     * @return Exception[]
     */
    public function getSubItems(): array
    {
        return $this->subItems;
    }

    /**
     * @param Exception[] $subItems
     */
    public function setSubItems(array $subItems = []): void
    {
        $this->subItems = $subItems;
    }

    public function addContext(string $context): void
    {
        $this->contextStack[] = $context;
    }

    public function getContextStack(): array
    {
        return $this->contextStack;
    }

    public function __toString(): string
    {
        $result = parent::__toString();
        foreach ($this->subItems as $subItem) {
            $result .= "\n\n";
            $result .= $subItem->__toString();
        }

        return $result;
    }

    /**
     * Sets a translation key and its parameters for this error, so it can be shown in the user's language.
     * The key is a fixed string from code, never derived from user input. Parameters must be scalar; strings
     * are cut to 100 characters, other values are dropped. Never pass the value of a password or encrypted field.
     *
     * @param array<string, mixed> $parameters
     */
    public function setTranslation(string|ValidationMessageKey $key, array $parameters = []): static
    {
        $this->translationKey = $key instanceof ValidationMessageKey ? $key->value : $key;
        $this->translationParameters = [];

        foreach ($parameters as $name => $value) {
            if ($value !== null && !is_scalar($value)) {
                continue;
            }

            if (is_string($value) && mb_strlen($value) > self::MAX_PARAMETER_LENGTH) {
                $value = mb_substr($value, 0, self::MAX_PARAMETER_LENGTH) . '…';
            }

            $this->translationParameters[(string) $name] = $value;
        }

        return $this;
    }

    public function getTranslationKey(): ?string
    {
        return $this->translationKey;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function getTranslationParameters(): array
    {
        return $this->translationParameters;
    }

    /**
     * Sets the field that failed, on this error and on every collected violation that has no field yet.
     * A field that is already set is never overwritten, so a container can name its child fields first.
     *
     * @param string|null $title raw, untranslated title from the definition
     */
    public function setField(string $name, ?string $title = null): static
    {
        foreach ($this->getAffectedExceptions() as $exception) {
            if ($exception->fieldName === null) {
                $exception->fieldName = $name;
                $exception->fieldTitle = $title === '' ? null : $title;
            }
        }

        return $this;
    }

    public function getFieldName(): ?string
    {
        return $this->fieldName;
    }

    public function getFieldTitle(): ?string
    {
        return $this->fieldTitle;
    }

    /**
     * Appends a location level to this error and to every collected violation. Containers call it from the
     * inside out, so the path is ordered innermost first.
     */
    public function addPathSegment(ValidationPathSegment $segment): static
    {
        foreach ($this->getAffectedExceptions() as $exception) {
            $exception->path[] = $segment;
        }

        return $this;
    }

    /**
     * @return list<ValidationPathSegment> innermost first
     */
    public function getPath(): array
    {
        return $this->path;
    }

    /**
     * Collects leaf errors on an aggregate exception. Nested aggregates are flattened to their leaves.
     */
    public function addViolations(self ...$violations): static
    {
        foreach ($violations as $violation) {
            foreach ($violation->getViolations() as $leaf) {
                $this->violations[] = $leaf;
            }
        }

        return $this;
    }

    /**
     * The leaf errors: the collected violations of an aggregate, or the exception itself.
     *
     * @return list<ValidationException>
     */
    public function getViolations(): array
    {
        return $this->violations === [] ? [$this] : $this->violations;
    }

    /**
     * Creates an exception of the same class with a new message and the structured data of this one
     * (translation, field, path and violations). Message, code and previous exception follow the constructor;
     * context stack and sub items are not copied, the caller decides about those as before.
     */
    public function withMessage(string $message): static
    {
        $exceptionClass = static::class;
        $exception = new $exceptionClass($message, $this->getCode(), $this->getPrevious());
        $exception->translationKey = $this->translationKey;
        $exception->translationParameters = $this->translationParameters;
        $exception->fieldName = $this->fieldName;
        $exception->fieldTitle = $this->fieldTitle;
        $exception->path = $this->path;
        $exception->violations = $this->violations;

        return $exception;
    }

    public function getAggregatedMessage(): string
    {
        $msg = $this->getMessage();
        $contextStack = $this->getContextStack();
        if ($contextStack) {
            $msg .= '[ '.$contextStack[0].' ]';
        }

        $subItems = $this->getSubItems();
        if (count($subItems) > 0) {
            $msg .= ' (';
            $subItemParts = [];

            foreach ($subItems as $subItem) {
                if ($subItem instanceof self) {
                    $subItemMessage = $subItem->getAggregatedMessage();
                    $contextStack = $subItem->getContextStack();
                    if ($contextStack) {
                        $subItemMessage .= '[ '.$contextStack[0].' ]';
                    }
                } else {
                    $subItemMessage = $subItem->getMessage();
                }
                $subItemParts[] = $subItemMessage;
            }
            $msg .= implode(', ', $subItemParts);
            $msg .= ')';
        }

        return $msg;
    }

    /**
     * @return list<ValidationException>
     */
    private function getAffectedExceptions(): array
    {
        $exceptions = [$this];
        foreach ($this->violations as $violation) {
            if ($violation !== $this) {
                $exceptions[] = $violation;
            }
        }

        return $exceptions;
    }
}
