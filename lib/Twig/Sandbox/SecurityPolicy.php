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

namespace Pimcore\Twig\Sandbox;

use PDO;
use PDOStatement;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityNotAllowedPropertyError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityPolicyInterface;

/**
 * Represents a security policy which needs to be enforced when sandbox mode is enabled.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class SecurityPolicy implements SecurityPolicyInterface
{
    /**
     * Default classes whose instances must not be traversable from Twig templates.
     * Method calls and property accesses on objects that are instances of any of these
     * classes will throw, preventing templates from traversing into
     * database or infrastructure layers (e.g. object.getDao().db.fetchOne(...)).
     *
     * This is the fallback denylist used when no allowed classes are configured. It stays
     * as a private constant (rather than a configurable default) so that a site cannot
     * accidentally weaken it via config merging - use the `blocked_classes` config option
     * to extend it, or `allowed_classes` to replace this denylist model with an allowlist.
     */
    private const BLOCKED_CLASSES = [
        \Pimcore\Model\Dao\AbstractDao::class,
        \Doctrine\DBAL\Connection::class,
        PDO::class,
        PDOStatement::class,
        \Symfony\Component\DependencyInjection\ContainerInterface::class,
        \Symfony\Component\Process\Process::class,
    ];

    private array $allowedTags;

    private array $allowedFilters;

    private array $allowedFunctions;

    /**
     * Additional classes to deny, on top of the built-in BLOCKED_CLASSES. Ignored
     * whenever $allowedClasses is non-empty (allowlist mode takes over entirely).
     */
    private array $blockedClasses;

    /**
     * When non-empty, switches from denylist mode to allowlist mode: only instances of
     * these classes (and their subclasses) may have their methods/properties accessed
     * from a sandboxed template. Every other object is denied, and the denylist
     * (BLOCKED_CLASSES + $blockedClasses) is no longer consulted.
     */
    private array $allowedClasses;

    public function __construct(
        array $allowedTags = [],
        array $allowedFilters = [],
        array $allowedFunctions = [],
        array $blockedClasses = [],
        array $allowedClasses = [],
    ) {
        $this->allowedTags = $allowedTags;
        $this->allowedFilters = $allowedFilters;
        $this->allowedFunctions = $allowedFunctions;
        $this->blockedClasses = $blockedClasses;
        $this->allowedClasses = $allowedClasses;
    }

    public function setAllowedTags(array $tags): void
    {
        $this->allowedTags = $tags;
    }

    public function setAllowedFilters(array $filters): void
    {
        $this->allowedFilters = $filters;
    }

    public function setAllowedFunctions(array $functions): void
    {
        $this->allowedFunctions = $functions;
    }

    public function setBlockedClasses(array $blockedClasses): void
    {
        $this->blockedClasses = $blockedClasses;
    }

    public function setAllowedClasses(array $allowedClasses): void
    {
        $this->allowedClasses = $allowedClasses;
    }

    /**
     * True once at least one class has been configured in the allowlist. In that mode
     * the denylist (built-in + configured) is bypassed entirely: only allowlisted
     * classes are reachable from sandboxed templates.
     */
    private function isAllowlistMode(): bool
    {
        return [] !== $this->allowedClasses;
    }

    /**
     * @param string[] $tags
     * @param string[] $filters
     * @param string[] $functions
     */
    public function checkSecurity($tags, $filters, $functions): void
    {
        foreach ($tags as $tag) {
            if (!in_array($tag, $this->allowedTags)) {
                throw new SecurityNotAllowedTagError(sprintf('Tag "%s" is not allowed.', $tag), $tag);
            }
        }

        foreach ($filters as $filter) {
            if (!in_array($filter, $this->allowedFilters)) {
                throw new SecurityNotAllowedFilterError(sprintf('Filter "%s" is not allowed.', $filter), $filter);
            }
        }

        foreach ($functions as $function) {
            // check if a function is allowed or a Pimcore Twig function
            if (!in_array($function, $this->allowedFunctions) && !str_starts_with($function, 'pimcore_')) {
                throw new SecurityNotAllowedFunctionError(sprintf('Function "%s" is not allowed.', $function), $function);
            }
        }
    }

    /**
     * @param object $obj
     * @param string $method
     */
    public function checkMethodAllowed($obj, $method): void
    {
        if ($this->isAllowlistMode()) {
            if (!$this->matchesAnyClass($obj, $this->allowedClasses)) {
                $class = $obj::class;

                throw new SecurityNotAllowedMethodError(
                    sprintf('Calling method "%s" on "%s" is not allowed in templates.', $method, $class),
                    $class,
                    $method,
                );
            }

            return;
        }

        if ($this->matchesAnyClass($obj, [...self::BLOCKED_CLASSES, ...$this->blockedClasses])) {
            $class = $obj::class;

            throw new SecurityNotAllowedMethodError(
                sprintf('Calling method "%s" on "%s" is not allowed in templates.', $method, $class),
                $class,
                $method,
            );
        }
    }

    /**
     * @param object $obj
     * @param string $property
     */
    public function checkPropertyAllowed($obj, $property): void
    {
        if ($this->isAllowlistMode()) {
            if (!$this->matchesAnyClass($obj, $this->allowedClasses)) {
                $class = $obj::class;

                throw new SecurityNotAllowedPropertyError(
                    sprintf('Accessing property "%s" on "%s" is not allowed in templates.', $property, $class),
                    $class,
                    $property,
                );
            }

            return;
        }

        if ($this->matchesAnyClass($obj, [...self::BLOCKED_CLASSES, ...$this->blockedClasses])) {
            $class = $obj::class;

            throw new SecurityNotAllowedPropertyError(
                sprintf('Accessing property "%s" on "%s" is not allowed in templates.', $property, $class),
                $class,
                $property,
            );
        }
    }

    /**
     * @param object $obj
     * @param string[] $classes
     */
    private function matchesAnyClass($obj, array $classes): bool
    {
        foreach ($classes as $candidate) {
            if (!class_exists($candidate, false) && !interface_exists($candidate, false)) {
                continue;
            }

            if ($obj instanceof $candidate) {
                return true;
            }
        }

        return false;
    }
}
