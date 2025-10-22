# Upgrade Notes

## 12.3.0

### Deprecations

#### Symfony 6.x Components Support

Support for all Symfony 6.x components will be removed in version 13.0.
This is part of the migration to Symfony 7, which requires updating all Symfony dependencies to version 7.3 or higher.

**What's Deprecated:**

-   All Symfony 6.x component versions
-   Mixed Symfony 6.x/7.x environments

**Timeline:**

-   Version 12.3: Deprecation notice for Symfony 6.x support
-   Version 13.0: Complete removal of Symfony 6.x compatibility

**Action Required:**
Update all Symfony components to version 7.3 or higher before upgrading to Pimcore 13.0.

**Note:**
If you want to stay on Symfony 6.x after updating to this version, you need to add explicit version constraints to your composer.json manually. If not, version 7.3 or later will be installed automatically.

#### Doctrine Annotations

The `doctrine/annotations` package has been removed as it's no longer needed with modern PHP 8+ attributes.
This is part of the migration to Symfony 7 and modern PHP practices.

**What's Removed:**

-   `doctrine/annotations` package dependency
-   Legacy annotation-based configurations

**Timeline:**

-   Version 12.3: `doctrine/annotations` package removed
-   All functionality now uses PHP 8+ attributes instead

**Action Required:**
No action required for most users. If your custom code relies on `doctrine/annotations` directly, update to use PHP 8+ attributes or add the dependency to your own composer.json if still needed.

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
