[![Build Status](https://travis-ci.org/gggordon/php-sql-replacer.svg?branch=main)](https://travis-ci.org/gggordon/php-sql-replacer)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/gggordon/php-sql-replacer.svg?style=flat-square)](https://scrutinizer-ci.com/g/gggordon/php-sql-replacer/)
[![Codecov](https://img.shields.io/codecov/c/github/gggordon/php-sql-replacer.svg?style=flat-square)](https://codecov.io/gh/gggordon/php-sql-replacer)
[![License](https://poser.pugx.org/gggordon/php-sql-replacer/license?format=flat-square)](https://packagist.org/packages/gggordon/php-sql-replacer)
[![Latest Stable Version](https://poser.pugx.org/gggordon/php-sql-replacer/version?format=flat-square)](https://packagist.org/packages/gggordon/php-sql-replacer)
[![Latest Unstable Version](https://poser.pugx.org/gggordon/php-sql-replacer/v/unstable?format=flat-square)](//packagist.org/packages/gggordon/php-sql-replacer)

# PHP SQL Replacer

A utility to replace strings/values in `SQL`  files especially with **serialized** `php` values. Useful for database migrations/clones. 

# Installation

```
composer require gggordon/php-sql-replacer
```

# Usage

## Command Line Usage

```
./bin/php-sql-replacer --input-file-path="./original.sql" --output-file-path="./updated.sql" --match="Original Text" --replace="New Text"

Required Options:
--input-file-path   : Path of input file
--output-file-path  : Path of output file
--match             : Exact string to look for
--replace           : String to replace match with

```

## Using Code

If you already have the contents stored as a string, you may follow the example below to replace the contents.
```php
<?php

require_once dirname(__FILE__) . '/vendor/autoload.php';

$contents ="insert into test (column1, column2) values ('I like trading','Another Value')";

$replacer = new \PhpSqlReplacer\PhpSqlReplacer();
$updatedContents = $replacer->replaceValue(
    $contents, 
    "trading", 
    "butter"
);

echo $updatedContents;
```
to get the following output:
```sql
insert into test (column1, column2) values ('I like butter','Another Value')
```

**OR**

If you are extracting the contents from a file:

```php
<?php

require_once dirname(__FILE__) . '/vendor/autoload.php';

$replacer = new \PhpSqlReplacer\PhpSqlReplacer();
$updatedContents = $replacer->replaceValueFromFile("/path/to/original_file.sql", "trading", "butter", "/path/to/output_file.sql");

echo $updatedContents;
```
to get the following output:
```sql
insert into test (column1, column2) values ('I like butter','Another Value')
```
and a new file created at `/path/to/output_file.sql`.

# Tests

Testing done using [`phpunit`](https://phpunit.de/)

```
phpunit
```

# Documentation

Documentation Generated using [`phpdox`](http://phpdox.de/)

```
phpdox
```

# License

MIT License

# Maintained by

[ gggordon](https://github.com/gggordon)