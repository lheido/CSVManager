<?php
/**
 * 
 */
class CSVManagerTest extends PHPUnit_Framework_TestCase {
  
  public function setUp(){
    require_once "../CSVManager.class.php";
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
    $file = 'test99999line.csv';
    $csvManager = new CSVManager($file);
    
    $data = $csvManager->extract();
    $errors = $csvManager->getErrors();
    $this->AssertEquals(array(), $errors);
  }
  
}