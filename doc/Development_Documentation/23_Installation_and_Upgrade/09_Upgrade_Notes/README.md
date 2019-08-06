# Upgrade Notes

## 6.1.0 

### E-Commerce Framework Refactorings
- Interface signature of `\Pimcore\Bundle\EcommerceFrameworkBundle\EnvironmentInterface` changed, added method `public function setDefaultCurrency(Currency $currency);`
- New method in `CartInterface`: `public function getPricingManagerTokenInformationDetails(): array` - default implementation in `AbstractCart` available
- New method in `CartPriceCalculatorInterface`: public function `getAppliedPricingRules()`: array; - default implementation in `CartPriceCalculator` available
- New method in `BracketInterface`: `public function getConditionsByType(string $typeClass): array;` - default implementation in `Bracket` available
- New method in `RuleInterface`: `public function getConditionsByType(string $typeClass): array` - default implementation in `Rule` available
- New method in `VoucherServiceInterface`: `public function getPricingManagerTokenInformationDetails(CartInterface $cart, string $locale = null): array;` - default implementation in `DefaultService` available
- Changed return type of `applyCartRules(CartInterface $cart)` in `PricingManagerInterface` - from `PricingManagerInterface` to `array`
- Introduction of new Checkout Manager architecture. It is parallel to the old architecture, which is deprecated now and will be removed in 
  Pimcore 7. For details see [Checkout Manager Details](../../10_E-Commerce_Framework/13_Checkout_Manager/08_Checkout_Manager_Details.md).
