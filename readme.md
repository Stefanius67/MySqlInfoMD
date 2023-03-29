# Generating report for a given MySQL database as MD-Files 

 ![Latest Stable Version](https://img.shields.io/badge/release-v1.0.0-brightgreen.svg)
 ![License](https://img.shields.io/packagist/l/gomoob/php-pushwoosh.svg) 
 [![Donate](https://img.shields.io/static/v1?label=donate&message=PayPal&color=orange)](https://www.paypal.me/SKientzler/5.00EUR)
 ![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.4-8892BF.svg)
 
----------
## Overview

This class can generate a complete documentation of a MySQL database in Markdown format.
In the MD format the documentation can be made available for everyone who is involved 
in an project containing a MySQL database. There are various scenarios:
- The easies way is just make the files accessible at the filesystem/fileserver
- More comfortable can be a webbased publication as part of an intra- or extranet 
  since the MD format is wide supported
- Or you can integrate it into other proprietaire layers (i.e. wikis, 
  documentation systems, ...)

The report contains 
1. An overview file with links to datail files for
   - tables
   - views
   - procedures
   - functions
   - trigger
2. Details for tables and views
   - all fields with datatype and keydefinition, nullable, defaultvalue
   - defined triggers
   - defined constraints
     - tables referenced by this table
	 - tables that references this table
3. Details for precedures, functions and trigger
   - create statement containing all infos

## Usage
1. Create an `mysqli` connection to the database to build the report for.
2. Create an instance of the `MySqlInfoMD()` class and pass the DB connection
3. Set prefered directories and options
4. Call the `buildInfo()` method.

```php
$strDBHost = 'localhost';
$strDBName = 'demo';
$strDBUser = 'demo';
$strDBPwd = 'demoPWD';
$oDB = mysqli_connect($strDBHost, $strDBUser, $strDBPwd, $strDBName);

$oInfo = new MySqlInfoMD($oDB, $MySqlInfoMD::STANDALONE);
$oInfo->setOptions(MySqlInfoMD::OPT_CREATE_SUBFOLDER | MySqlInfoMD::OPT_CREATE_STMT);
$oInfo->buildInfo();
```

> **Note:**  
> Since the Markdown renderer that is used at *phpClasses.org* do **not** support MD 
> tables, you will not get a satisfactory display if you look at the example directly
> in the source view window here in the package.

## Supported types
The report can be generated for
- `STANDALONE`
  Create the MD files for standalone use (file or web based) to view with a browser 
  addon or any MD viewer
- `GITHUB_WIKI`
  Build the MD files to upload to a Github-Wiki.
- `SKIEN_WIKI`
  Build the MD files for display inside a self defined structure for more complex 
  wikis.
  
## recommended browser add-ons
A very good display quality you get e.g. with the browser add-on ***'Markdown Viewer'***
(from somiv) that is available for most major browsers:

### firefox
https://addons.mozilla.org/de/firefox/addon/markdown-viewer-chrome/

### chrome
https://chrome.google.com/webstore/detail/markdown-viewer/ckkdlimhmcjmikdlpkmbgfkaikojcbjk?hl=de

### or visit on github
https://github.com/simov/markdown-viewer

