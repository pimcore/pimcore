# Testing Symfony Services

For integration tests of symfony services in context of their configuration in container, there are multiple ways for 
retrieving them directly from the symfony container (regardless if there are public or private). 

The symfony default way is described [here](https://symfony.com/doc/current/testing.html#retrieving-services-in-the-test)
and can be used in context within Pimcore too. 

In combination with codeception, where is the [codeception symfony module](https://codeception.com/docs/modules/Symfony) 
that provides additional functionality as also grabbing services from 
[the container](https://codeception.com/docs/modules/Symfony#grabService). 

Currently, we are not using the [codeception symfony module](https://codeception.com/docs/modules/Symfony) though, 
to reduce test complexity and due to lack of compatibility with symfony 6.

To still have the grab service functionality available, just use the 
[`Pimcore\Tests\Support\Helper\Pimcore`](https://github.com/pimcore/pimcore/blob/11.x/tests/Support/Helper/Pimcore.php#L101) 
module and call `grabService` as below: 

```php
use Pimcore\Tests\Support\Helper\Pimcore;

/** @var Pimcore $pimcoreModule */
$pimcoreModule = $this->getModule('\\' . Pimcore::class);
$mailerService = $pimcoreModule->grabService('mailer');
```
