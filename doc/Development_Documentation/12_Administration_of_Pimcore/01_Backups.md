# Backup of Pimcore

We recommend the usage of standard tools depending on your infrastructure for creating a back up of your Pimcore instance.

No matter which solution your're using, it's crucial to backup the following components: 
- All files in your project root, however you can normally exclude the following directories 
`web/var/tmp`, `var/tmp`, `var/logs`, `var/cache` and `var/sessions`
- The entire database 

#- Poor man's backup using Unix tools

We definitely recommend to use a professional backup solution depending on your infrastructure, but sometimes a poor 
man's backup can be quite handy :) 

```bash 

# change directory to your project root 
cd /var/www/your/project/

# create an archive of the entire project root, excluding temporary files
tar cfv /tmp/my-poor-mans-backup.tar ./

# create the mysql dump
mysqldump -u youruser -p yourdatabase > /tmp/my-poor-mans-backup.sql 

# put the dump into the tar archive
tar rf /tmp/my-poor-mans-backup.tar /tmp/my-poor-mans-backup.sql

# zip the archive (of course you can also use xz or any other tool) 
gzip /tmp/my-poor-mans-backup.tar

```