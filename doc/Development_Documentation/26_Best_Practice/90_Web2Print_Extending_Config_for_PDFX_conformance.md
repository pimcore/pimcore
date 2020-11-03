# Web2Print - Extending PDF Creation Config for PDF/X Conformance

Sometimes it is necessary to add additional configuration options to the PDF processing configuration in the Pimcore backend UI - 
for example when creating PDF/X conform PDFs with PDF Reactor.

![Config Options](../img/configs.jpg)

But also for other use cases, it might be necessary to hook into the PDF creation process and modify the configuration before
creating the file.  

**Solution**

To do so, Pimcore provides two events:
- [`PRINT_MODIFY_PROCESSING_OPTIONS`](https://github.com/pimcore/pimcore/blob/master/lib/Event/DocumentEvents.php#L126):
  Event to modify the processing options displayed in the Pimcore backend UI. For example add additional options like `AppendLog` and `My Additional ...` 
  in the screenshot above. 
  
- [`PRINT_MODIFY_PROCESSING_CONFIG`](https://github.com/pimcore/pimcore/blob/master/lib/Event/DocumentEvents.php#L148)
  Event to modify the configuration for the PDF processor when the PDF gets created. For example read values for additional
  options and apply these values to the configuration of the PDF processor accordingly or do some other stuff. 
  

##### Example for adding Additional Config

Services in Container:
```yml
 app.event_listener.test:
        class: AppBundle\EventListener\PDFConfigListener
        tags:
            - { name: kernel.event_listener, event: pimcore.document.print.processor.modifyProcessingOptions, method: modifyProcessingOptions }
            - { name: kernel.event_listener, event: pimcore.document.print.processor.modifyConfig, method: modifyConfig }
```

Implementation of Listener
```php
<?php 
namespace AppBundle\EventListener;

class PDFConfigListener
{
    public function modifyProcessingOptions(\Pimcore\Event\Model\PrintConfigEvent $event) {

        $arguments = $event->getArguments();
        $options = $arguments['options'];

        $processor = $event->getProcessor();
        if($processor instanceof \Pimcore\Web2Print\Processor\PdfReactor8) {
            
            //add option to append log into generated PDF (pdf reactor functionality) 
            $options[] = ['name' => 'appendLog', 'type' => 'bool', 'default' => false];
        }

        $arguments['options'] = $options;
        $event->setArguments($arguments);
    }

    public function modifyConfig(\Pimcore\Event\Model\PrintConfigEvent $event) {

        $arguments = $event->getArguments();

        $processor = $event->getProcessor();
        if($processor instanceof \Pimcore\Web2Print\Processor\PdfReactor8) {
            
            //check if option for appending log to PDF is set in configuration and apply it to reactor config accordingly  
            if($arguments['config']->appendLog == 'true'){
                $arguments['reactorConfig']['appendLog'] = true;
            }
        }

        $event->setArguments($arguments);
    }
}

```


##### Example for adding PDF/X Conformance    

Services in Container see above. 

Implementation of Listener
```php
<?php 
namespace AppBundle\EventListener;

class PDFConfigListener
{
    public function modifyProcessingOptions(\Pimcore\Event\Model\PrintConfigEvent $event) {
        //optionally add some configuration options for user interface here - e.g. some select options for user
    }

    public function modifyConfig(\Pimcore\Event\Model\PrintConfigEvent $event){

        $arguments = $event->getArguments();

        $processor = $event->getProcessor();
        if($processor instanceof \Pimcore\Web2Print\Processor\PdfReactor8) {
            
            //Set pdf reactor config for generating PDF/X conform PDF  
            $arguments['reactorConfig']['conformance'] = \Conformance::PDFX4;
            $arguments['reactorConfig']["outputIntent"] = [
                'identifier' => "ISO Coated v2 300% (ECI)",
                'data' => base64_encode(file_get_contents('/path-to-color-profile/ISOcoated_v2_300_eci.icc'))
            ];
        }

        $event->setArguments($arguments);
    }
}

```
