# Magic Parameters

Pimcore supports some *magic parameters* which can be added as parameter to every request.

### pimcore_nocache
Setting this parameter disables every kind of cache, eg.: `http://www.example.com/my/page?pimcore_nocache`  
This parameter only works if [`DEBUG MODE`](../18_Tools_and_Features/25_System_Settings.md) is on.

### pimcore_outputfilters_disabled
Disables all output filters, incl. the output-cache. But this doesn't disable the internal object cache, 
eg.: `http://www.example.com/my/page?pimcore_outputfilters_disabled=1`  
This parameter only works if [`DEBUG MODE`](../18_Tools_and_Features/25_System_Settings.md) is on.

### pimcore_log
Enables verbose logging (including database queries) to a separate log file only for this particular 
request called with this parameter, eg.: `http://www.example.com/my/page?pimcore_log=my-log-name` 

If no value is set to this parameter the log file can be found here: `/var/logs/request-[Y-m-d_H-i-s].log`. 
If a value is given, the value will be part of the log files name: `/var/logs/request-[NAME].log`
  
This parameter only works if [`DEBUG MODE`](../18_Tools_and_Features/25_System_Settings.md) is on. (this is also the successor of the parameter `pimcore_dbprofile` in earlier versions)

### pimcore_disable_host_redirect
Disables the "redirect to main domain" feature. This is especially useful when using Pimcore behind 
a reverse proxy. 
