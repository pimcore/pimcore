# Rendering PDFs

Instead of directly returning the HTML code of your website you could also return a PDF version.
You can use the built in Web2Print functionality to accomplish this.

Please make sure that you have set up the web2print functionality correctly ("Settings" -> "Web2Print Settings").

You don't need to enable the Web2Print Documents in Pimcore, you
just have to provide the correct settings (Tool -> WkHtmlToPdf / PDFreactor / LibreOffice) and the corresponding settings.

In your controller you just have to return the PDF instead of the HTML.

## Simple example
```php
class BlogController extends FrontendController
{
    public function indexAction(Request $request)
    {
        //your custom code....

        //return the pdf
        $html = $this->renderView(':Blog:index.html.php', $this->view->getAllParameters());
        return new \Symfony\Component\HttpFoundation\Response(
            \Pimcore\Web2Print\Processor::getInstance()->getPdfFromString($html),
            200,
            array(
                'Content-Type' => 'application/pdf',
            )
        );
    }
```
## Advanced example

```php
class BlogController extends FrontendController
{
    public function indexAction(Request $request)
    {
        //your custom code....

        //return the pdf
            $params = $this->view->getAllParameters();
            $params['testPlaceholder'] = ' :-)';
            $html = $this->renderView(':Blog:index.html.php', $params);

            $adapter = \Pimcore\Web2Print\Processor::getInstance();
            //add custom settings if necessary
            if ($adapter instanceof \Pimcore\Web2Print\Processor\WkHtmlToPdf) {
                $params['adapterConfig'] = '-O landscape';
            } elseif($adapter instanceof \Pimcore\Web2Print\Processor\PdfReactor8) {
                //Config settings -> http://www.pdfreactor.com/product/doc/webservice/php.html#Configuration
                $params['adapterConfig'] = [
                    'author' => 'Max Mustermann',
                    'title' => 'Custom Title',
                    'javaScriptMode' => 0,
                    'addLinks' => true,
                    'appendLog' => true,
                    'enableDebugMode' => true
                ];
            }

            return new \Symfony\Component\HttpFoundation\Response(
                $adapter->getPdfFromString($html, $params),
                200,
                array(
                    'Content-Type' => 'application/pdf',
                    // 'Content-Disposition'   => 'attachment; filename="custom-pdf.pdf"' //direct download
                )
            );
    }
```
