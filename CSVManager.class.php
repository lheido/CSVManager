<?php

/**
 * 
 */
class CSVManager {
  
  const ERROR_COLUMN_NUMBER    = 'Bad column number';
  const ERROR_FIELD_VALIDATION = 'Field validation failed';
  
  protected $filePath;
  protected $validators;
  protected $errors;
  protected $data;
  protected $headers;
  protected $delimiter;
  protected $headerUseCamelCase;
  
  public function __construct($filePath) {
    $this->filePath = $filePath;
    $this->validators = array();
    $this->errors = array();
    $this->data = array();
    $this->headers = array();
    $this->delimiter = ';';
    $this->headerUseCamelCase = true;
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
  
  public function setErrorMessage(stdClass &$error) {
    switch ($error->type) {
      case self::ERROR_COLUMN_NUMBER:
        $error->message = strtr("@expected columns expected but @column_number found.", array(
          '@expected'      => $error->expected,
          '@column_number' => $error->column_number
        ));
        break;
        
      case self::ERROR_FIELD_VALIDATION:
        $error->message = strtr("Error column @column: '@value' is invalid", array(
          '@column' => $error->column,
          '@value'  => $error->value
        ));
        break;
        
      default:
        break;
    }
  }
  
  public function extract() {
    $this->data = array();
    $this->errors = array();
    if (($handle = fopen($this->filePath, 'r')) !== false) {
      $row = 0;
      while (($line = fgetcsv($handle, 0, $this->delimiter)) !== false) {
        //get headers
        if ($row == 0) {
          $this->headers = array_map(array(get_called_class(), 'sanitize'), $line);
          if ($this->headerUseCamelCase) {
            $this->headers = array_map(array(get_called_class(), 'underscroreToCamelCase'), $line);
          }
        }
        if (count($line) != count($this->headers)) {
          $error = (object) array(
            'line'          => $line,
            'row'           => $row,
            'type'          => self::ERROR_COLUMN_NUMBER,
            'expected'      => count($this->headers),
            'columnNumber' => count($line)
          );
          $this->errors[$row][] = $error;
          //fatal error => return empty array.
          return array();
        }
        if ($row > 0) {
          $errors = array();
          foreach ($line as $key => $value) {
            //si $value n'est pas vide et
            //si il y a une fonction de validation prévue pour cette colonne
            if ($value && isset($this->validators[$key])) {
              $result = call_user_func($this->validators[$key], $value);
              if (!$result) {
                $error = (object) array(
                  'type'   => self::ERROR_FIELD_VALIDATION,
                  'column' => $this->headers[$key],
                  'value'  => $value
                );
                $errors[] = $error;
              }
            }
          }
          $data = array_map('trim', $line);
          if (count($this->headers) > 0) {
            $data = (object) array_combine($this->headers, $data);
          }
          $csvLine = new CSVLine($data, $errors);
          
          $this->onAddLine($csvLine, $row);
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
  
  public $data;
  public $dataIsArray;
  public $errors;
  
  public function __construct($data, $errors = array()) {
    $this->data = $data;
    $this->dataIsArray = is_array($this->data);
    $this->errors = $errors;
  }
  
  public function isValid() {
    return empty($this->errors);
  }
  
  public function __get($name) {
    return $this->dataIsArray ? $this->data[$name] : $this->data->{"$name"};
  }
  
  public function __isset($name) {
    if ($this->dataIsArray) {
      return isset($this->data[$name]);
    }
    return isset($this->data->{"$name"});
  }
  
  public function __unset($name) {
    if ($this->dataIsArray) {
      unset($this->data[$name]);
    } 
    else {
      unset($this->data->{"$name"});
    }
  }
}