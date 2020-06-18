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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartInterface ;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\VoucherServiceException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition\VoucherToken;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\PricingManager;
use Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\Token as VoucherServiceToken;
use Pimcore\Localization\LocaleServiceInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DefaultService implements VoucherServiceInterface
{
    /**
     * @var int
     */
    protected $reservationMinutesThreshold;

    /**
     * @var int
     */
    protected $statisticsDaysThreshold;

    /**
     * @var string
     */
    protected $currentLocale;

    public function __construct(LocaleServiceInterface $localeService, array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->processOptions($resolver->resolve($options));

        $this->currentLocale = $localeService->getLocale();
    }

    protected function processOptions(array $options)
    {
        $this->reservationMinutesThreshold = $options['reservation_minutes_threshold'];
        $this->statisticsDaysThreshold = $options['statistics_days_threshold'];
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'reservation_minutes_threshold',
            'statistics_days_threshold',
        ]);

        $resolver->setDefaults([
            'reservation_minutes_threshold' => 5,
            'statistics_days_threshold' => 30,
        ]);

        $resolver->setAllowedTypes('reservation_minutes_threshold', 'int');
        $resolver->setAllowedTypes('statistics_days_threshold', 'int');
    }

    /**
     * @param string $code
     * @param CartInterface  $cart
     *
     * @return bool
     *
     * @throws VoucherServiceException
     */
    public function checkToken($code, CartInterface $cart)
    {
        if ($tokenManager = $this->getTokenManager($code)) {
            return $tokenManager->checkToken($code, $cart);
        }
        throw new VoucherServiceException('No Token for code ' .$code . ' exists.', VoucherServiceException::ERROR_CODE_NO_TOKEN_FOR_THIS_CODE_EXISTS);
    }

    /**
     * @param string $code
     * @param CartInterface  $cart
     *
     * @return bool
     */
    public function reserveToken($code, CartInterface $cart)
    {
        if ($tokenManager = $this->getTokenManager($code)) {
            return $tokenManager->reserveToken($code, $cart);
        }

        return false;
    }

    /**
     * @param string $code
     * @param CartInterface  $cart
     *
     * @return bool
     */
    public function releaseToken($code, CartInterface $cart)
    {
        if ($tokenManager = $this->getTokenManager($code)) {
            return $tokenManager->releaseToken($code, $cart);
        }

        return false;
    }

    /**
     * @param string $code
     * @param CartInterface  $cart
     * @param AbstractOrder $order
     *
     * @return bool
     */
    public function applyToken($code, CartInterface $cart, AbstractOrder $order)
    {
        if ($tokenManager = $this->getTokenManager($code)) {
            if ($orderToken = $tokenManager->applyToken($code, $cart, $order)) {
                $voucherTokens = $order->getVoucherTokens();
                $voucherTokens[] = $orderToken;
                $order->setVoucherTokens($voucherTokens);

                $this->releaseToken($code, $cart);

                return true;
            }
        }

        return false;
    }

    /**
     * Gets the correct token manager and calls removeAppliedTokenFromOrder(), which cleans up the
     * token usage and the ordered token object if necessary, removes the token object from the order.
     *
     * @param \Pimcore\Model\DataObject\OnlineShopVoucherToken $tokenObject
     * @param AbstractOrder $order
     *
     * @return bool
     */
    public function removeAppliedTokenFromOrder(\Pimcore\Model\DataObject\OnlineShopVoucherToken $tokenObject, AbstractOrder $order)
    {
        if ($tokenManager = $tokenObject->getVoucherSeries()->getTokenManager()) {
            $tokenManager->removeAppliedTokenFromOrder($tokenObject, $order);

            $voucherTokens = $order->getVoucherTokens();

            $newVoucherTokens = [];
            foreach ($voucherTokens as $voucherToken) {
                if ($voucherToken->getId() != $tokenObject->getId()) {
                    $newVoucherTokens[] = $voucherToken;
                }
            }

            $order->setVoucherTokens($newVoucherTokens);

            return true;
        }

        return false;
    }

    /**
     * @param CartInterface $cart
     * @param string|null $locale
     *
     * @return PricingManagerTokenInformation[]
     *
     * @throws UnsupportedException
     */
    public function getPricingManagerTokenInformationDetails(CartInterface $cart, string $locale = null): array
    {
        if (empty($cart->getVoucherTokenCodes())) {
            return [];
        }

        if (null == $locale) {
            $locale = $this->currentLocale;
        }

        // get all valid rules configured in system
        /** @var PricingManager $pricingManager */
        $pricingManager = Factory::getInstance()->getPricingManager();
        $validRules = $pricingManager->getValidRules();
        $validRulesAssoc = [];
        foreach ($validRules as $rule) {
            $validRulesAssoc[$rule->getId()] = $rule;
        }

        // get all applied rules for current cart and cart items
        $appliedRules = $cart->getPriceCalculator()->getAppliedPricingRules();

        // filter applied rules for voucher conditions
        $appliedRulesWithVoucherCondition = [];
        foreach ($appliedRules as $appliedRule) {
            $conditions = $appliedRule->getConditionsByType(VoucherToken::class);
            if ($conditions) {
                $appliedRulesWithVoucherCondition[$appliedRule->getId()] = $conditions;
            }
        }

        // calculate not applied rules with voucher conditions
        $notAppliedRules = array_udiff($validRules, $appliedRules, function ($rule1, $rule2) {
            return strcmp($rule1->getId(), $rule2->getId());
        });
        $notAppliedRulesWithVoucherCondition = [];
        foreach ($notAppliedRules as $notAppliedRule) {
            $conditions = $notAppliedRule->getConditionsByType(VoucherToken::class);
            if ($conditions) {
                $notAppliedRulesWithVoucherCondition[$notAppliedRule->getId()] = $conditions;
            }
        }

        $tokenInformationList = [];

        foreach ($cart->getVoucherTokenCodes() as $tokenCode) {
            $tokenInformation = new PricingManagerTokenInformation();
            $tokenInformation->setTokenCode($tokenCode);
            $tokenInformation->setTokenObject(VoucherServiceToken::getByCode($tokenCode));

            $notAppliedPricingRules = [];
            $appliedPricingRules = [];
            $errorMessages = [];

            foreach ($notAppliedRulesWithVoucherCondition as $ruleId => $conditions) {
                foreach ($conditions as $condition) {
                    /** @var VoucherToken $condition */
                    if ($condition->checkVoucherCode($tokenCode)) {
                        $errorMessages[] = $condition->getErrorMessage($locale);
                        $notAppliedPricingRules[] = $validRulesAssoc[$ruleId];
                    }
                }
            }

            if (!$errorMessages) {
                $hasRule = false;
                foreach ($appliedRulesWithVoucherCondition as $ruleId => $conditions) {
                    foreach ($conditions as $condition) {
                        /** @var VoucherToken $condition */
                        if ($condition->checkVoucherCode($tokenCode)) {
                            $hasRule = true;
                            $appliedPricingRules[] = $validRulesAssoc[$ruleId];
                            break;
                        }
                    }
                }

                $tokenInformation->setHasNoValidRule(!$hasRule);
            }

            $tokenInformation->setErrorMessages($errorMessages);
            $tokenInformation->setNotAppliedRules($notAppliedPricingRules);
            $tokenInformation->setAppliedRules($appliedPricingRules);

            $tokenInformationList[$tokenCode] = $tokenInformation;
        }

        return $tokenInformationList;
    }

    /**
     * @param string|null $seriesId
     *
     * @return bool
     */
    public function cleanUpReservations($seriesId = null)
    {
        if (isset($seriesId)) {
            return Reservation::cleanUpReservations($this->reservationMinutesThreshold, $seriesId);
        } else {
            return Reservation::cleanUpReservations($this->reservationMinutesThreshold);
        }
    }

    /**
     * @param \Pimcore\Model\DataObject\OnlineShopVoucherSeries $series
     *
     * @return bool
     */
    public function cleanUpVoucherSeries(\Pimcore\Model\DataObject\OnlineShopVoucherSeries $series)
    {
        return Token\Listing::cleanUpAllTokens($series->getId());
    }

    /**
     * @param null|string $seriesId
     *
     * @return bool
     */
    public function cleanUpStatistics($seriesId = null)
    {
        if (isset($seriesId)) {
            return Statistic::cleanUpStatistics($this->statisticsDaysThreshold, $seriesId);
        } else {
            return Statistic::cleanUpStatistics($this->statisticsDaysThreshold);
        }
    }

    /**
     * @param string $code
     *
     * @return bool|TokenManager\TokenManagerInterface
     */
    public function getTokenManager($code)
    {
        if ($token = Token::getByCode($code)) {
            if ($series = \Pimcore\Model\DataObject\OnlineShopVoucherSeries::getById($token->getVoucherSeriesId())) {
                return $series->getTokenManager();
            }
        }

        return false;
    }
}
