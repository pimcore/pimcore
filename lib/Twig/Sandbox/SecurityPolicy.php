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
        \Pimcore\Model\User::class,
    ];

    /**
     * Methods that must never be callable from a Twig template on an instance of the given
     * class, no matter the blocklist/allowlist configuration - unlike BLOCKED_CLASSES/
     * $blockedClasses/$allowedClasses, this check is not bypassed by allowlist mode. Used for
     * classes that otherwise stay reachable (e.g. Asset is commonly used in templates for
     * filename/thumbnail access) but expose a handful of methods that return raw secrets or
     * file contents.
     */
    private const ALWAYS_BLOCKED_METHODS = [
        \Pimcore\Model\User::class => ['getPassword', 'getPasswordRecoveryToken', 'getTwoFactorAuthentication'],
        \Pimcore\Model\Asset::class => ['getData', 'getStream', 'getLocalFile', 'getTemporaryFile'],
    ];

    /**
     * Default `pimcore_*` functions excluded from the blanket prefix auto-allow, because
     * they look up and return a live model instance by id/path whose getters can expose
     * data outside the sandboxed template's intended scope (e.g. `pimcore_user(1)`,
     * `pimcore_asset(id)`).
     *
     * This is the fallback denylist consulted while $allowedPimcoreFunctions is empty - use
     * the `blocked_functions` config option to extend it, or `allowed_functions` to replace
     * the prefix auto-allow with an explicit allowlist instead. Mirrors BLOCKED_CLASSES /
     * `blocked_classes` / `allowed_classes` for object access.
     */
    private const BLOCKED_FUNCTIONS = [
        'pimcore_asset',
        'pimcore_asset_by_path',
        'pimcore_document',
        'pimcore_document_by_path',
        'pimcore_document_wrap_hardlink',
        'pimcore_object',
        'pimcore_object_by_path',
        'pimcore_object_classificationstore_group',
        'pimcore_object_brick_definition_key',
        'pimcore_site',
        'pimcore_site_by_root_id',
        'pimcore_site_by_domain',
        'pimcore_site_current',
        'pimcore_user',
    ];

    private array $allowedTags;

    private array $allowedFilters;

    /**
     * Explicitly allowed functions - always allowed, regardless of blocked/allowed_functions
     * mode, in addition to whatever the `pimcore_*` prefix rule permits.
     */
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

    /**
     * Additional `pimcore_*` function names to exclude from the prefix auto-allow, on top
     * of the built-in BLOCKED_FUNCTIONS. Ignored whenever $allowedPimcoreFunctions is
     * non-empty (allowlist mode takes over entirely).
     */
    private array $blockedFunctions;

    /**
     * When non-empty, switches the `pimcore_*` prefix rule from denylist mode to allowlist
     * mode: only the listed `pimcore_*` functions (plus whatever is in $allowedFunctions)
     * are callable from a sandboxed template. Every other `pimcore_*` function is denied,
     * and the denylist (BLOCKED_FUNCTIONS + $blockedFunctions) is no longer consulted.
     */
    private array $allowedPimcoreFunctions;

    public function __construct(
        array $allowedTags = [],
        array $allowedFilters = [],
        array $allowedFunctions = [],
        array $blockedClasses = [],
        array $allowedClasses = [],
        array $blockedFunctions = [],
        array $allowedPimcoreFunctions = [],
    ) {
        $this->allowedTags = $allowedTags;
        $this->allowedFilters = $allowedFilters;
        $this->allowedFunctions = $allowedFunctions;
        $this->blockedClasses = $blockedClasses;
        $this->allowedClasses = $allowedClasses;
        $this->blockedFunctions = $blockedFunctions;
        $this->allowedPimcoreFunctions = $allowedPimcoreFunctions;
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

    public function setAllowedPimcoreFunctions(array $allowedPimcoreFunctions): void
    {
        $this->allowedPimcoreFunctions = $allowedPimcoreFunctions;
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
     * True once at least one `pimcore_*` function has been configured in
     * $allowedPimcoreFunctions. In that mode the `pimcore_*` prefix denylist (built-in +
     * configured) is bypassed entirely: only allowlisted `pimcore_*` functions remain
     * callable from sandboxed templates.
     */
    private function isPimcoreFunctionAllowlistMode(): bool
    {
        return [] !== $this->allowedPimcoreFunctions;
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
        $this->assertNotAlwaysBlockedMethod($obj, $method);

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
     * @param string $function a function name already known to start with `pimcore_`
     */
    private function isPimcoreFunctionAllowed(string $function): bool
    {
        if ($this->isPimcoreFunctionAllowlistMode()) {
            return in_array($function, $this->allowedPimcoreFunctions, true);
        }

        return !in_array($function, [...self::BLOCKED_FUNCTIONS, ...$this->blockedFunctions], true);
    }

    /**
     * @param object $obj
     * @param string $method
     */
    private function assertNotAlwaysBlockedMethod($obj, $method): void
    {
        foreach (self::ALWAYS_BLOCKED_METHODS as $class => $methods) {
            if (!class_exists($class, false) && !interface_exists($class, false)) {
                continue;
            }

            if ($obj instanceof $class && in_array($method, $methods, true)) {
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
