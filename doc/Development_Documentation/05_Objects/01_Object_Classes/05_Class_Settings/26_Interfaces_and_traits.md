# Using interfaces and traits
In some cases you need to implement interfaces and overwrite some provided functions.

##### Example
```
Extending the customer management framework by implementing Symfony's UserInterface and overwritting the `getRoles` method to provide a default role for each user or
use a different value as username
```

## Create the trait
Returns default roles and use `UID` as username
```php
<?php
# src/AppBundle/Traits/SymfonyRolesTrait.php

namespace AppBundle\Traits;

trait SymfonyRolesTrait
{
    /**
     * Returns the roles granted to the user.
     *
     *     public function getRoles()
     *     {
     *         return ['ROLE_USER'];
     *     }
     *
     * Alternatively, the roles might be stored on a ``roles`` property,
     * and populated in any number of different ways when the user object
     * is created.
     *
     * @return (Role|string)[] The user roles
     */
    public function getRoles(){
        return ['ROLE_USER'];
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string|null The salt
     */
    public function getSalt(){
        return 'yourSaltGoesHere';
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername(){
        return $this->getUID();
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials(){

    }
}
```

## Use it in the Customer DataObject
Navigate to the Settings *Settings* -> *Data Objects* -> *Classes* -> *Customer Management* -> *Customer*

Click on *General Settings* and paste your interface and trait path into `Implements interface(s)` and `Use (traits)`

![]() Add screenshot here

Save your changes
