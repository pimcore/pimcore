# Adding Object Datatypes

> **Working example:** The
> [studio-example-bundle](https://github.com/pimcore/studio-example-bundle)
> contains a complete `simpleText` custom datatype example covering
> all steps below.

Adding a custom object datatype to Pimcore Studio requires registration
across four layers: 
- **Pimcore Core** (field definition class and config)
- **Generic Data Index** (search index adapter)
- **Studio Backend** (data adapter, grid column definition)
- **Studio UI** (frontend dynamic types and plugin).

The `getFieldType()` return value (e.g. `'simpleText'`) is the common
identifier that ties all layers together — it must match between the
PHP class, the GDI adapter tag, the Studio Backend column definition,
and the frontend dynamic type `id` property.

---

## Pimcore Core

### Step 1 — PHP Field Definition Class

Create a class extending
`Pimcore\Model\DataObject\ClassDefinition\Data` that defines how the
datatype is stored in the database, how getters/setters are generated,
and how data is serialized.

Key interfaces to implement:
- `ResourcePersistenceAwareInterface` — database column mapping
- `QueryResourcePersistenceAwareInterface` — query column mapping
- `TypeDeclarationSupportInterface` — PHP type declarations
- `NormalizerInterface` — data normalization for API transport

> See
> [SimpleText.php](https://github.com/pimcore/studio-example-bundle/blob/main/src/Model/DataObject/ClassDefinition/Data/SimpleText.php)
> in the example bundle.

### Step 2 — Register the Field Type

Register the datatype by extending the `pimcore.objects.class_definitions.data.map` 
configuration in a config file that is
[automatically loaded](../04_Pimcore_Bundle_Developers_Guide/04_Auto_Loading_Config_and_Routing.md).

> See
> [config.yaml](https://github.com/pimcore/studio-example-bundle/blob/main/config/pimcore/config.yaml)
> in the example bundle.

---

## Generic Data Index

### Step 3 — Field Definition Adapter (Required for Indexing)

The Generic Data Index (GDI) bundle indexes data object fields in
OpenSearch/Elasticsearch for search and filtering. Without a
registered adapter for your field type, the data will not be indexed
and grid filtering will not return results.

For simple string fields, reuse the built-in `TextKeywordAdapter`.
Register it as a **non-shared** (`shared: false`) service with the
`pimcore.generic_data_index.data-object.search_index_field_definition`
tag and your field type as the `type` attribute. The `shared: false`
setting is required because adapters are stateful — each instance
gets a specific field definition set on it.

Load the config from your bundle's DI extension `load()` method.
After registration, rebuild the search index:

```bash
bin/console generic-data-index:update:index -c CLASS_ID -r
```

For details on available built-in adapters and creating custom ones, see the
[Custom Field Definition Adapters](https://github.com/pimcore/generic-data-index-bundle/blob/1.x/doc/05_Extending_Data_Index/07_Custom_Field_Definition_Adapters.md)
documentation.

> See
> [generic-data-index.yaml](https://github.com/pimcore/studio-example-bundle/blob/main/config/generic-data-index.yaml)
> in the example bundle.

---

## Studio Backend

### Step 4 — Data Adapter (Required for Save/Load)

The Studio Backend Bundle uses data adapters to save and load field
data via the API. Without a mapping for your field type, the field
will render in the editor but data will not persist.

For simple string fields, reuse the existing `StringAdapter` by adding your field 
type to the `pimcore_studio_backend.data_object_data_adapter_mapping`
configuration. Load this config in your bundle's DI extension using
`prepend()` (since it targets another bundle's config key).

For complex field types, create your own adapter implementing
`SetterDataInterface` and tag it with `pimcore.studio_backend.data_adapter`.

> See
> [studio_backend.yaml](https://github.com/pimcore/studio-example-bundle/blob/main/config/pimcore/studio_backend.yaml)
> and
> [PimcoreStudioExampleExtension.php](https://github.com/pimcore/studio-example-bundle/blob/main/src/DependencyInjection/PimcoreStudioExampleExtension.php)
> in the example bundle.

### Step 5 — Grid Column Definition (Required for Grid View)

To make your field available as a column in the data object grid,
register a `ColumnDefinitionInterface` implementation with the
`pimcore.studio_backend.grid_column_definition` service tag.

Key methods:
- `getType()` — must return `'data-object.' . $fieldType`
- `getFrontendType()` — determines grid cell rendering (e.g.
  `'input'` for text fields, `'select'` for selects)
- `isSortable()`, `isFilterable()`, `isExportable()` — grid
  capabilities

Without this registration, the field will not appear in the grid
column configuration or filter sidebar.

> See
> [SimpleTextDefinition.php](https://github.com/pimcore/studio-example-bundle/blob/main/src/Grid/Column/Definition/SimpleTextDefinition.php)
> in the example bundle.

---

## Studio UI (Frontend)

### Step 6 — Object Data Dynamic Type

Create a class extending `DynamicTypeObjectDataAbstractInput` (from
`@pimcore/studio-ui-bundle/modules/element`). This renders the field
in the **data object editor** and **grid** — how users enter and
view data.

Key properties and methods:
- `id` — must match `getFieldType()` from the PHP class
- `getObjectDataComponent()` — React component for the field; the
  abstract input class provides a default `<Input>`
- `getDefaultGridColumnWidth()` — optional grid column width
- `dynamicTypeFieldFilterType` — determines the grid filter
  behavior (see [Filter Type Mapping](#filter-type-mapping) below)

Register in the `DynamicTypeObjectDataRegistry` via a module.

> See
> [dynamic-type-object-data-simple-text.tsx](https://github.com/pimcore/studio-example-bundle/blob/main/assets/js/src/examples/custom-object-datatype/dynamic-types/definitions/dynamic-type-object-data-simple-text.tsx)
> in the example bundle.

### Step 7 — Field Definition Dynamic Type

Create a class extending `DynamicTypeFieldDefinitionDataAbstract`
(from `@pimcore/studio-ui-bundle/modules/field-definitions`). This
renders the field configuration in the **class definition editor** —
how admins configure the field.

Key methods:
- `id` — must match `getFieldType()` from the PHP class
- `getIcon()` — icon for the field type dropdown
- `getGroup()` — category in the dropdown (e.g. `'text'`)
- `getSpecificFormFields()` — type-specific settings form

Register in the `DynamicTypeFieldDefinitionRegistry` via a module.

> See
> [dynamic-type-field-definition-simple-text.tsx](https://github.com/pimcore/studio-example-bundle/blob/main/assets/js/src/examples/custom-object-datatype/dynamic-types/definitions/dynamic-type-field-definition-simple-text.tsx)
> in the example bundle.

### Step 8 — Plugin Registration

Create a plugin that binds both dynamic type classes in `onInit`
(singleton scope) and registers the module in `onStartup`.

> See
> [index.ts](https://github.com/pimcore/studio-example-bundle/blob/main/assets/js/src/examples/custom-object-datatype/index.ts)
> and
> [simple-text-datatype-module.tsx](https://github.com/pimcore/studio-example-bundle/blob/main/assets/js/src/examples/custom-object-datatype/modules/simple-text-datatype-module.tsx)
> in the example bundle.

---

## Filter Type Mapping

The `dynamicTypeFieldFilterType` property on the object data dynamic
type (Step 6) controls both the filter UI in the grid sidebar and
which backend filter processes the query. Each field filter class
defines:

- A UI component for the filter sidebar (text input, select
  dropdown, date picker, etc.)
- A `getFieldFilterType()` method that returns the backend filter
  type string (e.g. `'system.string'`)

When the user applies a filter, the frontend sends this type string
in the API request. The Studio Backend matches it to a registered
search index filter that executes the query against the Generic Data
Index.

**Built-in filter types:**

| `dynamicTypeFieldFilterType` | Filter Type | UI | Backend Filter |
|---|---|---|---|
| `FieldFilter/String` | `system.string` | text input | wildcard search |
| `FieldFilter/Select` | `system.select` | select dropdown | exact match |
| `FieldFilter/Number` | `system.number` | number input | range query |
| `FieldFilter/Boolean` | `system.boolean` | checkbox | boolean match |
| `FieldFilter/DateTime` | `system.datetime` | date-time picker | range query |
| `FieldFilter/Date` | `system.date` | date picker | range query |
| `FieldFilter/None` | *(default)* | — | filtering disabled |

Set `dynamicTypeFieldFilterType` using the DI container:

```typescript
readonly dynamicTypeFieldFilterType: any =
    container.get(serviceIds['DynamicTypes/FieldFilter/String'])
```

For standard datatypes, choosing the right built-in filter type from
the table above is sufficient — no custom backend filter is needed.

**When you need a custom filter:** If your datatype requires filter
logic not covered by the built-in types (e.g. multi-value matching,
composite field queries), register a custom `FilterInterface` with
the `pimcore.studio_backend.search_index.data_object.filter` tag.
See the
[data-quality-management-bundle](https://github.com/pimcore/data-quality-management-bundle)
`MarkFilter` for a reference implementation, and the
[Extending Search Index Filters](https://github.com/pimcore/studio-backend-bundle/blob/2025.x/doc/03_Extending/08_Extending_Filters/01_Extending_Search_Index_Filters.md)
documentation for details.

---

## Complete Example

The
[studio-example-bundle](https://github.com/pimcore/studio-example-bundle)
implements the complete `simpleText` custom datatype. See the
[Custom Object Datatype](https://github.com/pimcore/studio-ui-bundle/blob/2025.x/doc/04_Extending/02_Plugin_Development_Examples/15_Custom_Object_Datatype.md)
plugin development example for an overview.
