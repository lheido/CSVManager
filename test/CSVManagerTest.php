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
  
  function testExtrac99kNoErrors() {
    $data = $this->csvManager->extract();
    $errors = $this->csvManager->getErrors();
    $this->AssertEquals(array(), $errors);
  }
  
  function testCSVLineGetterOkWithDataAttributes() {
    $data = $this->csvManager->extract();
    for ($i = 1; $i < 4; $i++) {
      print_r("--------------"."\n");
      print_r($data[$i]->guid."\n");
      print_r($data[$i]->first."\n");
      print_r($data[$i]->last."\n");
      print_r($data[$i]->email."\n");
      print_r($data[$i]->phone."\n");
      print_r($data[$i]->state."\n");
      print_r($data[$i]->sentence."\n");
      print_r("--------------"."\n");
    }
  }
  
}