---
title: Custom Perspective Widgets
description: Register custom perspective widget types across backend API and frontend layers.
---

# Custom Perspective Widgets

> **Working example:** The
> [studio-example-bundle](https://github.com/pimcore/studio-example-bundle)
> contains a complete `example_iframe` custom widget covering
> all steps below.

Adding a custom perspective widget to Pimcore Studio requires
registration across two layers:
- **Studio Backend** — widget type config, repository,
  hydrator, schema
- **Studio UI** — Widget Editor dynamic type (perspective
  editor integration) and Widget Manager component (rendering)

The widget type string (e.g. `'example_iframe'`) is the common
identifier that ties both layers together — it must match
between the backend config, repository, hydrator, and the
frontend dynamic type `id` property.

---

## Studio Backend

### Step 1 — Register the Widget Type

Extend the `pimcore_studio_backend.widget_types` configuration.
Load this via your bundle extension's `prepend()` method.

> See
> [studio_backend.yaml](https://github.com/pimcore/studio-example-bundle/blob/main/config/pimcore/studio_backend.yaml)
> and
> [PimcoreStudioExampleExtension.php](https://github.com/pimcore/studio-example-bundle/blob/main/src/DependencyInjection/PimcoreStudioExampleExtension.php)
> in the example bundle.

### Step 2 — Widget Config Schema

Create a class extending
`Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\WidgetConfig`.
Add custom properties your widget needs (e.g. URL, height).

> See
> [IframeWidgetConfig.php](https://github.com/pimcore/studio-example-bundle/blob/main/src/Perspective/Schema/IframeWidgetConfig.php)
> in the example bundle.

### Step 3 — Widget Config Repository

Implement `WidgetConfigRepositoryInterface` with CRUD methods.
Use `LocationAwareConfigRepository` with the **settings-store**
backend for persistent storage — this stores data in the
database and works without container recompilation. Register
with the `pimcore.studio_backend.widget_repository` service
tag.

> See
> [IframeWidgetConfigRepository.php](https://github.com/pimcore/studio-example-bundle/blob/main/src/Perspective/Repository/IframeWidgetConfigRepository.php)
> in the example bundle.

### Step 4 — Widget Config Hydrator

Implement `WidgetConfigHydratorInterface` to transform raw
config arrays into your typed schema object. Register with the
`pimcore.studio_backend.widget_hydrator` service tag.

> See
> [IframeWidgetConfigHydrator.php](https://github.com/pimcore/studio-example-bundle/blob/main/src/Perspective/Hydrator/IframeWidgetConfigHydrator.php)
> in the example bundle.

For detailed backend documentation including configuration
storage options, wrapper repositories, and restrictions, see
[Extending Widgets](https://github.com/pimcore/studio-backend-bundle/blob/1.x/doc/03_Extending/09_Perspectives/01_Extending_Widgets.md).

---

## Studio UI (Frontend)

### Step 5 — Widget Editor Dynamic Type

Create a class extending `DynamicTypeWidgetTypeAbstract` (from
`@pimcore/studio-ui-bundle/modules/widget-editor`). This
registers your widget type in the perspective editor's "add
widget" dropdown and provides the configuration form.

Key properties:
- `id` — must match the backend widget type string
- `name` — display name in the dropdown
- `group` — groups related widget types together
- `icon` — icon from the Pimcore icon library
- `form()` — returns a React form component for widget
  configuration

Use the `@injectable()` decorator and bind the class in your
plugin's `onInit`. Register it in the
`DynamicTypeWidgetTypeRegistry` via a module.

> See
> [dynamic-type-widget-type-iframe.tsx](https://github.com/pimcore/studio-example-bundle/blob/main/assets/js/src/examples/custom-widgets/dynamic-types/definitions/dynamic-type-widget-type-iframe.tsx)
> in the example bundle.

### Step 6 — Widget Rendering Component

Register a React component in the `WidgetRegistry` (from
`@pimcore/studio-ui-bundle/modules/widget-manager`) so
perspectives can render your widget type at runtime.

The component name registered in `WidgetRegistry` should match
your widget type string:

```typescript
widgetRegistry.registerWidget({
  name: 'example_iframe',
  component: IframeWidget
})
```

> See
> [iframe-widget.tsx](https://github.com/pimcore/studio-example-bundle/blob/main/assets/js/src/examples/custom-widgets/components/iframe-widget.tsx)
> in the example bundle.

:::note

When embedding external content via `<iframe>`, two CSP
layers apply:

1. **Your Pimcore Studio CSP** — the `frame-src` directive
   must allow the target origin. Add allowed origins via the
   `pimcore_studio_ui` configuration:

```yaml
# config/pimcore_studio_ui.yaml
pimcore_studio_ui:
    csp_header:
        additional_urls:
            frame-src:
                - 'https://example.com/'
```

2. **The target site's CSP** — most third-party sites set
   `X-Frame-Options: DENY` or a `frame-ancestors` directive
   that blocks embedding. Only URLs you control or that
   explicitly allow framing will render.

:::

### Step 7 — Plugin Registration

Create a plugin that binds the dynamic type class in `onInit`
and registers the module in `onStartup`.

> See
> [index.ts](https://github.com/pimcore/studio-example-bundle/blob/main/assets/js/src/examples/custom-widgets/index.ts)
> and
> [custom-widgets-extension.tsx](https://github.com/pimcore/studio-example-bundle/blob/main/assets/js/src/examples/custom-widgets/modules/custom-widgets-extension.tsx)
> in the example bundle.

For detailed frontend documentation, see
[Widgets: Widget Manager and Widget Editor](https://github.com/pimcore/studio-ui-bundle/blob/1.x/doc/04_Extending/02_Plugin_Development_Examples/05_Use_the_Widget_Manager.md).
