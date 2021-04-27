# Directory Structure

After installing a Pimcore package you should see the folder structure described below.  
The following table should give you a quick overview about the purpose of each folder.  
In general the directory structure follows the [best practice for Symfony projects](https://github.com/symfony/symfony-demo). 

| Directory                                            | Description                               |
|------------------------------------------------------|-------------------------------------------|
| `/bin/`      | Executable files (e.g. bin/console).                                               |
| `/config/`   | The application configuration.                                                     |
| `/public/`   | This is the **document root** (public folder) for your project - point your vhost to this directory!  |
| `/src/`      | The project's PHP code (Services, Controllers, EventListeners, ...)                |
| `/templates/`| The application templates.                                                     |
| `/var/`      | Private generated files - not accessible via the web (cache, logs, etc.).          |
| `/vendor/`   | All third-party libraries are there. It's the default location for packages installed by [Composer](https://getcomposer.org/) / [Packagist](https://packagist.org/). |

  
### The `public/` Directory

The web root directory is the home of all public and static files like images, stylesheets and 
JavaScript files. It is also the place where each front controller (the file that handles all requests 
to your application) lives, such as the [production controller](https://github.com/pimcore/skeleton/blob/master/public/index.php)


### The `src/` Directory
The Kernel class is the main entry point of the Symfony application configuration and as such, 
it is stored in the `src/` directory.
  
  
For more information about the folder structure and the architecture in general, please have a look at the 
[Symfony documentation](https://symfony.com/doc/5.2/best_practices.html#use-the-default-directory-structure). 
