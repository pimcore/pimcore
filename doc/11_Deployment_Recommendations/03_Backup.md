---
title: Backup
description: Components to back up and directories to exclude.
---

# Backup

Use standard backup tools appropriate to your infrastructure.
Regardless of the tool, back up these components:

- **All files in the project root**, excluding:
  - `public/var/tmp` - regenerated thumbnails
  - `var/tmp` - temporary upload/export files
  - `var/log` - log files (rotated automatically)
  - `var/cache` - Symfony/Pimcore cache (rebuilt automatically)
- **The entire database**

:::note
`var/versions` contains version history dumps and can be large, especially for assets.
Include it in backups to preserve version history, or exclude it if storage is constrained
and version history is expendable.
:::

## Simple Backup Using Unix Tools

Use a professional backup solution for production environments. For quick ad-hoc backups,
standard Unix tools work:

```bash
# Change to the project root
cd /var/www/your/project/

# Create the MySQL dump
mysqldump -u youruser -p yourdatabase > /tmp/pimcore-backup.sql

# Create an archive of the project root and database dump, excluding regenerable directories
tar cfz /tmp/pimcore-backup.tar.gz \
  --exclude='public/var/tmp' \
  --exclude='var/tmp' \
  --exclude='var/log' \
  --exclude='var/cache' \
  ./ -C /tmp pimcore-backup.sql
```
