<?php

namespace PhpSqlReplacer;

use PhpSqlReplacer\Exceptions\SqlReplacerException;
use PhpSqlReplacer\External\Serialization;

/**
 * PhpSqlReplacer - Main Driver Class
 * @author gggordon
 * @copyright gggordon 2020
 * @license MIT
 */
class PhpSqlReplacer
{
    /**
     * Extracts full record values and column names from insert statements within a file
     * For eg. if the the record contains `'I like trading'` and `trading` is the pattern being matched
     * `'I like trading'` will be extracted.
     *
     * @param string $filePath - Path to source file
     * @param string $patternToMatchFor - Optional pattern to match for. Default: null
     * @throws Exceptions\SqlReplacerException
     * @param bool  $includeColumnNames - Whether to include column names. Default:false
     * @return string[]
     */
    public function extractSqlValuesFromFile($filePath, $patternToMatchFor=null, $includeColumnNames=false)
    {
        if (!file_exists($filePath) || !is_string($filePath)) {
            throw new SqlReplacerException("File '$filePath' does not exist");
        }
        return $this->extractSqlValues(
           file_get_contents($filePath),
           $patternToMatchFor,
           $includeColumnNames
       );
    }

    /**
     * Extracts full record values and column names from insert statements within a string
     * For eg. if the the record contains `'I like trading'` and `trading` is the pattern being matched
     * `'I like trading'` will be extracted.
     *
     * @param string $contents - SQL Contents to search
     * @param string $patternToMatchFor - Optional pattern to match for. Default: null
     * @throws Exceptions\SqlReplacerException
     * @param bool  $includeColumnNames - Whether to include column names. Default:false
     * @return string[]
     */
    public function extractSqlValues($contents, $patternToMatchFor=null, $includeColumnNames=false)
    {
        $tokens=[];
        if (preg_match_all("/\(.*\)/U", $contents, $matches)) {
            $tokens=$matches[0];
        }
        
        $tokens = array_filter($tokens, function ($token) {
            return $token[0]=="(" && $token[strlen($token)-1]==")";
        });
        $tokens = array_map(function ($piece) use ($includeColumnNames) {
            $piece = trim(trim($piece, "("), ")");
            $pieces = explode(",", $piece);
            $pieces= array_filter(array_map('trim', $pieces), function ($p) {
                return !empty($p) && strlen($p)>0;
            });
            $pieces = array_values($pieces);
            if (!$includeColumnNames && count($pieces)>0) {
                $firstPiece = $pieces[0];
                
                
                if ($firstPiece[0] != "'" &&
                    $firstPiece[0] != "\"" &&
                    preg_match("/[a-zA-Z]/", $firstPiece[0])
                    ) {
                    return [];
                }
            }
            return $pieces;
        }, $tokens);
        
        $matches = [];
        array_walk_recursive($tokens, function ($value) use (&$matches,$patternToMatchFor) {
            if ($patternToMatchFor===null ||
                       stripos($value, $patternToMatchFor) !== false) {
                $matches[]=$value;
            }
        });
        
        
        return $matches;
    }

    /**
     * Safely (including managing serialized data) replaces matched string patterns in a string
     *
     * @param string $contents - String to search
     * @param string $value - Value to replace
     * @param string $replaceWith - Value to replace with
     * @return string Updated Contents
     */
    public function replaceValue($contents, $value, $replaceWith)
    {
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
        });
        $matches = $this->extractSqlValues($contents, $value);
        
        foreach ($matches as $match) {
            $matchWithoutQuotes = str_replace("\\\"", "\"", trim(trim($match, "'")));
            if (Serialization::isSerializedValue($matchWithoutQuotes)) {
                $matchReplaced = str_replace($value, $replaceWith, $matchWithoutQuotes);
                $matchReplaced = preg_replace_callback('!s:(\d+):"(.*?)";!', function ($match) {
                    return ($match[1] == strlen($match[2])) ? $match[0] : 's:' . strlen($match[2]) . ':"' . $match[2] . '";';
                }, $matchReplaced);
                try {
                    unserialize($matchReplaced);
                } catch (\Exception $e) {
                    //silently ignore
                    continue;
                }
                $matchReplaced = "'$matchReplaced'";
            } else {
                $matchReplaced = str_replace($value, $replaceWith, $match);
            }
            $contents = str_replace($match, $matchReplaced, $contents);
        }
        $contents=str_replace($value, $replaceWith, $contents);
        restore_error_handler();
        return $contents;
    }

    /**
     * Safely (including managing serialized data) replaces matched string patterns in a file and optionally saves to another file
     *
     * @param string $sourceFile - Source file to search
     * @param string $value - Value to replace
     * @param string $replaceWith - Value to replace with
     * @param string $destinationFile - Destination file to write to
     * @throws Exceptions\SqlReplacerException
     * @return string Updated Contents
     */
    public function replaceValueFromFile($sourceFile, $value, $replaceWith, $destinationFile=null)
    {
        if (!file_exists($sourceFile) || !is_string($sourceFile)) {
            throw new SqlReplacerException("Source File '$sourceFile' does not exist");
        }
        if (is_null($destinationFile) || !is_string($sourceFile)) {
            throw new SqlReplacerException("Destination File '$destinationFile'  is required");
        }
        $contents = $this->replaceValue(
             file_get_contents($sourceFile),
             $value,
             $replaceWith
        );
        
        if ($destinationFile) {
            file_put_contents($destinationFile, $contents);
        }
        return $contents;
    }


}
