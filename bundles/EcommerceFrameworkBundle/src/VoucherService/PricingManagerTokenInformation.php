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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService;

use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\RuleInterface;

class PricingManagerTokenInformation
{
    /**
     * Entered voucher token code
     *
     * @var string
     */
    protected string $tokenCode;

    /**
     * Corresponding voucher token object
     *
     * @var Token
     */
    protected Token $tokenObject;

    /**
     * List of error messages that are defined in voucher token conditions of all
     * pricing rules that would take the given voucher token into account but are not
     * applied because some other conditions are not met.
     *
     * @var string[]
     */
    protected array $errorMessages;

    /**
     * Flag that indicates if no pricing rules are defined for the given voucher token at all.
     *
     * @var bool
     */
    protected bool $hasNoValidRule = false;

    /**
     * List of not applied pricing rules that would take the given voucher token
     * into account but are not applied because some other conditions are not met.
     *
     * @var RuleInterface[]
     */
    protected array $notAppliedRules;

    /**
     * List of applied pricing rules that require the given voucher token.
     *
     * @var RuleInterface[]
     */
    protected array $appliedRules;

    public function getTokenCode(): string
    {
        return $this->tokenCode;
    }

    public function setTokenCode(string $tokenCode): void
    {
        $this->tokenCode = $tokenCode;
    }

    public function getTokenObject(): Token
    {
        return $this->tokenObject;
    }

    public function setTokenObject(Token $tokenObject): void
    {
        $this->tokenObject = $tokenObject;
    }

    /**
     * @return string[]
     */
    public function getErrorMessages(): array
    {
        return $this->errorMessages;
    }

    /**
     * @param string[] $errorMessages
     */
    public function setErrorMessages(array $errorMessages): void
    {
        $this->errorMessages = $errorMessages;
    }

    public function hasNoValidRule(): bool
    {
        return $this->hasNoValidRule;
    }

    public function setHasNoValidRule(bool $hasNoValidRule): void
    {
        $this->hasNoValidRule = $hasNoValidRule;
    }

    /**
     * @return RuleInterface[]
     */
    public function getNotAppliedRules(): array
    {
        return $this->notAppliedRules;
    }

    /**
     * @param RuleInterface[] $notAppliedRules
     */
    public function setNotAppliedRules(array $notAppliedRules): void
    {
        $this->notAppliedRules = $notAppliedRules;
    }

    /**
     * @return RuleInterface[]
     */
    public function getAppliedRules(): array
    {
        return $this->appliedRules;
    }

    /**
     * @param RuleInterface[] $appliedRules
     */
    public function setAppliedRules(array $appliedRules): void
    {
        $this->appliedRules = $appliedRules;
    }
}
