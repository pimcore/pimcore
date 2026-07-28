# Twig Sandbox Object & Function Access (Blocklist / Allowlist)

Pimcore renders certain user-authored Twig templates - Email documents (subject &
body), and DataObject `Layout\Text` (Dynamic Text) components - inside a Twig
[sandbox](https://twig.symfony.com/doc/3.x/api.html#sandbox-extension). The sandbox
restricts which tags, filters and functions a template may use (see
[Email Framework](../05_Email_Framework/README.md#sandbox-restrictions))
and, independently, which **PHP objects** a template is allowed to call methods or
read properties on, and which `pimcore_*` **functions** are callable.

Both are enforced by `Pimcore\Twig\Sandbox\SecurityPolicy` and are configurable via
`templating_engine.twig.sandbox_security_policy`. Object access supports both a
denylist and an allowlist mode, described below; function access is denylist-only.
Every built-in denylist described in this document (`blocked_classes`,
`blocked_functions`, `hard_blocked_methods`) is defined as the *default value* of
its config option in `bundles/CoreBundle/config/pimcore/default.yaml`, not hardcoded
in `SecurityPolicy` - a site's own config for the same option is merged with (appended
to) that default, not substituted for it, so extending one of these options cannot
accidentally drop the shipped defaults.

## Object access: two modes

The policy supports two mutually exclusive modes:

- **Blocklist mode (default)** - every object is reachable *except* instances of a
  denylist of classes. Pimcore's `blocked_classes` default covers the database/
  infrastructure layer (`Pimcore\Model\Dao\AbstractDao`, `Doctrine\DBAL\Connection`,
  `PDO`, `PDOStatement`, Symfony's `ContainerInterface`, `Process`) and
  `Pimcore\Model\User` (whose getters expose the password hash and password
  recovery token). Set `blocked_classes` to add more classes to this denylist.
- **Allowlist mode** - as soon as `allowed_classes` contains at least one entry, the
  policy switches to allow only instances of the listed classes (and their
  subclasses). **The entire `blocked_classes` denylist is deactivated in this
  mode** - every object that is not an instance of an allowed class is denied,
  regardless of what `blocked_classes` contains.

A blocklist is inherently open-ended: it must enumerate every class whose data must
stay out of templates, and any class added to the codebase later (or reachable via
a getter that returns a different object) can accidentally slip through. An
allowlist inverts this: only the classes a site explicitly trusts for template
rendering are reachable, so newly introduced classes are denied by default. Prefer
allowlist mode wherever the set of objects passed into a sandboxed template
(`Mail::setParams()`, `Layout\Text` context) is small and known.

## Hard-blocked methods

`Pimcore\Model\Asset` is deliberately **not** on the `blocked_classes` denylist -
templates commonly need it for filename/thumbnail access. But its content-returning
methods (`getData`, `getStream`, `getLocalFile`, `getTemporaryFile`) would let a
template read the raw bytes of any asset, bypassing that asset's workspace ACL.
These methods - together with `User::getPassword`, `User::getPasswordRecoveryToken`
and `User::getTwoFactorAuthentication` (returns the MFA secret alongside the rest of
the 2FA config) - are hard-blocked via the `hard_blocked_methods` option: unlike
`blocked_classes`, this check is **not** bypassed by allowlist mode. Even a site
that explicitly allowlists `Asset` or `User` cannot make these specific methods
template-reachable again. `hard_blocked_methods` can be extended (a site can add
further FQCN => methods entries on top of the shipped default), but the shipped
entries themselves stay in effect - since config for this option is merged with the
default rather than replacing it - keeping a small number of secret/content-returning
getters closed off no matter how the rest of the policy is configured.

## Function access

The `functions` option (see [Email Framework](../05_Email_Framework/README.md#sandbox-restrictions))
is an explicit allowlist: a function must be listed there to be callable from a
sandboxed template. Independently, any Twig function whose name starts with
`pimcore_` is additionally auto-allowed, except for the `blocked_functions` denylist.
By default, that denylist only contains `pimcore_user` (see
[Hard-blocked methods](#hard-blocked-methods) above for why `User` getters are
additionally hard-blocked at the object layer regardless).

All other `pimcore_*` functions - including the other id/path lookup functions,
`pimcore_asset`, `pimcore_asset_by_path`, `pimcore_document`,
`pimcore_document_by_path`, `pimcore_document_wrap_hardlink`, `pimcore_object`,
`pimcore_object_by_path`, `pimcore_object_classificationstore_group`,
`pimcore_object_brick_definition_key`, `pimcore_site`, `pimcore_site_by_root_id`,
`pimcore_site_by_domain`, `pimcore_site_current` - are auto-allowed by default, so
existing template rendering is unaffected. Each of these hands back a live model
instance looked up by an arbitrary id/path, which a sandboxed template can then call
further getters on to reach data outside its intended scope. **For a high-security
setup, add these to `blocked_functions`** (they're listed, commented out, in
`default.yaml` - uncomment or copy them into a site's own config) to restore the
stricter posture:

```yaml
pimcore:
    templating_engine:
        twig:
            sandbox_security_policy:
                blocked_functions:
                    - pimcore_asset
                    - pimcore_asset_by_path
                    - pimcore_document
                    - pimcore_document_by_path
                    - pimcore_document_wrap_hardlink
                    - pimcore_object
                    - pimcore_object_by_path
                    - pimcore_object_classificationstore_group
                    - pimcore_object_brick_definition_key
                    - pimcore_site
                    - pimcore_site_by_root_id
                    - pimcore_site_by_domain
                    - pimcore_site_current
```

## Configuration

`blocked_classes`, `blocked_functions` and `hard_blocked_methods` already default to
Pimcore's built-in denylists in `default.yaml` (shown below, abbreviated) - a site's
own `config/packages/pimcore.yaml` only needs to list what it wants to *add* on top of
those defaults:

```yaml
pimcore:
    templating_engine:
        twig:
            sandbox_security_policy:
                tags: ['set']
                filters: ['escape', 'trans', 'default']
                functions: ['path', 'asset']
                # Defaults to the built-in class denylist (Dao/Connection/PDO/.../User) -
                # a site's own config is appended to it. Only consulted while
                # allowed_classes is empty.
                blocked_classes:
                    - Pimcore\Model\Dao\AbstractDao
                    - Doctrine\DBAL\Connection
                    - PDO
                    - PDOStatement
                    - Symfony\Component\DependencyInjection\ContainerInterface
                    - Symfony\Component\Process\Process
                    - Pimcore\Model\User
                # Non-empty => object allowlist mode. Deactivates the class denylist entirely.
                allowed_classes: []
                # Defaults to the built-in pimcore_* function denylist - a site's own
                # config is appended to it. Only pimcore_user is blocked out of the
                # box; the id/path lookup functions below are shipped commented out -
                # uncomment them (or add the equivalent to a site's own config) for a
                # high-security setup.
                blocked_functions:
                    # - pimcore_asset
                    # - pimcore_asset_by_path
                    # - pimcore_document
                    # - pimcore_document_by_path
                    # - pimcore_document_wrap_hardlink
                    # - pimcore_object
                    # - pimcore_object_by_path
                    # - pimcore_object_classificationstore_group
                    # - pimcore_object_brick_definition_key
                    # - pimcore_site
                    # - pimcore_site_by_root_id
                    # - pimcore_site_by_domain
                    # - pimcore_site_current
                    - pimcore_user
                # FQCN => method names that are never callable, regardless of
                # blocked_classes/allowed_classes. Defaults to a small set of
                # secret/content-returning getters - a site's own config is merged
                # with (not substituted for) this default.
                hard_blocked_methods:
                    Pimcore\Model\User:
                        - getPassword
                        - getPasswordRecoveryToken
                        - getTwoFactorAuthentication
                    Pimcore\Model\Asset:
                        - getData
                        - getStream
                        - getLocalFile
                        - getTemporaryFile
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

With this configuration, `blocked_classes` is ignored entirely - any object that is
not a `Product` or `OrderConfirmationData` (or a subclass) raises
`Twig\Sandbox\SecurityNotAllowedMethodError` / `SecurityNotAllowedPropertyError` when
a template tries to call a method or read a property on it.

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

A site's own config for `blocked_classes` is merged with Pimcore's shipped default,
not substituted for it, so this results in the built-in denylist *plus*
`App\Service\SecretsProvider` - not just the single entry shown above.

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
- `blocked_functions` accepts exact Twig function names (as used in a template,
  e.g. `pimcore_user`) and is matched by string comparison.
- `hard_blocked_methods` keys are FQCNs, matched the same way as `blocked_classes`;
  each value is a list of method names, matched by string comparison.
- `blocked_classes`, `blocked_functions` and `hard_blocked_methods` are Symfony
  config array nodes, so a site's own value for any of them is merged with (appended
  to) Pimcore's default rather than replacing it - there is no config-only way to
  remove an entry from the shipped defaults.
- The object and function mechanisms are independent of each other, and both are
  independent from the `tags` / `filters` allowlists, which still apply as before.
- Changing these settings requires clearing the Symfony container cache
  (`bin/console cache:clear`) since the security policy is wired as a container
  parameter.
