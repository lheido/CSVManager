<?php
/**
 * 
 */
class CSVManagerTest extends PHPUnit_Framework_TestCase {
  
  public $file;
  public $csvManager;
  
  public function setUp(){
    require_once "../CSVManager.class.php";
    $this->file = 'test99999line.csv';
    $this->csvManager = new CSVManager($this->file);
  }
  
  public function testSanitize() {
    $input  = "42 Région ,;?~:{}=+-*/%$[]()<>«»'\" de France àéîôêç";
    $output = "_42_region_de_france_aeioec";
    $this->AssertEquals($output, CSVManager::sanitize($input));
  }
  
  public function testUnderScoreToCamelCase() {
    $input  = "_42_region_perdue_de_france";
    $output = "_42RegionPerdueDeFrance";
    $this->AssertEquals($output, CSVManager::underscroreToCamelCase($input));
  }
  
  function testExtrac99kNoErrorsNoCallback() {
    $data = $this->csvManager->extract();
    $errors = $this->csvManager->getErrors();
    $this->AssertEquals(array(), $errors);
  }
  
  function testExtractWithCallback() {
    $count = 0;
    $this->csvManager->extract(function($line, $row) use (&$count){
      $count += 1;
    });
    $this->AssertEquals(99999, $count);
  }
  
  function testExtractWithCallbackNoAnonymous() {
    $this->csvManager->extract(array($this, 'extractCallbackTest'));
  }
  
  function extractCallbackTest($line, $row) {
    if ($row == 1) {
      $this->AssertEquals($row, $line->row);
    }
  }
  
  function testCSVLineGetterOkWithDataAttributes() {
    $ok = true;
    $data = $this->csvManager->extract();
    for ($i = 1; $i < 4; $i++) {
      try {
        $guid = $data[$i]->guid;
        $first = $data[$i]->first;
        $last = $data[$i]->last;
        $email = $data[$i]->email;
        $phone = $data[$i]->phone;
        $state = $data[$i]->data['useless state'];
        $sentence = $data[$i]->sentence;
      } catch (Exception $e) {
        $ok = false;
      }
    }
    $this->AssertEquals(true, $ok);
  }
  
  function testCSVLineGetterOkWithRowAttribute() {
    $this->csvManager->extract(function($line, $row) use (&$ok) {
      if ($row == 1) {
        $r = $line->row;
        $this->AssertEquals($row, $r);
      }
    });
  }
  
  function testCSVLineGetterOkWithErrorsAttribute() {
    $this->csvManager->extract(function($line, $row) use (&$ok) {
      if ($row == 1) {
        $e = $line->errors;
        $this->AssertEquals(array(), $e);
      }
    });
  }
  
}