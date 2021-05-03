# Performance Recommendations

For high traffic websites, we recommend the following software and configurations: 

- Use [Redis cache backend](../../19_Development_Tools_and_Details/09_Cache/README.md)
- Enable the [full-page cache](../../19_Development_Tools_and_Details/09_Cache/03_Full_Page_Cache.md) in *Settings* > *System*.
- Install and setup [Varnish](https://www.varnish-cache.org/) cache and make use of ESI (Pimcore sends the right headers for Varnish if full-page cache is enabled).
- Only use sessions when really necessary. The Pimcore full-page cache detects the usage of sessions in the code and disables itself if necessary.
- Tuning your database configuration to exactly fit the needs of your application.
  - [mysqltuner.pl](https://github.com/rackerhacker/MySQLTuner-perl)
  - [mysqlprimer](https://launchpad.net/mysql-tuning-primer)
  - [MariaDB](http://mariadb.org/) / [Percona Server](http://www.percona.com/software/percona-server/) (if improvements are expected => test it on your stage-environment).
- Make use of [in-template caching](../../02_MVC/02_Template/02_Template_Extensions/README.md).
