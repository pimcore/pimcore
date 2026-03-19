# Adding Object Layout Types

> **Working example:** The
> [studio-example-bundle](https://github.com/pimcore/studio-example-bundle)
> contains a complete `simpleTextLayout` custom layout type example
> covering all steps below.

Adding a custom object layout type to Pimcore Studio requires
registration across two layers:
- **Pimcore Core** (layout class and config)
- **Studio UI** (frontend dynamic types and plugin)

Unlike data types, layout types are purely structural — they do
**not** require Generic Data Index adapters, Studio Backend data
adapters, grid column definitions, or search filters.

The `$fieldtype` property (e.g. `'simpleTextLayout'`) is the common
identifier that ties all layers together — it must match between the
PHP class, the field definition dynamic type `id`, and the object
layout dynamic type `id`.

---

## Pimcore Core

### Step 1 — PHP Layout Class

Create a class extending
`Pimcore\Model\DataObject\ClassDefinition\Layout`. This defines the
layout's properties and how they are serialized in the class
definition.

For simple layouts, you only need the `$fieldtype` property and any
custom properties (e.g. `$html` for rendering static content).

> See
> [SimpleTextLayout.php](https://github.com/pimcore/studio-example-bundle/blob/main/src/Model/DataObject/ClassDefinition/Layout/SimpleTextLayout.php)
> in the example bundle.

### Step 2 — Register the Layout Type

Register the layout type by extending the
`pimcore.objects.class_definitions.layout.map` configuration in a
config file that is
[automatically loaded](../04_Pimcore_Bundle_Developers_Guide/04_Auto_Loading_Config_and_Routing.md).

```yaml
pimcore:
    objects:
        class_definitions:
            layout:
                map:
                    simpleTextLayout: \App\Model\DataObject\ClassDefinition\Layout\SimpleTextLayout
```

> See
> [config.yaml](https://github.com/pimcore/studio-example-bundle/blob/main/config/pimcore/config.yaml)
> in the example bundle.

---

## Studio UI (Frontend)

### Step 3 — Object Layout Dynamic Type

Create a class extending `DynamicTypeObjectLayoutAbstract` (from
`@pimcore/studio-ui-bundle/modules/element`). This renders the
layout in the **data object editor** — the structural container
users see when editing objects.

Key properties and methods:
- `id` — must match `$fieldtype` from the PHP class
- `getObjectLayoutComponent()` — React component that renders the
  layout; receives all layout properties as props

Register in the `DynamicTypeObjectLayoutRegistry` via a module.

> See
> [dynamic-type-object-layout-simple-text-layout.tsx](https://github.com/pimcore/studio-example-bundle/blob/main/assets/js/src/examples/custom-object-layout-type/dynamic-types/definitions/dynamic-type-object-layout-simple-text-layout.tsx)
> in the example bundle.

### Step 4 — Field Definition Dynamic Type

Create a class extending `DynamicTypeFieldDefinitionLayoutAbstract`
(from `@pimcore/studio-ui-bundle/modules/field-definitions`). This
renders the layout configuration in the **class definition editor**
— how admins add and configure the layout.

Key methods:
- `id` — must match `$fieldtype` from the PHP class
- `getIcon()` — icon for the layout type dropdown
- `getGroup()` — category in the dropdown (e.g. `'text'`)
- `getSpecificFormFields()` — type-specific settings form

The base class `DynamicTypeFieldDefinitionLayoutAbstract` already
provides default behavior for child tags (`group:layout`,
`group:data`), tags (`group:layout`, `group:root`), and default
data. Override `getSpecificFormFields()` to add type-specific
settings, or return an empty fragment for layouts with no settings.

Register in the `DynamicTypeFieldDefinitionRegistry` via a module.

> See
> [dynamic-type-field-definition-simple-text-layout.tsx](https://github.com/pimcore/studio-example-bundle/blob/main/assets/js/src/examples/custom-object-layout-type/dynamic-types/definitions/dynamic-type-field-definition-simple-text-layout.tsx)
> in the example bundle.

### Step 5 — Plugin Registration

Create a plugin that binds both dynamic type classes in `onInit`
(singleton scope) and registers the module in `onStartup`.

> See
> [index.ts](https://github.com/pimcore/studio-example-bundle/blob/main/assets/js/src/examples/custom-object-layout-type/index.ts)
> and
> [simple-text-layout-module.tsx](https://github.com/pimcore/studio-example-bundle/blob/main/assets/js/src/examples/custom-object-layout-type/modules/simple-text-layout-module.tsx)
> in the example bundle.

---

## Complete Example

The
[studio-example-bundle](https://github.com/pimcore/studio-example-bundle)
implements the complete `simpleTextLayout` custom layout type. See
the
[Custom Object Layout Type](https://github.com/pimcore/studio-ui-bundle/blob/2025.x/doc/04_Extending/02_Plugin_Development_Examples/16_Custom_Object_Layout_Type.md)
plugin development example for an overview.
