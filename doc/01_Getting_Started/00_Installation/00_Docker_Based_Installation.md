# Docker-Based Installation

You can use Docker to set up a new Pimcore Installation.
You don't need to have a PHP environment with composer installed.

### Prerequisites

* Your user must be allowed to run docker commands (directly or via sudo).
* You must have docker compose installed.
* Your user must be allowed to change file permissions (directly or via sudo).

### Follow These Steps

1. Choose a Package to Install and create the project via composer
   * We offer 2 different installation packages:

```bash
# demo package with exemplary blueprints (`pimcore/demo`)
docker run -u `id -u`:`id -g` --rm -v `pwd`:/var/www/html pimcore/pimcore:php8.2-latest composer create-project pimcore/demo my-project
```  

```bash 
# empty skeleton package for experienced developers (`pimcore/skeleton`).
docker run -u `id -u`:`id -g` --rm -v `pwd`:/var/www/html pimcore/pimcore:php8.2-latest composer create-project pimcore/skeleton my-project
```

2. Go to your new project
`cd my-project/`

3. Part of the new project is a docker compose file
    * Run `` echo `id -u`:`id -g` `` to retrieve your local user and group id
    * Open the `docker-compose.yaml` file in an editor, uncomment all the `user: '1000:1000'` lines and update the ids if necessary
    * Start the needed services with `docker compose up -d`

4. Install Pimcore and initialize the DB
    `docker compose exec php vendor/bin/pimcore-install --mysql-host-socket=db --mysql-username=pimcore --mysql-password=pimcore --mysql-database=pimcore` (for demo package the installation can take a while)

:::info

If you choose to install backend search (which is installed by default), you must also adapt the [supervisor configuration](https://github.com/pimcore/skeleton/blob/11.x/.docker/supervisord.conf#LL5C39-L5C90) and add the `pimcore_search_backend_message` receiver to build up the search index. 


:::

5. :heavy_check_mark: DONE - You can now visit your Pimcore instance:
    * The frontend: [localhost](http://localhost)
    * The admin interface, using the credentials you have chosen above:
      [Admin interface](http://localhost/admin)


## Caching
Make sure to use any sort of [caching](https://pimcore.com/docs/platform/Pimcore/Development_Tools_and_Details/Cache/) to improve performance. We recommend Redis cache storage.

## Additional Information & Help

If you would like to know more about the installation process or if you are having problems getting Pimcore up and running, visit the [Installation Guide](../../23_Installation_and_Upgrade/README.md) section.

## Automating the Installation Process

For more information about ways to automate the installation process, have a look on [Advanced Installation Topics](../02_Advanced_Installation_Topics/README.md).
