# Performance Recommendations

For high traffic websites we recommend the following software and configurations: 

- Use a high performance cache backend for pimcore (Redis2 is recommended)
- Enable the output-cache in *Settings* > *System* 
- Install and setup varnish cache (pimcore sends already the right headers for varnish => ouput-cache lifetime, ... )
- Only use sessions when really necessary. The Pimcore Output-Cache detects the usage of sessions in the code and disables itself if necessary.
- Tuning your MySQL configuration to exactly fit the needs of your application
  - mysqltuner.pl
  - mysqlprimer 
- MariaDB / Percona Server (if improvements are expected => test it on your stage-environment)
- Make use of in-template caching
