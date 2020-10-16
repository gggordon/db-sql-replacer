<?php

namespace DbSqlReplacer\Tests;

use PHPUnit\Framework\TestCase;
use DbSqlReplacer\DbSqlReplacer;
use DbSqlReplacer\Exceptions\SqlReplacerException;

class DbSqlReplacerTest extends TestCase
{
    private function getTestFilePath()
    {
        return dirname(__FILE__).'/data/sample.sql';
    }
    
    public function testEnsureFileExists()
    {
        $replacer = new DbSqlReplacer();
        try {
            $replacer->extractSqlValuesFromFile($this->getTestFilePath().'wrong');
            $this->assertTrue(false);
        } catch (SqlReplacerException $e) {
            $this->assertTrue(true);
        }
        
        try {
            $replacer->extractSqlValuesFromFile($this->getTestFilePath());
            $this->assertTrue(true);
        } catch (SqlReplacerException $e) {
            $this->assertTrue(false);
        }
    }

    

    public function testExtractSqlValues()
    {
        $replacer = new DbSqlReplacer();
        $actualValues = $replacer->extractSqlValuesFromFile($this->getTestFilePath(), null, true);
        $this->assertEquals(12, count($actualValues));
        $actualValues = $replacer->extractSqlValuesFromFile($this->getTestFilePath());
        $this->assertEquals(8, count($actualValues));
    }

    public function testExtractSqlValuesMatchingValue()
    {
        $replacer = new DbSqlReplacer();
        $actualValues = $replacer->extractSqlValuesFromFile($this->getTestFilePath(), "none");
        $this->assertEquals(0, count($actualValues));
        $actualValues = $replacer->extractSqlValuesFromFile($this->getTestFilePath(), "trading");
        $this->assertEquals(2, count($actualValues));
    }

    public function testReplaceValue()
    {
        $replacer = new DbSqlReplacer();
        $contents = file_get_contents($this->getTestFilePath());
        $updatedContents = $replacer->replaceValue($contents, "trading", "butter");
        $this->assertTrue(strpos($updatedContents, "butter")>-1);
    }

    public function testReplaceFromFileValue()
    {
        $replacer = new DbSqlReplacer();
        $outputFileName = $this->getTestFilePath().'.out';
        $updatedContents = $replacer->replaceValueFromFile($this->getTestFilePath(), "trading", "butter", $outputFileName);
        $outputFileContents =  null;
        if (file_exists($outputFileName)) {
            $outputFileContents = file_get_contents($outputFileName);
            @unlink($outputFileName);
        }
        $this->assertTrue(strpos($updatedContents, "butter")>-1);
        $this->assertEquals($updatedContents, $outputFileContents);
    }
}
