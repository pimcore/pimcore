<?php

namespace Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService;

use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\RuleInterface;

class PricingManagerTokenInformation
{
    /**
     * Entered voucher token code
     *
     * @var string
     */
    protected $tokenCode;

    /**
     * Corresponding voucher token object
     *
     * @var Token
     */
    protected $tokenObject;

    /**
     * List of error messages that are defined in voucher token conditions of all
     * pricing rules that would take the given voucher token into account but are not
     * applied because some other conditions are not met.
     *
     * @var string[]
     */
    protected $errorMessages;

    /**
     * Flag that indicates if no pricing rules are defined for the given voucher token at all.
     *
     * @var bool
     */
    protected $hasNoValidRule = false;

    /**
     * List of not applied pricing rules that would take the given voucher token
     * into account but are not applied because some other conditions are not met.
     *
     * @var RuleInterface[]
     */
    protected $notAppliedRules;

    /**
     * List of applied pricing rules that require the given voucher token.
     *
     * @var RuleInterface[]
     */
    protected $appliedRules;

    /**
     * @return string
     */
    public function getTokenCode(): string
    {
        return $this->tokenCode;
    }

    /**
     * @param string $tokenCode
     */
    public function setTokenCode(string $tokenCode): void
    {
        $this->tokenCode = $tokenCode;
    }

    /**
     * @return Token
     */
    public function getTokenObject(): Token
    {
        return $this->tokenObject;
    }

    /**
     * @param Token $tokenObject
     */
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

    /**
     * @return bool
     */
    public function hasNoValidRule(): bool
    {
        return $this->hasNoValidRule;
    }

    /**
     * @param bool $hasNoValidRule
     */
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
