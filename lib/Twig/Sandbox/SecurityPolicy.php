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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Twig\Sandbox;

use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityPolicyInterface;

/**
 * Note: Reused to disable checks on object methods and properties.
 *
 * Represents a security policy which need to be enforced when sandbox mode is enabled.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class SecurityPolicy implements SecurityPolicyInterface
{
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
            //check if a function is allowed or a pimcore twig functions
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
        //do not perform any checks
    }

    /**
     * @param object $obj
     * @param string $property
     */
    public function checkPropertyAllowed($obj, $property): void
    {
        //do not perform any checks
    }
}
