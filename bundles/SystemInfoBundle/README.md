# Pimcore System Info & Tools
System Info & Tools bundle provides additional tools for checking system information in Pimcore Admin UI.

## Tools:

### PHP Info
A simple interface that implement `phpinfo()` to provide PHP installation information.

You can check via Admin UI `Tools` / `System Info & Tools` / `PHP Info` menu.

### PHP OPcache Status
A clean interface based on [`amnuts/opcache-gui`](https://github.com/amnuts/opcache-gui) for 
Zend OPcache information, showing statistics, settings and cached files, and providing a real-time update 
for the cache information.

You can check via Admin UI `Tools` / `System Info & Tools` / `PHP OPcache Status` menu.

### System Requirements Check
A tool that gives you an overview of required and optional system requirements for running Pimcore Application.

You can check via Admin UI `Tools` / `System Info & Tools` / `System-Requirements Check` menu.

Or via following CLI command:

```bash
bin/console pimcore:system:requirements:check
```