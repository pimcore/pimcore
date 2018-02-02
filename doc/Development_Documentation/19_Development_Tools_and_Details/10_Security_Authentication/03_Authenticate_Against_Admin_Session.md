# Authenticate against Admin Session

If you want to authenticate against an existing admin session, you can use the provided `pimcore_admin_pre_auth` authenticator.
The authenticator does not handle authentication, but just returns a pre authenticated token if there's an existing admin
session. To use the authenticator, just enable it on your firewall configuration:

```yaml
security:
    firewalls:
        demo_cms_fw:
            pimcore_admin_pre_auth: ~
```
