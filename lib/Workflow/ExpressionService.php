<?php
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

namespace Pimcore\Workflow;

use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Workflow\EventListener\ExpressionLanguage;
use Symfony\Component\Workflow\Workflow;

class ExpressionService
{
    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authenticationChecker;

    /**
     * @var AuthenticationTrustResolverInterface
     */
    private $trustResolver;

    /**
     * @var RoleHierarchyInterface
     */
    private $roleHierarchy;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(ExpressionLanguage $expressionLanguage, TokenStorageInterface $tokenStorage, AuthorizationCheckerInterface $authenticationChecker, AuthenticationTrustResolverInterface $trustResolver, RoleHierarchyInterface $roleHierarchy = null, ValidatorInterface $validator = null)
    {
        $this->expressionLanguage = $expressionLanguage;
        $this->tokenStorage = $tokenStorage;
        $this->authenticationChecker = $authenticationChecker;
        $this->trustResolver = $trustResolver;
        $this->roleHierarchy = $roleHierarchy;
        $this->validator = $validator;
    }

    public function evaluateExpression(Workflow $workflow, $subject, string $expression)
    {
        return $this->expressionLanguage->evaluate($expression, $this->getVariables($workflow, $subject));
    }

    // code should be sync with Symfony\Component\Security\Core\Authorization\Voter\ExpressionVoter
    private function getVariables(Workflow $workflow, $subject)
    {
        $token = $this->tokenStorage->getToken() ?: new AnonymousToken('', 'anonymous', []);

        $roles = $token ? $token->getRoles() : [];
        if (null !== $this->roleHierarchy) {
            $roles = $this->roleHierarchy->getReachableRoles($roles);
        }

        $variables = [
            'token' => $token,
            'user' => $token->getUser(),
            'subject' => $subject,
            'roles' => array_map(function ($role) {
                return $role->getRole();
            }, $roles),
            // needed for the is_granted expression function
            'auth_checker' => $this->authenticationChecker,
            // needed for the is_* expression function
            'trust_resolver' => $this->trustResolver,
            // needed for the is_valid expression function
            'validator' => $this->validator,
        ];

        return $variables;
    }
}
