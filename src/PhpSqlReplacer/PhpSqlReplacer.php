<?php

namespace PhpSqlReplacer;

use PhpSqlReplacer\Exceptions\SqlReplacerException;

class PhpSqlReplacer
{
    public function extractSqlValuesFromFile($filePath, $patterToMatchFor=null, $includeColumnNames=false)
    {
        if (!file_exists($filePath) || !is_string($filePath)) {
            throw new SqlReplacerException("File '$filePath' does not exist");
        }
        return $this->extractSqlValues(
           file_get_contents($filePath),
           $patterToMatchFor,
           $includeColumnNames
       );
    }

    public function extractSqlValues($contents, $patterToMatchFor=null, $includeColumnNames=false)
    {
        $startTime = time();
        $currentDate=date("Y-m-d H:i:s");
        
        $tokens=[];
        if (preg_match_all("/\(.*\)/U", $contents, $matches)) {
            $tokens=$matches[0];
        }
        $difference = (time()-$startTime)/1000/60;
        //echo "end token split - $difference - ".date("Y-m-d H:i:s")."\n";
        $tokens = array_filter($tokens, function ($token) {
            return $token[0]=="(" && $token[strlen($token)-1]==")";
        });
        $tokens = array_map(function ($piece) use ($includeColumnNames) {
            // var_dump($piece);
            $piece = trim(trim($piece, "("), ")");
            $pieces = explode(",", $piece);
            $pieces= array_filter(array_map('trim', $pieces), function ($p) {
                return !empty($p) && strlen($p)>0;
            });
            $pieces = array_values($pieces);
            if (!$includeColumnNames && count($pieces)>0) {
                // var_dump($pieces[0]);
                $firstPiece = $pieces[0];
                //echo $firstPiece."\n";
                
                if ($firstPiece[0] != "'" &&
                    $firstPiece[0] != "\"" &&
                    preg_match("/[a-zA-Z]/", $firstPiece[0])
                    ) {
                    //echo "\n$firstPiece is a column \n";
                    return [];
                }
            }
            return $pieces;
        }, $tokens);
        
        $matches = [];
        array_walk_recursive($tokens, function ($value) use (&$matches,$patterToMatchFor) {
            if ($patterToMatchFor==null ||
                       stripos($value, $patterToMatchFor) !== false) {
                $matches[]=$value;
            }
        });
        
        
        return $matches;
    }

    public function replaceValue($contents, $value, $replaceWith)
    {
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
        });
        $matches = $this->extractSqlValues($contents, $value);
        
        foreach ($matches as $match) {
            $matchWithoutQuotes = str_replace("\\\"", "\"", trim(trim($match, "'")));
            if ($this->isSerializedValue($matchWithoutQuotes)) {
                $matchReplaced = str_replace($value, $replaceWith, $matchWithoutQuotes);
                $matchReplaced = preg_replace_callback('!s:(\d+):"(.*?)";!', function ($match) {
                    return ($match[1] == strlen($match[2])) ? $match[0] : 's:' . strlen($match[2]) . ':"' . $match[2] . '";';
                }, $matchReplaced);
                try {
                    unserialize($matchReplaced);
                } catch (\Exception $e) {
                    //silently ignore
                    $matchReplaced=$match;
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

    public function isSerializedValue($data, $strict=true)
    {
        //CREDIT TO: Wordpress.com
        // If it isn't a string, it isn't serialized.
        if (! is_string($data)) {
            return false;
        }
        $data = trim($data);
        if ('N;' === $data) {
            return true;
        }
        if (strlen($data) < 4) {
            return false;
        }
        if (':' !== $data[1]) {
            return false;
        }
        if ($strict) {
            $lastc = substr($data, -1);
            if (';' !== $lastc && '}' !== $lastc) {
                return false;
            }
        } else {
            $semicolon = strpos($data, ';');
            $brace     = strpos($data, '}');
            // Either ; or } must exist.
            if (false === $semicolon && false === $brace) {
                return false;
            }
            // But neither must be in the first X characters.
            if (false !== $semicolon && $semicolon < 3) {
                return false;
            }
            if (false !== $brace && $brace < 4) {
                return false;
            }
        }
        $token = $data[0];
        switch ($token) {
        case 's':
            if ($strict) {
                if ('"' !== substr($data, -2, 1)) {
                    return false;
                }
            } elseif (false === strpos($data, '"')) {
                return false;
            }
            // Or else fall through.
            // no break
        case 'a':
        case 'O':
            return (bool) preg_match("/^{$token}:[0-9]+:/s", $data);
        case 'b':
        case 'i':
        case 'd':
            $end = $strict ? '$' : '';
            return (bool) preg_match("/^{$token}:[0-9.E+-]+;$end/", $data);
    }
        return false;
    }
}
