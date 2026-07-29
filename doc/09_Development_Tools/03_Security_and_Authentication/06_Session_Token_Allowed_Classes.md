# Admin Session Token Allowed Classes

When Pimcore restores a backend user from the session, it deserializes the security token stored under the
`_security_pimcore_admin` session key. To prevent PHP object-injection attacks (an attacker who can write to the
session store planting a serialized gadget chain), the deserialization is restricted to a fixed set of expected
classes via PHP's [`allowed_classes`](https://www.php.net/manual/en/function.unserialize.php) option — any other
class is reduced to `__PHP_Incomplete_Class` and its magic methods (`__wakeup`/`__destruct`/…) are never executed.

The built-in allowlist covers the standard token graph:

- `Pimcore\Security\User\User` and `Pimcore\Model\User`
- `Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken`
- `Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken`
- `Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken` (when `scheb/2fa-bundle` is installed)
- `Pimcore\Bundle\AdminBundle\Security\Authentication\Token\TwoFactorRequiredToken` — the classic
  `admin-ui-classic-bundle` `/admin/login` firewall (Pimcore < 2026.1 / LTS) stores this while 2FA is pending

## Extending the Allowlist

If you use a **custom authenticator** that stores a different token class in the session, register the additional
class(es); otherwise the session token will be rejected and the user silently logged out:

```yaml
# config/packages/pimcore.yaml
pimcore:
    security:
        session_token_allowed_classes:
            - App\Security\Token\MyCustomToken
```

The classes listed here are **merged** with Pimcore's built-in defaults, so you do not need to repeat the core
classes. This mirrors the `tmp_store.unserialize_allowed_classes` mechanism used for the Tmp Store.
