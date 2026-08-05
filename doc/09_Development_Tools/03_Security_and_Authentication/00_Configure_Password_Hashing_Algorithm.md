---
title: Password Hashing
description: Configure the password hashing algorithm for Pimcore users and objects.
---

# Configure Password Hashing Algorithm

Pimcore uses PHP's default password hashing algorithm, which currently equals `BCrypt` with a cost of `10`
(see [`PASSWORD_DEFAULT`](https://www.php.net/manual/en/password.constants.php#constant.password-default)).
The algorithm is configurable
(see [possible algorithms and their options](https://www.php.net/manual/en/password.constants.php)):

```yaml
pimcore:
    security:
        password:
            algorithm: !php/const PASSWORD_BCRYPT
            options:
                cost: 13
```

This configuration applies to Pimcore Studio users and
[fields of type `Password` in custom Pimcore Objects](./01_Authenticate_Pimcore_Objects.md).
