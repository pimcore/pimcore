# Database Setup 

Pimcore requires a standard MySQL database, the only thing you should assure is that the database uses `utf8mb4` as character set.  
If you create a new database just set the character set to `utf8mb4`.

> Note: You have to create the database manually before you can continue with the web-based installer, 
> which automatically creates the underlying database schema for pimcore. 

### Command to Create a new Database 

```bash
mysql -u root -p -e "CREATE DATABASE pimcore charset=utf8mb4;"
```

### Permissions needed for Pimcore 
Pimcore requires all permissions on database level, that means the following: 

```sql
CREATE DATABASE `project_database` charset=utf8mb4;
CREATE USER 'project_user'@'%' IDENTIFIED BY 'PASSWORD';
GRANT USAGE ON *.* TO 'project_user'@'%';
GRANT ALL ON `project_database`.* TO 'project_user'@'%' WITH GRANT OPTION;
```
