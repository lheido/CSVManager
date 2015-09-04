<?php
/**
 * 
 */
class CSVManagerTest extends PHPUnit_Framework_TestCase {
  
  public function setUp(){
    require_once "../CSVManager.class.php";
  }
  
  public function testSanitizeOk() {
    $input  = "Région perdue de France¬";
    $output = "region_perdue_de_franceEUR";
    $this->AssertEquals($output, CSVManager::sanitize($input));
  }
  
  public function testUnderScoreToCamelCase() {
    $input  = "region_perdue_de_france";
    $output = "regionPerdueDeFrance";
    $this->AssertEquals($output, CSVManager::underscroreToCamelCase($input));
  }
  
  function testExtracNoErrors() {
    
  }
  
}