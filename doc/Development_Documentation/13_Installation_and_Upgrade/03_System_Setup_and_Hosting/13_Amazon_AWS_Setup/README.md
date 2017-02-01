# Amazon AWS Setup Guide & Best Practice Configurations

This guide works using Pimcore version => 4.2.0 (build 3878)

## App-Server (EC2 + EBS)
We recommend a Debian based setup. Provision as many nodes as needed. 
See also our [Docker example configuration](https://github.com/pimcore/docker-pimcore-demo-standalone/blob/master/Dockerfile).  

## Database (Aurora)
There's nothing special to consider, but of course it's necessary to configure Aurora according to your database size and structure (innodb_buffer_pool_size, ...). 

## Cache (ElastiCache)
We recommend using the Redis engine, with the following settings:
```
maxmemory-policy allkeys-lru
# memory depends on the amount and structure of your data
maxmemory 512mb
```

## File Storage
You have 3 choices: 
- Locally on EBS (recommended for single instance setup)
- Certain files like `website/var/assets` and `website/var/tmp` on EFS (recommended for multi instance setup)
  - Mount EBS volume on all instances under a certain path
  - Create symlinks for `website/var/assets` and `website/var/tmp` ... or use [contants.php](../../../10_Extending_Pimcore/09_Hook_into_the_Startup_Process.md) to overwrite default locations
- [Certain files](03_Amazon_AWS_S3_Setup.md) like `website/var/assets` and `website/var/tmp` on [Amazon S3](03_Amazon_AWS_S3_Setup.md) (recommended for multi-zone setup) 

## CDN (CloudFront) & Elastic Load Balancing 
Setup as normal and adapt the configuration to your CDN needs (rewriting the frontend paths). 
Details: see [CDN - Amazon Cloudfront](02_Amazon_AWS_Cloudfront_CDN_Setup.md)

## Deployment
If you are running a single instance on AWS there's nothing special to consider. You can modify your object classes directly in the admin interface and deploy your code to the EBS volume (via EC2). 

#### Cluster Deployment
If you're in cluster-mode deployment needs more attention. 

> **Please notice:**   
> Never ever make any configuration changes that reflect on files (like object class modifications, static routes, 
> document types, predefined properties, system settings, thumbnails, etc.) in cluster-mode!  

- Prepare & test all your changes on a test-system
- Deploy the following to all EC2/EBS instances 
  - Your code changes, basically everything in `/website/` except `/website/var`
  - everything in `/website/var/classes` (if you changed a class, field collection or object brick) 
  - everything in `/website/var/config/` (**Note**: you can use multi environment configurations to specify different configurations for different environments)
- Run the following commands in order to recreate the database schema according to your changes in the class editor and clear the cache
```
php pimcore/cli/console.php deployment:classes-rebuild
php pimcore/cli/console.php cache:clear
```

Done, that's it!  
Everything should work as before.  

### Known issues
- Using S3: A remote object storage (HTTP based) can't be as fast as a local storage, bear that in mind! 
 
