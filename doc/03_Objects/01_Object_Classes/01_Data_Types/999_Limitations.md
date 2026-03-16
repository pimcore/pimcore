---
title: Limitations
description: "Nesting restrictions for data types like blocks, field collections, and localized fields."
---

## Limitations

### Nesting of Data Types
Data types cannot be arbitrarily nested within each other.
For example, it is not possible to place a block inside another block, or a localized field inside another localized field.
Additionally, data type containers such as blocks or field collections cannot be nested more than two levels deep.

For instance, the following structure is **not allowed**:

```text
-- Field Collection
    -- Block
        -- Localized Field
            -- Input field
```

However, the following structure **is allowed**:

```text
-- Field Collection
    -- Block
        -- Input field
```