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
