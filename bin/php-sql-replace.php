#!/usr/bin/env php
<?php

$shortopts  = "";
$shortopts .= "f:";  // Required value
$shortopts .= "v::"; // Optional value
$shortopts .= "abc"; // These options do not accept values

$longopts  = array(
    "input-file-path:",     
    "output-file-path:",   
    "match:",  
    "replace:",           
);
$options = getopt("", $longopts);


if(count($options)!=count($longopts)){
    echo <<<EOD
Php SQL Replacer Usage:

php-sql-replace.php --input-file-path="./original.sql" --output-file-path="./updated.sql" --match="Original Text" --replace="New Text"

Replaces all occurrences of a value even in serialized values in a file

Required Options:
--input-file-path   : Path of input file
--output-file-path  : Path of output file
--match             : Exact string to look for
--replace           : String to replace match with

EOD;
    exit(1);
}

require_once dirname(__FILE__) . '/../vendor/autoload.php';


$replacer = new \PhpSqlReplacer\PhpSqlReplacer();
echo "Processing\n\n";
$replacer->replaceValueFromFile(
    $options['input-file-path'],
    $options['match'],
    $options['replace'],
    $options['output-file-path']
);
echo "Complete\n\n";