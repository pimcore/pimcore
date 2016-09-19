# Maintenance Mode

Pimcore offers a maintenance mode, which limits the access to the user who enabled the maintenance mode. It is session based 
and no other user will be able to access the website or the admin interface. 

All other users get a [default "Temporary not available" page](https://demo.pimcore.org/pimcore/static/html/maintenance.html) 
displayed. 

Also maintenance scripts and headless executions of Pimcore will be prevented.  

The Maintenance Mode is activated by Pimcore during Pimcore Update.
 

## Customize Maintenance Page

The Maintenance Page can be customized by just putting a HTML-File under `/website/config/maintenance.html`. 



