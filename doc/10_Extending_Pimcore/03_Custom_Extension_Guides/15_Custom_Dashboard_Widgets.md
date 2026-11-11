---
title: Custom Dashboard Widgets
description: Register custom Studio Dashboards widget types across backend and frontend layers.
---

# Custom Dashboard Widgets

> **Working example:** The
> [studio-example-bundle](https://github.com/pimcore/studio-example-bundle)
> contains a complete `top_assets` custom dashboard widget covering
> all steps below.

Adding a custom widget type to Pimcore Studio Dashboards (provided
by the [Studio Dashboards Bundle](https://github.com/pimcore/studio-dashboards-bundle))
requires registration across two layers:
- **Studio Dashboards Backend** — widget type config, schema,
  config repository, config hydrator, data resolver
- **Studio UI** — a widget type definition registered in the
  dashboards widget-type registry, and a React component that
  renders the widget content

The widget type string (e.g. `'top_assets'`) is the common
identifier that ties both layers together — it must match between
the `pimcore_studio_dashboards.widget_types` entry, the
`getSupportedWidgetType()` methods of the backend services, and
the frontend definition's `id` property.

:::note

Dashboard widgets are not the same as *perspective* widgets — see
[Custom Perspective Widgets](./14_Custom_Perspective_Widgets.md)
for extending perspectives.

:::

---

## Studio Dashboards Backend

### Step 1 — Register the Widget Type

Add the type string to `pimcore_studio_dashboards.widget_types`
so the widget appears in the dashboard's add-widget dialog. Load
this via your bundle extension's `prepend()` method.

> See
> [PimcoreStudioExampleExtension.php](https://github.com/pimcore/studio-example-bundle/blob/main/src/DependencyInjection/PimcoreStudioExampleExtension.php)
> in the example bundle.

### Step 2 — Widget Config Schema

Create two classes: one extending
`Pimcore\Bundle\StudioDashboardsBundle\Schema\Widget\WidgetConfig`
(the hydrated config returned to the frontend) and one extending
`Pimcore\Bundle\StudioDashboardsBundle\Schema\Widget\SaveWidgetConfig`
(the validated payload persisted on save). Add the custom
properties your widget needs (e.g. a row limit).

> See
> [TopAssetsWidgetConfig.php](https://github.com/pimcore/studio-example-bundle/blob/main/src/Dashboards/Schema/TopAssetsWidgetConfig.php)
> and
> [SaveTopAssetsWidgetConfig.php](https://github.com/pimcore/studio-example-bundle/blob/main/src/Dashboards/Schema/SaveTopAssetsWidgetConfig.php)
> in the example bundle.

### Step 3 — Config Repository

Extend
`Pimcore\Bundle\StudioDashboardsBundle\Repository\Widget\AbstractConfigRepository`
to store and load the widget configuration via Pimcore's
`LocationAwareConfigRepository`. Register with the
`pimcore.studio_dashboards.widget_repository` service tag (the
`#[AutoconfigureTag]` attribute is sufficient). Because the
widgets of an external bundle are persisted under that bundle's
own configuration root node, define a config node and storage
location in your bundle's `Configuration` class and wire the
storage arguments in your services file.

> See
> [TopAssetsConfigRepository.php](https://github.com/pimcore/studio-example-bundle/blob/main/src/Dashboards/Repository/TopAssetsConfigRepository.php),
> [Configuration.php](https://github.com/pimcore/studio-example-bundle/blob/main/src/DependencyInjection/Configuration.php)
> and
> [studio_dashboards.yaml](https://github.com/pimcore/studio-example-bundle/blob/main/config/studio_dashboards.yaml)
> in the example bundle.

### Step 4 — Config Hydrator

Implement
`Pimcore\Bundle\StudioDashboardsBundle\Hydrator\Widget\ConfigHydratorInterface`
to transform raw config arrays into your typed schema object.
Register with the `pimcore.studio_dashboards.widget_hydrator`
service tag.

> See
> [TopAssetsConfigHydrator.php](https://github.com/pimcore/studio-example-bundle/blob/main/src/Dashboards/Hydrator/TopAssetsConfigHydrator.php)
> in the example bundle.

### Step 5 — Data Resolver

Implement
`Pimcore\Bundle\StudioDashboardsBundle\Resolver\Widget\DataResolverInterface`
to supply the data displayed inside the widget at runtime. The
resolver receives the hydrated config object, so it has access to
all stored configuration fields. Register with the
`pimcore.studio_dashboards.widget_data_resolver` service tag.

> See
> [TopAssetsResolver.php](https://github.com/pimcore/studio-example-bundle/blob/main/src/Dashboards/Resolver/TopAssetsResolver.php)
> in the example bundle.

For detailed backend documentation, see
[Creating Custom Widgets](https://github.com/pimcore/studio-dashboards-bundle/blob/2026.x/doc/10_Extending_Dashboards/README.md)
in the Studio Dashboards Bundle.

---

## Studio UI (Frontend)

### Step 6 — Widget Type Definition

Create a class implementing the dashboards widget-definition
contract with the `@injectable()` decorator. Key members:
- `id` — must match the backend widget type string
- `getWidgetComponent()` — returns the React component rendering
  the resolved widget data
- `getWidgetEditModeView()` — the config summary shown in edit mode
- `getWidgetAllowedVisualizations()` / `getWidgetIsAllowedBySlot()`
  — visualization and grid-slot constraints
- `getWidgetAdditionalFormElements()` — extra form fields for the
  add-widget dialog; the field `name`s must match the backend save
  schema properties

The dashboards bundle exposes only its root plugin entry through
module federation, so do not deep-import from it: resolve the
widget-type registry from the shared DI container by its string
service id `'StudioDashboards/DynamicTypes/WidgetType/Registry'`
and mirror the small definition contract locally.

> See
> [dynamic-type-widget-type-top-assets.tsx](https://github.com/pimcore/studio-example-bundle/blob/main/assets/js/src/examples/custom-dashboard-widgets/dynamic-types/definitions/dynamic-type-widget-type-top-assets.tsx)
> and
> [types.ts](https://github.com/pimcore/studio-example-bundle/blob/main/assets/js/src/examples/custom-dashboard-widgets/types.ts)
> in the example bundle.

### Step 7 — Widget Component

Create the React component that renders the widget content. Data
is fetched by the generic dashboards widget renderer (which calls
your backend data resolver) and passed in via props — the
component only renders it.

> See
> [top-assets-widget.tsx](https://github.com/pimcore/studio-example-bundle/blob/main/assets/js/src/examples/custom-dashboard-widgets/components/top-assets-widget.tsx)
> in the example bundle.

### Step 8 — Plugin Registration

Create a plugin that binds the definition class in `onInit` and
registers a module in `onStartup`; the module registers the
definition in the dashboards widget-type registry (guarded by
`container.isBound()` so the plugin also works when the dashboards
bundle is not installed).

> See
> [index.ts](https://github.com/pimcore/studio-example-bundle/blob/main/assets/js/src/examples/custom-dashboard-widgets/index.ts)
> and
> [custom-dashboard-widgets-extension.tsx](https://github.com/pimcore/studio-example-bundle/blob/main/assets/js/src/examples/custom-dashboard-widgets/modules/custom-dashboard-widgets-extension.tsx)
> in the example bundle.

For detailed frontend documentation, see
[Frontend Widget Type](https://github.com/pimcore/studio-dashboards-bundle/blob/2026.x/doc/10_Extending_Dashboards/04_Frontend_Widget_Type.md)
and
[Registering a Widget Type](https://github.com/pimcore/studio-dashboards-bundle/blob/2026.x/doc/10_Extending_Dashboards/05_Registering_Widget_Type.md)
in the Studio Dashboards Bundle.
