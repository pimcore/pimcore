# Pimcore Web to Print Bundle

Adds the ability to create web-to-print documents in Pimcore and to convert them into a PDF.

## Document Types
This bundle introduces 2 new document types:

| Type           | Description                                                                                                                                                 | 
|----------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------|
| [PrintPage](./doc/01_Print_Documents.md#printpage)      | Like pages, but specialized for print (PDF preview, rendering options, ...)                                                                                 | 
| [PrintContainer](./doc/01_Print_Documents.md#printcontainer) | Organizing print pages in chapters and render them all together.                                                                                            | 

## Available PDF Processors

| Name           | Description                                                                                                                                                 | 
|----------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------|
| [Chromium](https://www.chromium.org/Home/)      | Convert to PDF by installing the Chromium binary or by using a dockerized chromium (via websocket)                                                                              | 
| [Gotenberg](https://gotenberg.dev/) | A Docker service with Chromium and LibreOffice support   | 
| [PDF Reactor](https://www.pdfreactor.com/) | A REST/SOAP solution, please visit the official website for further information                                                                                          | 

 > For details on how to install and configure these processors, please see [Additional Tools Installation](https://github.com/pimcore/pimcore/blob/11.x/doc/23_Installation_and_Upgrade/03_System_Setup_and_Hosting/06_Additional_Tools_Installation.md) page in the Core.

### Installation
After installing the bundle and the required dependencies of the processor you wish to use, you need to configure the settings under *Settings >  Web-to-Print*. 
There you will find detailed notes about the options and settings available. depending on which processor you will use. 

### Uninstallation
Uninstalling the bundle does not clean up `printpages` or `printcontainers`. Before uninstalling make sure to remove or archive all dependent documents.
You can also use the following command to clean up you database. Create a backup before executing the command. All data will be lost.

```bash
 bin/console pimcore:document:cleanup printpage printcontainer
```

### Best Practice

- [Events and PDFX Conformance](./doc/90_Web2Print_Extending_Config_for_PDFX_conformance.md)

## Contributing and Development

For details see our [Contributing guide](./CONTRIBUTING.md).