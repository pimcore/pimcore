# Directories Structure

After extracting the installation package of Pimcore you should see the folder structure described below.  
The following tables should give you a quick overview about the purpose of this folders.  
In general the directory structure follows the [best practice for Symfony projects](https://github.com/symfony/symfony-demo). 

| Directory                                            | Description                               |
|------------------------------------------------------|-------------------------------------------|
| `/app/`     | The application configuration, templates and translations.                         |
| `/bin/`     | Executable files (e.g. bin/console).                                               |
| `/doc/`     | Core files of Pimcore, do not change anything here.                                |
| `/pimcore/` | Core files of Pimcore, do not change anything here.                                |
| `/src/`     | The project's PHP code (Services, Controllers, EventListeners, ...                 |
| `/var/`     | Private generated files - not accessible via the web (cache, logs, etc.).          |
| `/vendor/`  | All third-party libraries are there. It's the default location for packages installed by [Composer](https://getcomposer.org/) / [Packagist](https://packagist.org/). |
| `/web/`     | This is the **document root** (public folder) for your project - point your vhost to this directory!  |
  
  
### The `web/` Directory

The web root directory is the home of all public and static files like images, stylesheets and 
JavaScript files. It is also the place where each front controller (the file that handles all requests 
to your application) lives, such as the [production controller](https://github.com/pimcore/pimcore/blob/master/web/app.php)


### The `app/` Directory
The AppKernel class is the main entry point of the Symfony application configuration and as such, 
it is stored in the `app/` directory.
  
  
For more information about the folder structure and the architecture in general, please have a look at the 
[Symfony documentation](http://symfony.com/doc/current/quick_tour/the_architecture.html). 