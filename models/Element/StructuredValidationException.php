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

use function is_finite;
use function is_float;
use function is_scalar;
use function is_string;
use function mb_scrub;
use function mb_strlen;
use function mb_substr;

/**
 * A validation error with structured data: a translation key with parameters, the field that failed and its
 * location (path), and the collected leaf errors (violations) of an aggregate error.
 *
 * The message, context stack and sub items behave exactly like those of ValidationException, so the legacy
 * message stays the same. The class is meant to be extended; subclasses must keep the constructor signature
 * of Exception (message, code, previous), see withMessage().
 */
class StructuredValidationException extends ValidationException
{
    private const MAX_PARAMETER_LENGTH = 100;

    private ?string $translationKey = null;

    /** @var array<string, scalar|null> */
    private array $translationParameters = [];

    private ?string $fieldName = null;

    private ?string $fieldTitle = null;

    /** @var list<ValidationPathSegment> */
    private array $path = [];

    /** @var list<StructuredValidationException> */
    private array $violations = [];

    /**
     * Returns the exception itself if it is already structured. Otherwise, creates a structured exception with
     * the same message, code, context stack and sub items (so getAggregatedMessage() returns the same text) and
     * the original exception as previous exception.
     */
    public static function from(ValidationException $exception): self
    {
        if ($exception instanceof self) {
            return $exception;
        }

        $structured = new self($exception->getMessage(), $exception->getCode(), $exception);
        $structured->contextStack = $exception->getContextStack();
        $structured->subItems = $exception->getSubItems();

        return $structured;
    }

    /**
     * Sets a translation key and its parameters for this error, so it can be shown in the user's language.
     * The key is a fixed string from code, never derived from user input. Parameters must be scalar; strings
     * are cut to 100 characters and invalid UTF-8 is replaced, other values (incl. INF/NAN) are dropped.
     * Never pass the value of a password or encrypted field.
     *
     * @param array<string, mixed> $parameters
     */
    public function setTranslation(string|ValidationMessageKey $key, array $parameters = []): static
    {
        $this->translationKey = $key instanceof ValidationMessageKey ? $key->value : $key;
        $this->translationParameters = [];

        foreach ($parameters as $name => $value) {
            // the parameters end up in JSON responses: no INF/NAN, no invalid UTF-8
            if (($value !== null && !is_scalar($value)) || (is_float($value) && !is_finite($value))) {
                continue;
            }

            if (is_string($value)) {
                $value = mb_scrub($value, 'UTF-8');
                if (mb_strlen($value) > self::MAX_PARAMETER_LENGTH) {
                    $value = mb_substr($value, 0, self::MAX_PARAMETER_LENGTH) . '…';
                }
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
     * Collects leaf errors on an aggregate exception. Plain validation exceptions are converted with from(),
     * nested aggregates are flattened to their leaves.
     */
    public function addViolations(ValidationException ...$violations): static
    {
        foreach ($violations as $violation) {
            foreach (self::from($violation)->getViolations() as $leaf) {
                $this->violations[] = $leaf;
            }
        }

        return $this;
    }

    /**
     * The leaf errors: the collected violations of an aggregate, or the exception itself.
     *
     * @return list<StructuredValidationException>
     */
    public function getViolations(): array
    {
        return $this->violations === [] ? [$this] : $this->violations;
    }

    /**
     * Copy of a validation exception with a new message, as the inheritance retries of the containers need it:
     * a structured exception keeps its data (see withMessage()), a plain one is rebuilt with its own class,
     * code and previous exception, as before.
     *
     * @internal
     */
    public static function copyWithMessage(ValidationException $exception, string $message): ValidationException
    {
        if ($exception instanceof self) {
            return $exception->withMessage($message);
        }

        $exceptionClass = $exception::class;

        return new $exceptionClass($message, $exception->getCode(), $exception->getPrevious());
    }

    /**
     * Creates an exception of the same class with a new message and the structured data of this one
     * (translation, field, path and violations). Message, code and previous exception follow the constructor;
     * context stack and sub items are not copied, the caller decides about those.
     * Subclasses must keep the constructor signature of Exception (message, code, previous).
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

    /**
     * @return list<StructuredValidationException>
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
