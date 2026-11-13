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
           - condition: is_fully_authenticated() and 'ROLE_PIMCORE_ADMIN' in role_names
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
           - condition: is_fully_authenticated() and 'ROLE_PIMCORE_ADMIN' in role_names
             modify: true
           - modify: false
```

> If multiple places provide a valid permission configuration the one with the highest priority will be used.
> The priority is based on the workflow priority (the workflow with the higher priority setting will win). Within a
> single workflow the order within the places section of the configuration file will be used.

## Condition and guard expressions

The `condition` of a permission set is a [Symfony expression](https://symfony.com/doc/current/reference/formats/expression_language.html).
Pimcore evaluates expressions in two different contexts that offer slightly different variables:

- **Pimcore expressions** (evaluated by `Pimcore\Workflow\ExpressionService`): permission `condition`s (this page),
  global action `guard`s, notification `condition`s (see [Notifications](./04_Notifications.md)) and the
  [expression support strategy](./02_Support_Strategies.md).
- **Transition guards** (`transitions.<name>.guard`, see the [Tutorial](../06_Workflow_Tutorial.md) and
  [Configuration Details](./05_Configuration_Details/README.md)): evaluated by Symfony's own workflow `GuardListener`.

### Available variables

| Variable       | Pimcore expressions                                                                                           | Transition guards                         |
|----------------|---------------------------------------------------------------------------------------------------------------|-------------------------------------------|
| `subject`      | The element the workflow acts on (data object, document or asset).                                            | Same.                                     |
| `object`       | Alias for `subject`.                                                                                          | **Not available**, use `subject`.         |
| `role_names`   | Array of the current user's role names, **expanded through the configured role hierarchy**. Includes `ROLE_PIMCORE_ADMIN` / `ROLE_PIMCORE_USER` and `ROLE_<ROLE NAME>` for each Pimcore role (see below). | Same.                                     |
| `user`         | The authenticated user object, or the string `anonymous` when there is no user.                               | The user object, or `null` when there is no user. |
| `token`        | The current security token (a null token when nobody is logged in).                                           | The current security token, or `null` when nobody is logged in. |

> The transition guard column follows Symfony's `GuardListener`; it is not defined in Pimcore code. If you rely on a
> specific variable in a guard, check it against the Symfony version your project uses.

### Available functions

| Function                         | Description                                                                                                          |
|----------------------------------|--------------------------------------------------------------------------------------------------------------------|
| `is_granted('ROLE_X'[, subject])`| Checks the attribute through Symfony's authorization checker. Honors the role hierarchy **and any custom voters**. Element-level attributes (for example `is_granted('VIEW', subject)`) only work if your project registers a voter for them; Pimcore core ships no such voter, so without one the check is always false. |
| `is_fully_authenticated()`       | `true` only when the user is fully authenticated (not only via a "remember me" cookie).                 |
| `is_authenticated()`             | `true` for any authenticated user, including "remember me".                                                          |
| `is_remember_me()`               | `true` when the user was authenticated from a "remember me" cookie.                                                  |
| `is_valid(subject[, groups])`    | `true` when the subject passes validation (requires the Validator component).                                        |

> **Note:** `has_role()` is **not** available. It was deprecated in Symfony 4.2 and removed in 5.0. Use
> `is_granted('ROLE_X')` (or `'ROLE_X' in role_names`) instead.

### How Pimcore roles map to role names

The security token exposes roles as uppercase, `ROLE_`-prefixed strings:

- Users flagged as **admin** get `ROLE_PIMCORE_ADMIN`; all other backend users get `ROLE_PIMCORE_USER`.
- Every Pimcore role becomes `ROLE_<role name in uppercase>`, e.g. a role named "Editor" becomes `ROLE_EDITOR`
  and "Journalist" becomes `ROLE_JOURNALIST`. Only the case is changed, spaces are kept: a role named
  "Product Manager" becomes `ROLE_PRODUCT MANAGER`.
- This mapping applies to Pimcore backend users (`Pimcore\Security\User\User`). Other user types have the roles
  their own user provider returns.

### `is_granted('ROLE_X')` vs `'ROLE_X' in role_names` vs `has_role('ROLE_X')`

- **`is_granted('ROLE_X')`** &mdash; the idiomatic form. It runs the check through the Symfony
  authorization checker, so it respects the role hierarchy and also consults any custom security voters. It is the
  only form that can check non-role attributes or an element, provided a voter handles them.
- **`'ROLE_X' in role_names`** &mdash; a plain array membership test. Because Pimcore expands `role_names`
  through the role hierarchy before evaluating the expression, it returns the same result as
  `is_granted('ROLE_X')` for a **plain role check** with the default voters (a custom voter that votes on
  `ROLE_*` attributes can make them differ). It does not invoke voters and cannot check element-level
  attributes, but it is convenient for negation (`'ROLE_X' not in role_names`) and set logic
  (`'ROLE_A' in role_names or 'ROLE_B' in role_names`).
- **`has_role('ROLE_X')`** &mdash; obsolete, do not use. It is not a registered function and evaluating it throws.

Both forms are correct for a role check. `is_granted('ROLE_X')` is the idiomatic one and also consults custom voters;
`'ROLE_X' in role_names` is what the examples on this page use, because it reads well with negation and with
`and`/`or` combinations of several roles.

### Is `is_fully_authenticated()` required?

No &mdash; the role check itself is what enforces access: an anonymous request has no roles, so
`is_granted('ROLE_X')` and `'ROLE_X' in role_names` already fail for it. Adding `is_fully_authenticated()` has two
effects:

1. It rejects users who are only authenticated via a "remember me" cookie, forcing a full/fresh login before the
   sensitive transition or permission applies.
2. It reads as an explicit, defensive intent.

Backend users logging in through the Pimcore Studio are normally fully authenticated, so in that context the prefix
rarely changes the outcome (a transition triggered from the CLI or a worker has no logged-in user at all). It is a recommended convention rather than a strict requirement.

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
           - condition: is_fully_authenticated() and 'ROLE_EDITOR' in role_names
             modify: true
           - modify: false
```
