# Custom Grid Columns

> **Working example:** The
> [studio-example-bundle](https://github.com/pimcore/studio-example-bundle)
> contains a complete progress bar custom grid column example
> covering all steps below.

Adding a custom grid column to Pimcore Studio requires registration
across two layers:
- **Studio Backend** (column definition, resolver, collector)
- **Studio UI** (frontend cell type — only needed for custom
  frontend types)

The `getFrontendType()` return value from the backend column
definition is the key that ties the two layers together — it must
match the `id` property of the frontend
`DynamicTypeGridCellAbstract` subclass.

---

## When Is the UI Layer Needed?

| Frontend Type | UI Work Required? |
|---|---|
| `element_dropzone` | No |
| `input` | No |
| `id` | No |
| `textarea` | No |
| `select` | No |
| `multiselect` | No |
| `checkbox` | No |
| `datetime` | No |
| `image` | No |
| `asset-link` | No |
| `object-link` | No |
| `asset-preview` | No |
| `boolean` | No |
| Any custom string (e.g. `progress-bar`) | **Yes** — create a `DynamicTypeGridCellAbstract` subclass |

If your column uses a built-in frontend type, you only need the
backend layer. The Studio UI already knows how to render those
cells. See the
[Built-in Frontend Types](https://github.com/pimcore/studio-backend-bundle/blob/1.x/doc/03_Extending/02_Extending_Grid_with_Custom_Columns.md#built-in-frontend-types)
table in the Studio Backend documentation for the full
`FrontendType` enum reference.

---

## Backend

The backend layer has three components, each with its own service
tag:

| Component | Interface | Tag |
|---|---|---|
| Column Definition | `ColumnDefinitionInterface` | `pimcore.studio_backend.grid_column_definition` |
| Column Resolver | `ColumnResolverInterface` | `pimcore.studio_backend.grid_column_resolver` |
| Column Collector | `ColumnCollectorInterface` | `pimcore.studio_backend.grid_column_collector` |

### Column Definition

Declares the column type, capabilities (`isSortable()`,
`isFilterable()`, `isExportable()`), and the `getFrontendType()`
value that determines cell rendering. See
[Column Definition](https://github.com/pimcore/studio-backend-bundle/blob/1.x/doc/03_Extending/02_Extending_Grid_with_Custom_Columns.md#column-definition)
for the full interface reference and example.

### Column Resolver

Fetches the column value for a given element. Implement
`CoreElementColumnResolverInterface` (for `ElementInterface`) or
`StudioElementColumnResolverInterface` (for `StudioElementInterface`
from GDI). Both can be implemented; the core interface has higher
priority. See
[Column Resolver](https://github.com/pimcore/studio-backend-bundle/blob/1.x/doc/03_Extending/02_Extending_Grid_with_Custom_Columns.md#column-resolver)
for the `ColumnData` constructor signature and example.

### Column Collector

Provides the list of available columns by returning
`ColumnConfiguration` objects. Receives all registered column
definitions keyed by type. See
[Column Collector](https://github.com/pimcore/studio-backend-bundle/blob/1.x/doc/03_Extending/02_Extending_Grid_with_Custom_Columns.md#column-collector)
for the `ColumnConfiguration` constructor signature and example.

### Service Registration

```yaml
services:
    App\Grid\Column\Definition\ProgressBarDefinition:
        tags:
            - { name: pimcore.studio_backend.grid_column_definition }

    App\Grid\Column\Resolver\ProgressBarResolver:
        tags:
            - { name: pimcore.studio_backend.grid_column_resolver }

    App\Grid\Column\Collector\ProgressBarCollector:
        tags:
            - { name: pimcore.studio_backend.grid_column_collector }
```

For full details and code examples, see
[Extending Grid with Custom Columns](https://github.com/pimcore/studio-backend-bundle/blob/1.x/doc/03_Extending/02_Extending_Grid_with_Custom_Columns.md)
in the Studio Backend documentation.

---

## UI (Custom Frontend Types Only)

If your `getFrontendType()` returns a custom string, you must
register a matching grid cell dynamic type in the Studio UI.

### Step 1 — Grid Cell Dynamic Type

Create a class extending `DynamicTypeGridCellAbstract` (from
`@pimcore/studio-ui-bundle/modules/element`).

Key properties and methods:
- `id` — must match the `getFrontendType()` return value from the
  backend definition
- `getGridCellComponent()` — returns the React component that
  renders the cell

### Step 2 — Module

Create a module that registers the dynamic type in the
`DynamicTypeGridCellRegistry`:

```typescript
const registry = container.get<DynamicTypeGridCellRegistry>(
    serviceIds['DynamicTypes/GridCellRegistry']
)
registry.registerDynamicType(container.get(MY_GRID_CELL_SERVICE_ID))
```

### Step 3 — Plugin

In your plugin's `onInit`, bind the dynamic type class as a
singleton. In `onStartup`, register the module.

### Key Mapping

The connection between backend and frontend is:

```
Backend:  getFrontendType() → 'progress-bar'
                                    ↕
Frontend: DynamicTypeGridCellAbstract.id = 'progress-bar'
```

These strings must match exactly.

---

## Complete Example

The
[studio-example-bundle](https://github.com/pimcore/studio-example-bundle)
implements the complete progress bar custom grid column. See the
[Custom Grid Column](https://github.com/pimcore/studio-ui-bundle/blob/1.x/doc/04_Extending/02_Plugin_Development_Examples/18_Custom_Grid_Column.md)
plugin development example for an overview of the frontend files.
