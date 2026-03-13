# Fix Performance Issues on Windows

> It is highly recommended not to use Windows based systems in production!

In times of Docker, the host operating system shouldn't really matter - at least in theory. Our experience shows, that 
Docker in combination with a Windows host system has its problems – especially performance wise when mounting volumes to 
local file systems, which is quite mandatory when developing with Pimcore.

In combination with [Windows Subsystem for Linux (WSL)](https://docs.microsoft.com/en-us/windows/wsl/install-win10) there 
are some tricks to improve performance and make local development with Windows systems possible. 

Requirement for this is to have [WSL2](https://docs.microsoft.com/en-us/windows/wsl/install-win10) and 
[Docker](https://docs.docker.com/docker-for-windows/install/) installed on the Windows system. Then you can run Docker 
completely in your WSL environment and also do the bind mounts directly within the linux file system (not on to the Windows 
mounts somewhere like `/mnt/c/Users` or so!).

Then you can follow our default docker installation instructions in the [skeleton readme](https://github.com/pimcore/skeleton) 
file. 

If you want to access the mounted resources within our IDE, you can access them via this virtual network share and 
work on the files, like e.g. `\\wsl$\<YOUR_DISTRIBUTION>\home\<USER>\pimcore`

> Sufficient RAM of the host system will be required for proper performance. 
