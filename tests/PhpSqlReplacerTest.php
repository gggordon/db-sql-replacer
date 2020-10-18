<?php

namespace PhpSqlReplacer\Tests;

use PHPUnit\Framework\TestCase;
use PhpSqlReplacer\PhpSqlReplacer;
use PhpSqlReplacer\Exceptions\SqlReplacerException;

class PhpSqlReplacerTest extends TestCase
{
    protected $replacer;

    protected function setup()
    {
        parent::setup();
        $this->replacer = new PhpSqlReplacer();
    }
    private function getTestFilePath()
    {
        return dirname(__FILE__).'/data/sample.sql';
    }
    
    public function testEnsureFileExists()
    {
        try {
            $this->replacer
                 ->extractSqlValuesFromFile($this->getTestFilePath().'wrong');
            $this->assertTrue(false);
        } catch (SqlReplacerException $e) {
            $this->assertTrue(true);
        }
        
        try {
            $this->replacer
                 ->extractSqlValuesFromFile($this->getTestFilePath());
            $this->assertTrue(true);
        } catch (SqlReplacerException $e) {
            $this->assertTrue(false);
        }
    }

    

    public function testExtractSqlValuesWithColumnNames()
    {
        $actualValues = $this->replacer
                             ->extractSqlValuesFromFile(
                                 $this->getTestFilePath(),
                                 null,
                                 true
                              );
        $this->assertEquals(12, count($actualValues));
    }

    public function testExtractSqlValuesWithoutColumnNames()
    {
        $actualValues = $this->replacer
                             ->extractSqlValuesFromFile(
                                 $this->getTestFilePath()
                              );
        $this->assertEquals(8, count($actualValues));
    }

    public function testExtractSqlValuesFromFileMatchingValue()
    {
        $actualValues = $this->replacer
                             ->extractSqlValuesFromFile(
                                 $this->getTestFilePath(),
                                 "none"
                              );
        $this->assertEquals(0, count($actualValues));
        $actualValues = $this->replacer
                             ->extractSqlValuesFromFile(
                                 $this->getTestFilePath(),
                                 "trading"
                              );
        $this->assertEquals(2, count($actualValues));
    }

    public function testReplaceValue()
    {
        $contents = file_get_contents($this->getTestFilePath());
        $updatedContents = $this->replacer
                                ->replaceValue($contents, "trading", "butter");
        $this->assertTrue(strpos($updatedContents, "butter")>-1);
    }

    public function testReplaceValueFromFile()
    {
        $outputFileName = $this->getTestFilePath().'.out';
        $updatedContents = $this->replacer
                                ->replaceValueFromFile(
                                    $this->getTestFilePath(),
                                    "trading",
                                    "butter",
                                    $outputFileName
                                );
        $outputFileContents =  null;
        if (file_exists($outputFileName)) {
            $outputFileContents = file_get_contents($outputFileName);
            @unlink($outputFileName);
        }
        $this->assertTrue(strpos($updatedContents, "butter")>-1);
        $this->assertEquals($updatedContents, $outputFileContents);
    }
}
