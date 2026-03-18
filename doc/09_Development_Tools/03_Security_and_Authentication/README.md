---
title: Security and Authentication
description: Symfony Security integration for frontend authentication in Pimcore.
---

# Security and Authentication

Pimcore integrates with the [Symfony Security Component](https://symfony.com/doc/current/security.html)
for authentication and authorization.
Pimcore Studio uses its own security firewall; configure separate firewalls
for any frontend authentication requirements.

## Login Example

The [demo-enterprise](https://github.com/pimcore/demo-enterprise) repository (branch: `2026.x`)
provides a full login implementation with form-based authentication, CSRF protection,
remember-me support, CMF `CustomerObjectUserProvider`, and e-commerce cart migration on login.

Key files:

- [`config/packages/security.yaml`](https://github.com/pimcore/demo-enterprise/blob/2026.x/config/packages/security.yaml)
  - Firewalls, user providers, access control rules
- [`src/Controller/AccountController.php`](https://github.com/pimcore/demo-enterprise/blob/2026.x/src/Controller/AccountController.php)
  - Login/logout controller actions
- [`src/Form/LoginFormType.php`](https://github.com/pimcore/demo-enterprise/blob/2026.x/src/Form/LoginFormType.php)
  - Login form definition
- [`src/EventListener/AuthenticationLoginListener.php`](https://github.com/pimcore/demo-enterprise/blob/2026.x/src/EventListener/AuthenticationLoginListener.php)
  - Post-login hooks (e.g. cart migration)

:::info

The `demo-enterprise` repository is private and requires an enterprise license.
The [simplified authentication guide](./01_Authenticate_Pimcore_Objects.md) below
covers the same concepts with a publicly accessible, minimal example.

:::

## Simplified Guide

For basic setups without CMF or e-commerce, follow the step-by-step guide at
[Authenticate Against Pimcore Objects](./01_Authenticate_Pimcore_Objects.md).
It covers user providers, password hashing with `PasswordFieldHasher`,
and firewall configuration using Pimcore data objects.

## Further Reading

- [Configure Password Hashing](./00_Configure_Password_Hashing_Algorithm.md)
- [Symfony Security Component documentation](https://symfony.com/doc/current/security.html)
