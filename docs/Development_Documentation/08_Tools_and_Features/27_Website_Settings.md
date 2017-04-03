# Website Settings

The `Website Settings` give you the possibility to configure website-specific settings, which you can 
access in every controller and view.

Examples
* ReCAPTCHA public & private key
* Locale settings
* Google Maps API key
* Defaults
* ....

### Access the Settings
You can access the settings in every controller and view with `$this->config`. This variable contains 
a `Zend_Config` object containing your settings.

If you're not in a view or controller you can use `Pimcore\Tool\Frontend::getWebsiteConfig();` to 
retrieve the configuration.


### Example Configuration
![Website Setting Config](../img/website-settings.png)

### Example in a View
```php 
<script type="text/javascript" src="http://www.google.com/jsapi?autoload=%7B%22modules%22%3A%5B%7B%22name%22%3A%22maps%22%2C%22version%22%3A%222%22%7D%5D%7D&amp;key=<?= $this->config->googleMapsKey ?>"></script>
```

### Example in a Controller
```php
public function testAction () {
    $recaptchaKeyPublic = $this->config->recaptchaPublic;
}
```

### Manipulate the values in a Controller
If you want to change the value of a website setting from your PHP script, for example from a controller, you can use this code.
```php
public function testAction () {
    $somesetting = \Pimcore\Model\WebsiteSetting::getByName('somenumber');
    $currentnumber = $somesetting->getData();
    //Now do something with the data or set new data
    //Count up in this case
    $newnumber = $currentnumber + 1;
    $somesetting->setData($newnumber);
    $somesetting->save();
}
```
