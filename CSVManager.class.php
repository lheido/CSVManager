<?php

/**
 * 
 */
class CSVManager {
  
  const ERROR_COLUMN_NUMBER    = 'bad column number';
  const ERROR_FIELD_VALIDATION = 'field validation failed';
  
  protected $filePath;
  protected $validators;
  protected $errors;
  protected $data;
  protected $headers;
  protected $delimiter;
  protected $headerUseCamelCase;
  protected $inputEncoding;
  
  public function __construct($filePath, $encoding='UTF-8') {
    $this->filePath = $filePath;
    $this->validators = array();
    $this->errors = array();
    $this->data = array();
    $this->headers = array();
    $this->delimiter = ';';
    $this->headerUseCamelCase = true;
    $this->inputEncoding = $encoding;
  }
  
  public function setInputEncoding($encoding) {
    $this->inputEncoding = $encoding;
  }
  
  public function getFilePath() {
    return $this->filePath;
  }
  
  public function setFilePath($filePath) {
    $this->filePath = $filePath;
  }
  
  public function getDelimiter() {
    return $this->delimiter;
  }
  
  public function setDelimiter($delimiter) {
    $this->delimiter = $delimiter;
  }
  
  public function getErrors() {
    return $this->errors;
  }
  
  public function getHeaders() {
    return $this->headers;
  }
  
  public function headerUseCamelCase($bool) {
    $this->headerUseCamelCase = $bool;
  }
  
  /**
   * @param validators array  (column => validator)
   *                          column must be match with csv column name or int
   *                          validator is a string or array (for POO), 
   *                            ex: 'is_numeric' or array($this, 'method').
   */
  public function setValidators(array $validators) {
    $this->validators = $validators;
  }
  
  public function getValidators() {
    return $this->validators;
  }
  
  public function extract(Callable $callback=null) {
    $this->data = array();
    $this->errors = array();
    if (($handle = fopen($this->filePath, 'r')) !== false) {
      $row = 0;
      while (($line = fgetcsv($handle, 0, $this->delimiter)) !== false) {
        //get headers
        if ($row == 0) {
          $this->header = $line;
          // create header mapping
          $this->headerMap = array_map(array('CSVManager', 'sanitize'), $this->header);
          if ($this->headerUseCamelCase) {
            $this->headerMap = array_map(array('CSVManager', 'underscroreToCamelCase'), $this->headerMap);
          }
          $this->headerMap = array_combine($this->headerMap, $this->header);
        }
        if ($row > 0) {
          $errors = array();
          // check column number
          $bad_column_number = false;
          if (count($line) != count($this->header)) {
            $bad_column_number = true;
            $error = (object) array(
              'type'          => self::ERROR_COLUMN_NUMBER,
              'expected'      => count($this->header),
              'columnNumber' => count($line)
            );
            $errors[] = $error;
          }
          //check each fields
          foreach ($line as $key => $value) {
            if (isset($this->validators[$key])) {
              $result = call_user_func($this->validators[$key], $value);
              if (!$result) {
                $error = (object) array(
                  'type'   => self::ERROR_FIELD_VALIDATION,
                  'column' => $this->header[$key],
                  'value'  => $value
                );
                $errors[] = $error;
              }
            }
          }
          $data = array_map('trim', $line);
          if ($bad_column_number == false) {
            $data = array_combine($this->header, $data);
          }
          $csvLine = new CSVLine($row, $data, $this->headerMap, $errors);
          if ($callback == null) {
            $callback = array($this, 'onAddLine');
          }
          call_user_func($callback, $csvLine, $row);
        }
        $row += 1;
      }
      fclose($handle);
    }
    return $this->data;
  }
  
  public function onAddLine(CSVLine $line, $row) {
    $this->data[$row] = $line;
    if (!$line->isValid()) {
      $this->errors[$row] = $line->errors;
    }
  }
  
  /**
   * convert string to utf8.
   */
  public function toUtf8($content, $baseEncoding = null) {
    $encoding = (empty($baseEncoding)) ? $this->inputEncoding : $baseEncoding;
    if (!empty($content) && !mb_detect_encoding($content, 'UTF-8', true)) {
      return iconv($encoding, 'UTF-8', $content);
    }
    return $content;
  }
  
  /**
   * Slugify and replace space by underscore (default).
   * @from http://stackoverflow.com/questions/2955251/php-function-to-make-slug-url-string
   * @return string
   * */
  public static function sanitize($str, $spaceReplacement='_') {
    // replace non letter or digits by -
    $text = preg_replace('~[^\\pL\d]+~u', $spaceReplacement, $str);
    // trim
    $text = trim($text, $spaceReplacement);
    // transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    // lowercase
    $text = strtolower($text);
    // remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);
    // add $spaceReplacement at the beginning chain if it starts with a number.
    $text = preg_replace('~^([0-9])~', $spaceReplacement.'${1}', $text);
    return $text;
  }
  
  public static function underscroreToCamelCase($str) {
    return preg_replace_callback("/(\w{1})_(.)/", function($c) {return $c[1].strtoupper($c[2]);}, $str);
  }
  
}

class CSVLine {
  
  public $row;
  public $data;
  public $errors;
  public $headerMap;
  
  public function __construct($row, $data, $headerMap, $errors = array()) {
    $this->row = $row;
    $this->data = $data;
    $this->errors = $errors;
    $this->headerMap = $headerMap;
  }
  
  public function isValid() {
    return empty($this->errors);
  }
  
  public function getRow() {
    return $this->row;
  }
  
  public function __get($name) {
    return $this->data[$this->headerMap[$name]];
  }
  
  public function __isset($name) {
    return isset($this->data[$this->headerMap[$name]]);
  }
  
  public function __unset($name) {
    unset($this->data[$this->headerMap[$name]]);
  }
}