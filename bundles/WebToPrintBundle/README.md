# Pimcore Web to Print Bundle

Adds the ability to create web-to-print documents in Pimcore and to generate PDFs.

## Document Types
This bundle introduces 2 new document types:

| Type           | Description                                                                                                                                                 | 
|----------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------|
| [PrintPage](./doc/01_Print_Documents.md)      | Like pages, but specialized for print (PDF preview, rendering options, ...)                                                                                 | 
| [PrintContainer](./doc/01_Print_Documents.md) | Organizing print pages in chapters and render them all together.                                                                                            | 

## Available PDF Processors

| Name           | Description                                                                                                                                                 | 
|----------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------|
| [Chromium](https://github.com/pimcore/pimcore/blob/11.x/doc/23_Installation_and_Upgrade/03_System_Setup_and_Hosting/06_Additional_Tools_Installation.md#chromium-chrome-headless)      | Convert to PDF by installing the chromium binary or by using a dockerized chromium                                                                               | 
| [Gotenberg](https://github.com/pimcore/pimcore/blob/11.x/doc/23_Installation_and_Upgrade/03_System_Setup_and_Hosting/06_Additional_Tools_Installation.md#gotenberg) | A Docker service with Chromium and LibreOffice support   | 
| [PDF Reactor](https://www.pdfreactor.com/) | Please visit the website for further information and pricing plans.                                                                                          | 

 > For details on how to install and configure these processors, please see [Additional Tools Installation](https://github.com/pimcore/pimcore/blob/11.x/doc/23_Installation_and_Upgrade/03_System_Setup_and_Hosting/06_Additional_Tools_Installation.md) page in the Core.

### Installation
After installing the bundle and the required dependencies of the processor you want to use, you need to configure the settings in the *Settings >  Web-to-Print* Settings. 
There you will find detailed notes about the settings available, as they differ depending on which processor will be in use.

### Uninstallation
Uninstalling the bundle does not clean up `printpages` or `printcontainers`. Before uninstalling make sure to remove or archive all dependent documents.
You can also use the following command to clean up you database. Create a backup before executing the command. All data will be lost.

```bash
 bin/console pimcore:document:cleanup printpage printcontainer
```

### Best Practice

Please see the [PDFX Conformance](./doc/90_Web2Print_Extending_Config_for_PDFX_conformance.md) page.

## Contributing and Development

For details see our [Contributing guide](./CONTRIBUTING.md).