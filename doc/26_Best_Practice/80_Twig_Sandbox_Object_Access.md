# Twig Sandbox Object Access (Blocklist / Allowlist)

Pimcore renders certain user-authored Twig templates - Email documents (subject &
body), and DataObject `Layout\Text` (Dynamic Text) components - inside a Twig
[sandbox](https://twig.symfony.com/doc/3.x/api.html#sandbox-extension). The sandbox
restricts which tags, filters and functions a template may use (see
[Email Framework](../19_Development_Tools_and_Details/25_Email_Framework/README.md#sandbox-restrictions))
and, independently, which **PHP objects** a template is allowed to call methods or
read properties on.

Object access is enforced by `Pimcore\Twig\Sandbox\SecurityPolicy` and is
configurable via `templating_engine.twig.sandbox_security_policy`.

## Two modes

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

## Configuration

```yaml
pimcore:
    templating_engine:
        twig:
            sandbox_security_policy:
                tags: ['set']
                filters: ['escape', 'trans', 'default']
                functions: ['path', 'asset']
                # Extend the built-in denylist (Dao/Connection/PDO/.../User). Only
                # consulted while allowed_classes is empty.
                blocked_classes: []
                # Non-empty => allowlist mode. Deactivates the denylist entirely.
                allowed_classes: []
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

## Notes

- `blocked_classes` and `allowed_classes` both accept any fully qualified class or
  interface name; matching uses `instanceof`, so subclasses/implementations are
  covered automatically.
- Classes that don't exist (e.g. an optional bundle that isn't installed) are
  skipped silently, matching the existing behavior of the built-in denylist.
- This only governs **object** access from within the sandbox. It is independent
  from the `tags` / `filters` / `functions` allowlists, which still apply as
  before.
- Changing these settings requires clearing the Symfony container cache
  (`bin/console cache:clear`) since the security policy is wired as a container
  parameter.
