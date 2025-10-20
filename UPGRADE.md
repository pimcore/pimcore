# Upgrade Notes

## 12.3.0

### Deprecations

#### Symfony Templating Component

The `Symfony\Component\Templating\EngineInterface` and related templating services are deprecated and will be removed in version 13.0.
This is part of the migration to Symfony 7, which no longer includes the `symfony/templating` component.

**What's Deprecated:**

1. **Service**: `pimcore.templating.engine.delegating` and `Symfony\Component\Templating\EngineInterface`
    - Use `Twig\Environment` service directly instead
2. **Classes** using `EngineInterface`:

    - `Pimcore\Templating\TwigDefaultDelegatingEngine`
    - `Pimcore\Navigation\Renderer\AbstractRenderer`
    - `Pimcore\Document\Editable\EditableHandler`
    - `Pimcore\Controller\Controller`
    - `Pimcore\Workflow\Notification\NotificationEmailService`
    - `Pimcore\Bundle\GoogleMarketingBundle\EventListener\Frontend\GoogleTagManagerListener`
    - `Pimcore\Bundle\HeadlessDocumentsBundle\Headless\LayoutManager`
    - `Pimcore\Bundle\PersonalizationBundle\Targeting\Code\TargetingCodeGenerator`
    - `Pimcore\Bundle\PersonalizationBundle\Targeting\EventListener\ToolbarListener`
    - `Pimcore\Bundle\EcommerceFrameworkBundle\FilterService\FilterType\AbstractFilterType`

3. **Container Lookups**:
    - Retrieving `pimcore.templating.engine.delegating` directly from the container

**Timeline:**

-   Version 12.3: Deprecation warnings introduced
-   Version 13.0: Complete removal of symfony/templating support

**Action Required:**
Update your code to use `Twig\Environment` directly instead of `Symfony\Component\Templating\EngineInterface`.
All functionality remains the same, but the interface changes from the Symfony templating abstraction to Twig directly.

#### ParameterBag::getInt() and getBool() Symfony 7 Breaking Changes

Symfony 7 introduces breaking changes to `ParameterBag::getInt()` and `ParameterBag::getBool()` methods. These methods now throw `UnexpectedValueException` for invalid values instead of returning fallback values.

**What Changed:**

-   `$request->query->getInt('id')` now throws an exception if 'id' is not a valid integer
-   `$request->query->getBool('active')` now throws an exception if 'active' is not a valid boolean

**New Helper Available:**

Use `Pimcore\Helper\ParameterBagHelper` for safe parameter extraction:

```php
// Before (Symfony 6 - may throw exception in Symfony 7)
$page = $request->query->getInt('page', 1);
$active = $request->query->getBool('active', false);

// After (Symfony 7 compatible)
use Pimcore\Helper\ParameterBagHelper;

$page = ParameterBagHelper::getInt($request->query, 'page', 1);
$active = ParameterBagHelper::getBool($request->query, 'active', false);
```

**Timeline:**

-   Version 12.3: `ParameterBagHelper` introduced for forward compatibility
-   Symfony 7: Direct ParameterBag methods will throw exceptions

**Action Required:**
Replace `$request->query->getInt()` and `$request->query->getBool()` calls with `ParameterBagHelper::getInt()` and `ParameterBagHelper::getBool()` respectively.

```

```
