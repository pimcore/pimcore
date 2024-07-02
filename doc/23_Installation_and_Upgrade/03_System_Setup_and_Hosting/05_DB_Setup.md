# Database Setup 

Pimcore requires a standard MySQL database, the only thing you should assure is that the database uses `utf8mb4` as character set.  
If you create a new database just set the character set to `utf8mb4`.

> Note: You have to create the database manually before you can continue with the web-based installer, 
> which automatically creates the underlying database schema for Pimcore.

### Command to Create a new Database

```bash
mysql -u root -p -e "CREATE DATABASE project_database CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
```

### Permissions needed for Pimcore

Pimcore requires all permissions on database level. You can create a user with the necessary
rights with the following commands:

```sql
CREATE USER 'project_user'@'localhost' IDENTIFIED BY 'PASSWORD';
GRANT ALL ON `project_database`.* TO 'project_user'@'localhost';
```
### Database Server Configuration (Optional)

While setting up the MySQL Server you can enforce `utf8mb4` character set and [lower case table names](https://dev.mysql.com/doc/refman/8.0/en/server-system-variables.html#sysvar_lower_case_table_names) by placing a `pimcore.cnf` file with the following contents into the server config directory (e.g. `/etc/mysql/conf.d/`). Refer to the server configuration manual applicable to your environment to determine the location of the server config directory.

```ini
# MySQL Server configuration for Pimcore.
# @See https://dev.mysql.com/doc/refman/8.0/en/option-files.html
# @See https://pimcore.com/docs/pimcore/11.0/Development_Documentation/Installation_and_Upgrade/System_Setup_and_Hosting/DB_Setup.html

# Applies to any client connecting to this sever
[client]
default-character-set=utf8mb4

# Applies to mysql cli client application
[mysql]
default-character-set=utf8mb4

# Applies to mysql server
[mysqld]
character-set-server=utf8mb4
collation-server=utf8mb4_general_ci
init-connect='SET NAMES utf8mb4'
lower_case_table_names=1
```

Setting `lower_case_table_names=1` makes sure that tables for Pimcore classes are created in lower case even though their class names contain capital letters. Starting with MySQL 8, you can no longer set the `lower_case_table_names` option after the data directory has been initialized. If the directory was already initialized with a different `lower_case_table_names` setting, MySQL will fail to start (`Different lower_case_table_names settings for server and data dictionary`). To fix this, place the `pimcore.cnf` file in the config directory, remove the MySQL data directory and run `mysqld --initialize`. This will delete all databases, so backup the existing databases with `mysqldump` before deleting the data directory.

```bash
rm -rf /var/lib/mysql
mkdir /var/lib/mysql
chown mysql:mysql /var/lib/mysql
mysqld --initialize
```
