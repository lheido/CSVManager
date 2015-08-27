<?php
namespace CSVManager;

/**
 * 
 * */
class CSVManager {
  
  protected $filePath;
  protected $validators;
  protected $errors;
  protected $data;
  protected $headers;
  protected $delimiter;
  protected $headerUseCamelCase;
  protected $errorNumberColumnMessage;
  protected $errorValidationMessage;
  protected $errorReplacementCallback;
  
  public function __construct($filePath) {
    $this->filePath = $filePath;
    $this->validators = array();
    $this->errors = array();
    $this->data = array();
    $this->headers = array();
    $this->delimiter = ';';
    $this->headerUseCamelCase = true;
    $this->errorNumberColumnMessage = "@expected columns expected but @column_number found.";
    $this->errorValidationMessage = "Error column @column: '@value' is invalid";
    $this->errorReplacementCallback = 'strtr';
  }
  
  public function getFilePath() {
    return $this->filePath;
  }
  
  public function setFilePath($filePath) {
    $this->filePath = $filePath;
  }
  
  public function headerUseCamelCase($bool) {
    $this->headerUseCamelCase = $bool;
  }
  
  public function setErrorReplacementCallback($callback) {
    $this->errorReplacementCallback = $callback;
  }
  
  public function setErrorNumberColumnMessage($str) {
    $this->errorNumberColumnMessage = $str;
  }
  
  public function setErrorValidationMessage($str) {
    $this->errorValidationMessage = $str;
  }
  
  /**
   * @param validators array  (column => validator)
   *                          column must be match with csv column name or int
   *                          validator is a string or array (for POO), 
   *                            ex: 'is_numeric' or ($this, 'method').
   * */
  public function setValidators(array $validators) {
    $this->validators = $validators;
  }
  
  public function extract() {
    $this->data = array();
    $this->errors = array();
    if (($handle = fopen($filename, 'r')) !== false) {
			$row = 0;
			while (($line = fgetcsv($handle, 0, $this->delimiter)) !== false) {
			  //get headers
			  if ($row == 0) {
			    $this->headers = array_map($this->sanitize, $line);
			    if ($this->headerUseCamelCase) {
			      $this->headers = array_map($this->underscroreToCamelCase, $line);
			    }
			  }
				if (count($line) != count($this->headers)) {
          $this->errors[$row][] = (object) array(
            'line' => $line,
            'row' => $row,
            'error' => 'Bad Column number',
            'message' => call_user_func($this->errorReplacementCallback, 
              $this->errorNumberColumnMessage, array(
                '@expected' => count($this->headers),
                '@column_number' => count($line)
              )
            )
          );
					return false;
				}
        if ($row > 0) {
          foreach ($line as $key => $value) {
            //si $value n'est pas vide et
            //si il y a une fonction de validation prévue pour cette colonne
            if ($value && isset($this->validators[$key])) {
              $result = call_user_func($this->validators[$key], $value);
              if (!$result) {
                $this->errors[$row][] = (object) array(
                  'line' => $line,
                  'row' => $row,
                  'error' => 'Column validation failed',
                  'message' => call_user_func($this->errorReplacementCallback, 
                    $this->errorValidationMessage, array(
                      '@column' => $key,
                      '@value' => $value
                    )
                  )
                );
              }
            }
          }
          //if errors[row] don't exist then data[row] <- line.
          //if count(headers) > 0 then line -> stdClass
          if (!isset($this->errors[$row])) {
            $data = array_map('trim', $line);
            if (count($this->headers) > 0) {
              $data = (object) array_combine($this->headers, $data);
            }
            $this->data[$row] = $data;
          }
        }
        $row += 1;
			}
			fclose($handle);
    }
    return $this->data;
  }
  
  /**
   * Replace space by underscore (default).
   * Transliterate $str if transliterator_transliterate function exist.
   * @return string
   * */
  public static function sanitize($str, $spaceReplacement='_') {
    if (function_exists('transliterator_transliterate')) {
      $rules = "Any-Latin; NFD; [:Nonspacing Mark:] Remove; NFC; [:Punctuation:] Remove; Lower();";
      return str_replace(' ', $spaceReplacement, transliterator_transliterate($rules, $str));
    }
    $search = array(' ','ï','î','É','é','È','è','Ê','ê','À','à','Ç','ç','Â','â','¬','@','&','ù','Ù','$','!','#',"'",'"');
    $replace = array($spaceReplacement,'i','i','E','e','E','e','E','e','A','a','C','c','A','a','e','','','u','U','','','',"",'','');
    return str_replace($search, $replace, $str);
  }
  
  public static function underscroreToCamelCase($str) {
    return preg_replace_callback("/_(.)/", function($c) {return strtoupper($c[1]);}, $str);
  }
  
}