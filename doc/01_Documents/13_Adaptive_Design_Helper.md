# Adaptive Design Helper

The DeviceDetector helper makes it easy to implement the adaptive design approach in Pimcore. 
 
### Using It Anywhere in Your Code

```php
use Pimcore\Tool\DeviceDetector;
use Symfony\Component\HttpFoundation\Response;

class TestController extends Action
{
    public function testAction(): Response
    {
        $device = DeviceDetector::getInstance();
        $device->getDevice(); // returns "phone", "tablet" or "desktop"
 
        if($device->isDesktop() || $device->isTablet()) {
            // do something
        }
        
        // ...
    }
}
```

### Force a Device Type
Sometimes it's necessary to force a device type. A typical use case is a "Back to Desktop Version" 
or vice versa link. 

To do so, just add the parameter `forceDeviceType` to your request: 

```
/your/link?forceDeviceType=desktop
/another/link?forceDeviceType=tablet
/a/mobile/link?forceDeviceType=phone
```

This will set the device to the specified value and Pimcore will remember this setting using a 
cookie (name: `forceDeviceType`) till the browser session ends. 
 
 
### Caching
The Pimcore output-cache is aware of this feature and just works as expected. 

If you're using a caching proxy like Varnish you have to take the value of the cookie 
`forceDeviceType` into the hash calculation, otherwise there's just one hash for different contents 
of an URL (phone, tablet, desktop).
