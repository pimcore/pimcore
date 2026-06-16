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
     * Classes whose instances must not be traversable from Twig templates.
     * Method calls and property accesses on objects that are an instance of any of
     * these classes will throw, preventing templates from traversing into
     * database or infrastructure layers (e.g. object.getDao().db.fetchOne(...)).
     */
    private const BLOCKED_CLASSES = [
        \Pimcore\Model\Dao\AbstractDao::class,
        \Doctrine\DBAL\Connection::class,
        \Doctrine\DBAL\Driver\Connection::class,
        \PDO::class,
        \PDOStatement::class,
        \Symfony\Component\DependencyInjection\ContainerInterface::class,
        \Symfony\Component\Process\Process::class,
    ];

    private array $allowedTags;

    private array $allowedFilters;

    private array $allowedFunctions;

    public function __construct(array $allowedTags = [], array $allowedFilters = [], array $allowedFunctions = [])
    {
        $this->allowedTags = $allowedTags;
        $this->allowedFilters = $allowedFilters;
        $this->allowedFunctions = $allowedFunctions;
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
        foreach (self::BLOCKED_CLASSES as $blocked) {
            if (!class_exists($blocked, false) && !interface_exists($blocked, false)) {
                continue;
            }

            if ($obj instanceof $blocked) {
                $class = $obj::class;

                throw new SecurityNotAllowedMethodError(
                    sprintf('Calling method "%s" on "%s" is not allowed in templates.', $method, $class),
                    $class,
                    $method,
                );
            }
        }
    }

    /**
     * @param object $obj
     * @param string $property
     */
    public function checkPropertyAllowed($obj, $property): void
    {
        foreach (self::BLOCKED_CLASSES as $blocked) {
            if (!class_exists($blocked) && !interface_exists($blocked)) {
                continue;
            }

            if ($obj instanceof $blocked) {
                $class = $obj::class;

                throw new SecurityNotAllowedPropertyError(
                    sprintf('Accessing property "%s" on "%s" is not allowed in templates.', $property, $class),
                    $class,
                    $property,
                );
            }
        }
    }
}
