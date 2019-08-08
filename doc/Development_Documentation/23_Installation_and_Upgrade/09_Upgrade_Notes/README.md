# Upgrade Notes

## 6.1.0 

### E-Commerce Framework Refactorings
- New methods in interfaces - only relevant, when you directly implement interfaces. If you just extend existing E-Commerce Framework
  implementations, default implementations for the new methods are provided.
  - New method in `EcommerceFrameworkBundle\EnvironmentInterface`: `public function setDefaultCurrency(Currency $currency);` - default implementation in `Environment` available.
  - New method in `CartInterface`: `public function getPricingManagerTokenInformationDetails(): array` - default implementation in `AbstractCart` available.
  - New method in `CartInterface`: `public function isCalculated(): bool` - default implementation in `AbstractCart` available.
  - New method in `CartPriceCalculatorInterface`: `public function getAppliedPricingRules(): array;` - default implementation in `CartPriceCalculator` available.
  - New method in `BracketInterface`: `public function getConditionsByType(string $typeClass): array;` - default implementation in `Bracket` available.
  - New method in `RuleInterface`: `public function getConditionsByType(string $typeClass): array` - default implementation in `Rule` available.
  - New method in `VoucherServiceInterface`: `public function getPricingManagerTokenInformationDetails(CartInterface $cart, string $locale = null): array;` - default implementation in `DefaultService` available.
  
- Changed return type of `applyCartRules(CartInterface $cart)` in `PricingManagerInterface` - from `PricingManagerInterface` to `array`  
- Introduction of new Checkout Manager architecture. It is parallel to the old architecture, which is deprecated now and will be removed in 
  Pimcore 7. For details see [Checkout Manager Details](../../10_E-Commerce_Framework/13_Checkout_Manager/08_Checkout_Manager_Details.md).

### E-Commerce Back Office
- Following views are migrated from .php to .twig (with snake_case naming)
 ```
 bundles/EcommerceFrameworkBundle/Resources/views/AdminOrder/detail.html.php
 bundles/EcommerceFrameworkBundle/Resources/views/AdminOrder/itemCancel.html.php
 bundles/EcommerceFrameworkBundle/Resources/views/AdminOrder/itemComplaint.html.php
 bundles/EcommerceFrameworkBundle/Resources/views/AdminOrder/itemEdit.html.php
 bundles/EcommerceFrameworkBundle/Resources/views/AdminOrder/list.html.php
 bundles/EcommerceFrameworkBundle/Resources/views/Includes/paging.html.php
 bundles/EcommerceFrameworkBundle/Resources/views/back-office.html.php
 bundles/EcommerceFrameworkBundle/Resources/views/Voucher/voucherCodeTabError.html.php
 bundles/EcommerceFrameworkBundle/Resources/views/Voucher/voucherCodeTabPattern.html.php
 bundles/EcommerceFrameworkBundle/Resources/views/Voucher/voucherCodeTabSingle.html.php
 bundles/EcommerceFrameworkBundle/Resources/views/Voucher/parts/paginator.html.php
 bundles/EcommerceFrameworkBundle/Resources/views/Voucher/parts/statistics.html.php
 bundles/EcommerceFrameworkBundle/Resources/views/Voucher/parts/usageStatisticScript.html.php
 bundles/EcommerceFrameworkBundle/Resources/views/Voucher/parts/modals/cleanupReservationsModal.html.php
 bundles/EcommerceFrameworkBundle/Resources/views/Voucher/parts/modals/pattern/cleanupModal.html.php
 bundles/EcommerceFrameworkBundle/Resources/views/Voucher/parts/modals/pattern/generateModal.html.php
 bundles/EcommerceFrameworkBundle/Resources/views/Voucher/parts/modals/single/assignSettingsModal.html.php
 
 ```