# Database Setup 

Pimcore requires a standard MySQL database, the only thing you should assure is that the database uses `utf8mb4` as character set.  
If you create a new database just set the character set to `utf8mb4`.

> Note: You have to create the database manually before you can continue with the web-based installer, 
> which automatically creates the underlying database schema for Pimcore. 

### Command to Create a new Database 

```bash
mysql -u root -p -e "CREATE DATABASE project_database charset=utf8mb4;"
```

### Permissions needed for Pimcore

Pimcore requires all permissions on database level. You can create a user with the necessary
rights with the following commands:

```sql
CREATE USER 'project_user'@'localhost' IDENTIFIED BY 'PASSWORD';
GRANT ALL ON `project_database`.* TO 'project_user'@'localhost';
```
