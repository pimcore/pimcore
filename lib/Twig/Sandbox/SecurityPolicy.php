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

use Pimcore\Model\AbstractModel;
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
    private array $allowedTags;

    private array $allowedFilters;

    /**
     * Explicitly allowed functions - always allowed, in addition to whatever the
     * `pimcore_*` prefix rule permits.
     */
    private array $allowedFunctions;

    /**
     * Classes to deny - includes Pimcore's built-in denylist (database/infrastructure
     * layer, `Pimcore\Model\User`) via the `sandbox_security_policy.blocked_classes`
     * default in default.yaml, plus whatever a site appends to that same option. Ignored
     * whenever $allowedClasses is non-empty (allowlist mode takes over entirely).
     */
    private array $blockedClasses;

    /**
     * When non-empty, switches from denylist mode to allowlist mode: only instances of
     * these classes (and their subclasses) may have their methods/properties accessed
     * from a sandboxed template. Every other object is denied, and $blockedClasses is no
     * longer consulted.
     */
    private array $allowedClasses;

    /**
     * `pimcore_*` function names to exclude from the prefix auto-allow - includes
     * Pimcore's built-in denylist of id/path lookup functions via the
     * `sandbox_security_policy.blocked_functions` default in default.yaml, plus whatever
     * a site appends to that same option.
     */
    private array $blockedFunctions;

    /**
     * FQCN => method names map. Methods listed here can never be called on a matching
     * instance from a sandboxed template, no matter the blocklist/allowlist
     * configuration - unlike $blockedClasses/$allowedClasses, this check is not bypassed
     * by allowlist mode. Populated via the `sandbox_security_policy.hard_blocked_methods`
     * default in default.yaml (secret/content-returning getters such as
     * `User::getPassword`, `Asset::getData`, ...), extendable by a site via that same
     * option.
     */
    private array $hardBlockedMethods;

    /**
     * FQCN => PCRE pattern list map. A matching instance may never call a method whose
     * name matches any of these patterns, subject to the same "not bypassed by allowlist
     * mode" guarantee as $hardBlockedMethods. Populated via the
     * `sandbox_security_policy.hard_blocked_method_patterns` default in default.yaml -
     * used for method families (e.g. every dynamically-generated `setXxx` setter on a
     * DataObject class) that cannot be enumerated by exact name.
     */
    private array $hardBlockedMethodPatterns;

    public function __construct(
        array $allowedTags = [],
        array $allowedFilters = [],
        array $allowedFunctions = [],
        array $blockedClasses = [],
        array $allowedClasses = [],
        array $blockedFunctions = [],
        array $hardBlockedMethods = [],
        array $hardBlockedMethodPatterns = [],
    ) {
        $this->allowedTags = $allowedTags;
        $this->allowedFilters = $allowedFilters;
        $this->allowedFunctions = $allowedFunctions;
        $this->blockedClasses = $blockedClasses;
        $this->allowedClasses = $allowedClasses;
        $this->blockedFunctions = $blockedFunctions;
        $this->hardBlockedMethods = $hardBlockedMethods;
        $this->setHardBlockedMethodPatterns($hardBlockedMethodPatterns);
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

    public function setBlockedFunctions(array $blockedFunctions): void
    {
        $this->blockedFunctions = $blockedFunctions;
    }

    public function setHardBlockedMethods(array $hardBlockedMethods): void
    {
        $this->hardBlockedMethods = $hardBlockedMethods;
    }

    /**
     * @throws \InvalidArgumentException if a pattern is not a valid PCRE pattern - this is a deny
     *                                   rule, so a malformed one must never be silently ignored
     */
    public function setHardBlockedMethodPatterns(array $hardBlockedMethodPatterns): void
    {
        foreach ($hardBlockedMethodPatterns as $class => $patterns) {
            if (!is_array($patterns)) {
                throw new \InvalidArgumentException(sprintf(
                    'Invalid hard-blocked method patterns for class "%s": expected a list of patterns, got %s',
                    $class,
                    get_debug_type($patterns),
                ));
            }

            foreach ($patterns as $pattern) {
                if (!is_string($pattern) || @preg_match($pattern, '') === false) {
                    throw new \InvalidArgumentException(sprintf(
                        'Invalid hard-blocked method pattern for class "%s": %s',
                        $class,
                        is_string($pattern) ? $pattern : get_debug_type($pattern),
                    ));
                }
            }
        }

        $this->hardBlockedMethodPatterns = $hardBlockedMethodPatterns;
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
            if (in_array($function, $this->allowedFunctions, true)) {
                continue;
            }

            if (!str_starts_with($function, 'pimcore_') || !$this->isPimcoreFunctionAllowed($function)) {
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
        $this->assertNotHardBlockedMethod($obj, $method);
        $this->assertNotHardBlockedMethodPattern($obj, $method);
        $this->assertNotDaoDelegatedMethod($obj, $method);

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

        if ($this->matchesAnyClass($obj, $this->blockedClasses)) {
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

        if ($this->matchesAnyClass($obj, $this->blockedClasses)) {
            $class = $obj::class;

            throw new SecurityNotAllowedPropertyError(
                sprintf('Accessing property "%s" on "%s" is not allowed in templates.', $property, $class),
                $class,
                $property,
            );
        }
    }

    /**
     * @param string $function a function name already known to start with `pimcore_`
     */
    private function isPimcoreFunctionAllowed(string $function): bool
    {
        return !in_array($function, $this->blockedFunctions, true);
    }

    /**
     * @param object $obj
     * @param string $method
     */
    private function assertNotHardBlockedMethod($obj, $method): void
    {
        foreach ($this->hardBlockedMethods as $class => $methods) {
            if (!class_exists($class, false) && !interface_exists($class, false)) {
                continue;
            }

            // PHP method names are case-insensitive: `GETPASSWORD` reaches `getPassword`
            if ($obj instanceof $class && in_array(strtolower($method), array_map('strtolower', $methods), true)) {
                $objClass = $obj::class;

                throw new SecurityNotAllowedMethodError(
                    sprintf('Calling method "%s" on "%s" is not allowed in templates.', $method, $objClass),
                    $objClass,
                    $method,
                );
            }
        }
    }

    /**
     * @param object $obj
     * @param string $method
     */
    private function assertNotHardBlockedMethodPattern($obj, $method): void
    {
        foreach ($this->hardBlockedMethodPatterns as $class => $patterns) {
            if (!class_exists($class, false) && !interface_exists($class, false)) {
                continue;
            }

            if (!$obj instanceof $class) {
                continue;
            }

            foreach ($patterns as $pattern) {
                // a PCRE runtime error (e.g. backtrack limit) yields false: fail closed
                if (preg_match($pattern, $method) !== 0) {
                    $objClass = $obj::class;

                    throw new SecurityNotAllowedMethodError(
                        sprintf('Calling method "%s" on "%s" is not allowed in templates.', $method, $objClass),
                        $objClass,
                        $method,
                    );
                }
            }
        }
    }

    /**
     * `AbstractModel::__call()` delegates every method the model does not declare to its DAO
     * (`beginTransaction()`, `commit()`, `rollBack()`, `moveThumbnailCache()`, ...), so those calls
     * reach the database layer under the model's class and bypass a check on the DAO class itself.
     * No template needs that: only the delegated read operations (`get*`/`is*`/`has*` magic
     * accessors, and `load*`/`count*` as used by listings, whose `load()` lives in the DAO) are
     * let through, everything else delegated to the DAO is denied.
     *
     * @param object $obj
     * @param string $method
     */
    private function assertNotDaoDelegatedMethod($obj, $method): void
    {
        if (!$obj instanceof AbstractModel || method_exists($obj, $method)) {
            return;
        }

        if (1 === preg_match('/^(get|is|has|load|count)/i', $method)) {
            return;
        }

        $objClass = $obj::class;

        throw new SecurityNotAllowedMethodError(
            sprintf('Calling method "%s" on "%s" is not allowed in templates.', $method, $objClass),
            $objClass,
            $method,
        );
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
