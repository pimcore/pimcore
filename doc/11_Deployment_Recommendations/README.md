---
title: Deployment Recommendations
description: Best practices and tools for deploying Pimcore applications.
---

# Deployment Recommendations

Pimcore follows standard Symfony deployment practices.
This chapter covers the tools and conventions specific to Pimcore on top of that foundation.

- [**Symfony Deployment**](https://symfony.com/doc/current/deployment.html) -
  The official Symfony deployment guide applies to Pimcore projects.
- [**Version Control**](./01_Version_Control_Systems.md) -
  Git configuration and paths to exclude from tracking.
- [**Configuration Environments**](../08_Development_Details/01_Configuration/01_Configuration_Environments.md) -
  Environment-specific configuration for different deployment stages.
- [**Deployment Tools**](./02_Deployment_Tools.md) -
  Configuration management, class definitions, and console commands for deployment.
- [**Backup**](./03_Backup.md) -
  Components to back up and directories to exclude.
- [**Cleanup Data Storage**](./04_Cleanup_Data_Storage.md) -
  Ongoing maintenance of versioning data, logs, temporary files, and recycle bin.
- [**Security Concept**](./05_Security_Concept.md) -
  Multi-layer security approach for Pimcore applications.
