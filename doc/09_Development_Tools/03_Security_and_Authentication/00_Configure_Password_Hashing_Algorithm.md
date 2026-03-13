# Configure Password Hashing Algorithm

Pimcore uses PHP's default password hashing algorithm by default, which currently equals to `BCrypt` with a cost of `10`
(see [`PASSWORD_DEFAULT`](https://www.php.net/manual/en/password.constants.php#constant.password-default)), but the algorithm 
can also be configured (see here for [possible algorithms and their options](https://www.php.net/manual/en/password.constants.php)),
for example:

 ```yaml
pimcore:
    security:
        password:
            algorithm: !php/const PASSWORD_BCRYPT
            options:
                cost: 13
  ```

This config will be used for Pimcore's backend users and [fields of type `Password` in custom Pimcore Objects](./01_Authenticate_Pimcore_Objects.md).
