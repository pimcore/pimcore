---
title: Permissions
description: Modify Pimcore element permissions based on the current workflow place.
---

# Permissions

Workflow places support permission overrides that modify element permissions
based on the current place.
See [Configuration Details](./05_Configuration_Details/README.md) for the full options reference.

Add multiple permission configurations based on conditions. The first entry
where the condition evaluates to true is used.

##### Configuration Examples

Pimcore admins are allowed to publish and delete the object but for all other users it will be suppressed:

```yaml

   places:
      closed:
         permissions:
           - condition: is_fully_authenticated() and is_granted('ROLE_PIMCORE_ADMIN')
             publish: true
             delete: true
           - publish: false
             delete: false
```

Pimcore admins are allowed to modify (save, publish,...) the object but for all other users the save
and delete button will be hidden. `modify` is a short hand for save, publish, unpublish, delete and rename:

```yaml

   places:
      closed:
         permissions:
           - condition: is_fully_authenticated() and is_granted('ROLE_PIMCORE_ADMIN')
             modify: true
           - modify: false
```

> If multiple places provide a valid permission configuration the one with the highest priority will be used.
> The priority is based on the workflow priority (the workflow with the higher priority setting will win). Within a
> single workflow the order within the places section of the configuration file will be used.

## Condition and guard expressions

The `condition` of a permission set is a [Symfony expression](https://symfony.com/doc/current/reference/formats/expression_language.html).
The **same expression language** is used everywhere Pimcore evaluates a workflow rule against the current
user and element:

- permission `condition`s (this page),
- transition and global action `guard`s (see the [Tutorial](../06_Workflow_Tutorial.md) and
  [Configuration Details](./05_Configuration_Details/README.md)),
- the [expression support strategy](./02_Support_Strategies.md),
- notification `condition`s (see [Notifications](./04_Notifications.md)).

Because the context is identical, the guidance below applies to all of them.

### Available variables

| Variable       | Description                                                                                                   |
|----------------|---------------------------------------------------------------------------------------------------------------|
| `subject`      | The element the workflow acts on (data object, document or asset). `object` is an alias for `subject`.         |
| `role_names`   | Array of the current user's role names, **expanded through the configured role hierarchy**. Includes `ROLE_PIMCORE_ADMIN` / `ROLE_PIMCORE_USER` and `ROLE_<ROLE NAME>` for each Pimcore role (see below). |
| `user`         | The authenticated user object, or the string `anonymous`/`null` when there is no user.                          |
| `token`        | The current security token.                                                                                    |

### Available functions

| Function                         | Description                                                                                                          |
|----------------------------------|--------------------------------------------------------------------------------------------------------------------|
| `is_granted('ROLE_X'[, subject])`| Checks the attribute through Symfony's authorization checker. Honors the role hierarchy **and any custom voters**, and can check element-level attributes such as `is_granted('VIEW', subject)`. |
| `is_fully_authenticated()`       | `true` only when the user is authenticated in the current session (not via a "remember me" cookie).                 |
| `is_authenticated()`             | `true` for any authenticated user, including "remember me".                                                          |
| `is_remember_me()`               | `true` when the user was authenticated from a "remember me" cookie.                                                  |
| `is_valid(subject[, groups])`    | `true` when the subject passes validation (requires the Validator component).                                        |

> **Note:** `has_role()` is **not** available. It was removed from Symfony in version 4.0. Use
> `is_granted('ROLE_X')` (or `'ROLE_X' in role_names`) instead.

### How Pimcore roles map to role names

The security token exposes roles as uppercase, `ROLE_`-prefixed strings:

- Users flagged as **admin** get `ROLE_PIMCORE_ADMIN`; all other backend users get `ROLE_PIMCORE_USER`.
- Every Pimcore role becomes `ROLE_<role name in uppercase>`, e.g. a role named "Editor" becomes `ROLE_EDITOR`
  and "Journalist" becomes `ROLE_JOURNALIST`.

### `is_granted('ROLE_X')` vs `'ROLE_X' in role_names` vs `has_role('ROLE_X')`

- **`is_granted('ROLE_X')`** &mdash; the recommended, idiomatic form. It runs the check through the Symfony
  authorization checker, so it respects the role hierarchy and also consults any custom security voters. It is the
  only form that can check non-role attributes or an element (`is_granted('VIEW', subject)`).
- **`'ROLE_X' in role_names`** &mdash; a plain array membership test. Because Pimcore expands `role_names`
  through the role hierarchy before evaluating the expression, it returns the same result as
  `is_granted('ROLE_X')` for a **plain role check**. It does not invoke voters and cannot check element-level
  attributes, but it is convenient for negation (`'ROLE_X' not in role_names`) and set logic
  (`'ROLE_A' in role_names or 'ROLE_B' in role_names`).
- **`has_role('ROLE_X')`** &mdash; obsolete, do not use. It is not a registered function and evaluating it throws.

For a straightforward role check, prefer `is_granted('ROLE_X')`. Reach for `role_names` when you need negation or
combine several roles with `and`/`or`.

### Is `is_fully_authenticated()` required?

No &mdash; the role check itself is what enforces access: an anonymous request has no roles, so
`is_granted('ROLE_X')` and `'ROLE_X' in role_names` already fail for it. Adding `is_fully_authenticated()` has two
effects:

1. It rejects users who are only authenticated via a "remember me" cookie, forcing a full/fresh login before the
   sensitive transition or permission applies.
2. It reads as an explicit, defensive intent.

In the Pimcore Studio backend users are always fully authenticated, so in that context the prefix rarely changes the
outcome. It is a recommended convention rather than a strict requirement.

### Checking for admins

There is no `is_admin()` function. Check the admin role explicitly with either
`is_granted('ROLE_PIMCORE_ADMIN')` or `'ROLE_PIMCORE_ADMIN' in role_names` &mdash; both are correct.

### Referencing other roles

To reference roles other than admin, use `ROLE_<role name in uppercase>`,
e.g. `ROLE_EDITOR` for a role named "Editor":

```yaml
   places:
      closed:
         permissions:
           - condition: is_fully_authenticated() and is_granted('ROLE_EDITOR')
             modify: true
           - modify: false
```
