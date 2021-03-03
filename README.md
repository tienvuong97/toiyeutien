## Project base on October CMS

October CMS has a few system requirements:

* PHP version 7.2 or higher
* PDO PHP Extension (and relevant driver for the database you want to connect to)
* cURL PHP Extension
* OpenSSL PHP Extension
* Mbstring PHP Extension
* ZipArchive PHP Extension
* GD PHP Extension
* SimpleXML PHP Extension

Some OS distributions may require you to manually install some of the required PHP extensions.

When using Ubuntu, the following command can be run to install all required extensions:

```bash
sudo apt-get update &&
sudo apt-get install php php-ctype php-curl php-xml php-fileinfo php-gd php-json php-mbstring php-mysql php-sqlite3 php-zip
```

## Project Development

1. Create a database and run script ./docs/db/init.sql
2. Acc: admin/123456

## Project Feature

* Blog
* Tool for personal finance