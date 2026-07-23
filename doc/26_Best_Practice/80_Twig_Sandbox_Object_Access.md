# Twig Sandbox Object & Function Access (Blocklist / Allowlist)

Pimcore renders certain user-authored Twig templates - Email documents (subject &
body), and DataObject `Layout\Text` (Dynamic Text) components - inside a Twig
[sandbox](https://twig.symfony.com/doc/3.x/api.html#sandbox-extension). The sandbox
restricts which tags, filters and functions a template may use (see
[Email Framework](../19_Development_Tools_and_Details/25_Email_Framework/README.md#sandbox-restrictions))
and, independently, which **PHP objects** a template is allowed to call methods or
read properties on, and which `pimcore_*` **functions** are callable.

Both are enforced by `Pimcore\Twig\Sandbox\SecurityPolicy` and are configurable via
`templating_engine.twig.sandbox_security_policy`. They follow the same blocklist/
allowlist shape, described once below and then applied to objects and functions
separately.

## Object access: two modes

The policy supports two mutually exclusive modes:

- **Blocklist mode (default)** - every object is reachable *except* instances of a
  denylist of classes. Pimcore ships a built-in denylist covering the database/
  infrastructure layer (`Pimcore\Model\Dao\AbstractDao`, `Doctrine\DBAL\Connection`,
  `PDO`, `PDOStatement`, Symfony's `ContainerInterface`, `Process`) and
  `Pimcore\Model\User` (whose getters expose the password hash and password
  recovery token). Use `blocked_classes` to add more classes to this denylist.
- **Allowlist mode** - as soon as `allowed_classes` contains at least one entry, the
  policy switches to allow only instances of the listed classes (and their
  subclasses). **The entire denylist (built-in + `blocked_classes`) is deactivated
  in this mode** - every object that is not an instance of an allowed class is
  denied, regardless of what `blocked_classes` contains.

A blocklist is inherently open-ended: it must enumerate every class whose data must
stay out of templates, and any class added to the codebase later (or reachable via
a getter that returns a different object) can accidentally slip through. An
allowlist inverts this: only the classes a site explicitly trusts for template
rendering are reachable, so newly introduced classes are denied by default. Prefer
allowlist mode wherever the set of objects passed into a sandboxed template
(`Mail::setParams()`, `Layout\Text` context) is small and known.

## Always-blocked methods

`Pimcore\Model\Asset` is deliberately **not** on the built-in denylist - templates
commonly need it for filename/thumbnail access. But its content-returning methods
(`getData`, `getStream`, `getLocalFile`, `getTemporaryFile`) would let a template
read the raw bytes of any asset, bypassing that asset's workspace ACL. These
methods - together with `User::getPassword` / `User::getPasswordRecoveryToken` -
are hard-blocked unconditionally: unlike the denylist, this check is **not**
bypassed by allowlist mode. Even a site that explicitly allowlists `Asset` or
`User` cannot make these specific methods template-reachable again. This set is
not configurable; it exists to keep a small number of secret/content-returning
getters closed off no matter how the rest of the policy is configured.

## Function access: two modes

The `functions` option (see [Email Framework](../19_Development_Tools_and_Details/25_Email_Framework/README.md#sandbox-restrictions))
is an explicit allowlist: a function must be listed there to be callable from a
sandboxed template - this always applies, in either mode below, and is unaffected by
them. Independently, any Twig function whose name starts with `pimcore_` is
additionally auto-allowed, following the exact same two-mode shape as object access:

- **Blocklist mode (default)** - every `pimcore_*` function is auto-allowed *except*
  a denylist of functions. Pimcore ships a built-in denylist of functions that look
  up and return a live model instance by id/path, whose getters can expose data
  outside the sandboxed template's intended scope (e.g. `pimcore_user(1)`,
  `pimcore_asset(id)` - see [Always-blocked methods](#always-blocked-methods) above
  for why those two are additionally hard-blocked at the object layer): `pimcore_asset`,
  `pimcore_asset_by_path`, `pimcore_document`, `pimcore_document_by_path`,
  `pimcore_document_wrap_hardlink`, `pimcore_object`, `pimcore_object_by_path`,
  `pimcore_object_classificationstore_group`, `pimcore_object_brick_definition_key`,
  `pimcore_site`, `pimcore_site_by_root_id`, `pimcore_site_by_domain`,
  `pimcore_site_current`, `pimcore_user`. Use `blocked_functions` to add more
  functions to this denylist.
- **Allowlist mode** - as soon as `allowed_functions` contains at least one entry,
  the `pimcore_*` prefix rule switches to allow only the listed functions.
  **The entire denylist (built-in + `blocked_functions`) is deactivated in this
  mode** - every `pimcore_*` function not in `allowed_functions` (and not in the
  general `functions` allowlist) is denied.

All other `pimcore_*` functions (rendering/helper functions such as `pimcore_dump`,
`pimcore_url`, `pimcore_head_link`, the editable functions, ...) remain auto-allowed
in blocklist mode, so existing template rendering is unaffected unless a site
switches to allowlist mode.

## Configuration

```yaml
pimcore:
    templating_engine:
        twig:
            sandbox_security_policy:
                tags: ['set']
                filters: ['escape', 'trans', 'default']
                functions: ['path', 'asset']
                # Extend the built-in class denylist (Dao/Connection/PDO/.../User).
                # Only consulted while allowed_classes is empty.
                blocked_classes: []
                # Non-empty => object allowlist mode. Deactivates the class denylist entirely.
                allowed_classes: []
                # Extend the built-in pimcore_* function denylist. Only consulted
                # while allowed_functions is empty.
                blocked_functions: []
                # Non-empty => pimcore_* function allowlist mode. Deactivates that denylist entirely.
                allowed_functions: []
```

### Example: allowlist mode

If a site only ever passes a handful of DTOs/value objects into sandboxed
templates (e.g. an order confirmation email that only needs `firstName`,
`lastName` and a `product` DataObject), lock the sandbox down to exactly those
classes:

```yaml
pimcore:
    templating_engine:
        twig:
            sandbox_security_policy:
                functions: ['path', 'asset']
                allowed_classes:
                    - 'Pimcore\Model\DataObject\Product'
                    - 'App\Mail\OrderConfirmationData'
```

With this configuration, `blocked_classes` (and the built-in denylist) is ignored -
any object that is not a `Product` or `OrderConfirmationData` (or a subclass)
raises `Twig\Sandbox\SecurityNotAllowedMethodError` /
`SecurityNotAllowedPropertyError` when a template tries to call a method or read a
property on it.

### Example: extending the denylist

If switching to a full allowlist is not feasible (e.g. many different DataObject
classes are legitimately passed into templates), extend the denylist instead to
explicitly deny further classes whose getters return sensitive data - e.g. a
custom service locator or a DTO wrapping credentials:

```yaml
pimcore:
    templating_engine:
        twig:
            sandbox_security_policy:
                blocked_classes:
                    - 'App\Service\SecretsProvider'
```

### Example: pimcore_* function allowlist mode

If a site's sandboxed templates only ever need one or two of the id/path-lookup
functions, lock the `pimcore_*` prefix rule down to exactly those instead of relying
on the (larger) built-in denylist:

```yaml
pimcore:
    templating_engine:
        twig:
            sandbox_security_policy:
                allowed_functions:
                    - 'pimcore_user'
```

With this configuration, `blocked_functions` (and the built-in function denylist) is
ignored - every `pimcore_*` function other than `pimcore_user` now raises
`Twig\Sandbox\SecurityNotAllowedFunctionError`, including ones that were previously
auto-allowed (e.g. `pimcore_dump`). Add them to `functions` if still needed.

### Example: extending the function denylist

```yaml
pimcore:
    templating_engine:
        twig:
            sandbox_security_policy:
                blocked_functions:
                    - 'pimcore_element_tags'
```

## Notes

- `blocked_classes`/`allowed_classes` accept any fully qualified class or interface
  name; matching uses `instanceof`, so subclasses/implementations are covered
  automatically, and a configured class that doesn't exist (e.g. an optional bundle
  that isn't installed) is skipped silently.
- `blocked_functions`/`allowed_functions` accept exact Twig function names (as used
  in a template, e.g. `pimcore_user`) and are matched by string comparison.
- The object and function mechanisms are independent - switching one to allowlist
  mode does not affect the other. Both are independent from the `tags` / `filters`
  allowlists, which still apply as before.
- Changing these settings requires clearing the Symfony container cache
  (`bin/console cache:clear`) since the security policy is wired as a container
  parameter.
