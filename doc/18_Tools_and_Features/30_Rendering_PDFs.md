# Rendering PDFs

Instead of directly returning the HTML code of your website you could also return a PDF version. 
You can use the PimcoreWebToPrintBundle functionality to accomplish this.

Please make sure that you have set up and installed the PimcoreWebToPrintBundle correctly ("Settings" -> "Web2Print Settings").

You need to enable and install the PimcoreWebToPrintBundle via the bundles.php, and then you 
just have to provide the correct settings (Tool -> PDFreactor / Chromium / Gotenberg) and the corresponding settings.

In your controller you just have to return the PDF instead of the HTML. 

## Simple example

```php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BlogController extends FrontendController
{
    public function indexAction(Request $request): Response
    {
        //your custom code....

        //return the pdf
        $html = $this->renderView(':Blog:index.html.php', [
            'document' => $this->document,
            'editmode' => $this->editmode,
        ]);
        return new Response(
            \Pimcore\Bundle\WebToPrintBundle\Processor::getInstance()->getPdfFromString($html),
            200,
            array(
                'Content-Type' => 'application/pdf',
            )
        );
    }
```
## Advanced example

```php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BlogController extends FrontendController
{
    public function indexAction(Request $request): Response
    {
        //your custom code....

        //return the pdf
        $params = [
              'document' => $this->document,
              'editmode' => $this->editmode,
          ];
        $params['testPlaceholder'] = ' :-)';
        $html = $this->renderView(':Blog:index.html.php', $params);

        $adapter = \Pimcore\Bundle\WebToPrintBundle\Processor::getInstance();
        //add custom settings if necessary
        if ($adapter instanceof \Pimcore\Bundle\WebToPrintBundle\Processor\PdfReactor) {
            //Config settings -> http://www.pdfreactor.com/product/doc/webservice/php.html#Configuration
            $params['adapterConfig'] = [
                'author' => 'Max Mustermann',
                'title' => 'Custom Title',
                'javaScriptMode' => 0,
                'addLinks' => true,
                'appendLog' => true,
                'enableDebugMode' => true
            ];
        } elseif ($adapter instanceof \Pimcore\Bundle\WebToPrintBundle\Processor\Gotenberg) {
            $params = Config::getWeb2PrintConfig();
            $params = json_decode($params['gotenbergSettings'], true) ?: [];
        } elseif ($adapter instanceof \Pimcore\Bundle\WebToPrintBundle\Processor\Chromium) {
            $params = Config::getWeb2PrintConfig();
            $params = json_decode($params['chromiumSettings'], true) ?: [];
        }

        return new Response(
            $adapter->getPdfFromString($html, $params),
            200,
            array(
                'Content-Type' => 'application/pdf',
                // 'Content-Disposition'   => 'attachment; filename="custom-pdf.pdf"' //direct download
            )
        );
    }
```
