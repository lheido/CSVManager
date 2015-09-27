# CSVManager
Simple PHP class to work with csv file.

## Extract CSV file

requirement:
 - utf8 encoded file
 - first line is table header

### Example
```php
$cm = new CSVManager($filename);

// extract with ';' delimiter by default
$lines = $cm->extract();

foreach ($lines as $line) {
  // type of $line is CSVLine
  if ($line->isValid()) {
    // POO wày (use __get() magic method)
    $email = $line->email;
    // other way
    $email = $line->data['email'];
    // with complex column name : 'foo useless field'
    $field = $line->fooUselessField;
    // or
    $field = $line->data['foo useless field'];
  }
  else {
    $errors = $line->errors;
    foreach ($errors as $error) {
      switch ($error->type) {
        case CSVManager::ERROR_COLUMN_NUMBER:
          $error->message = strtr("@expected columns expected but @column_number found.", array(
            '@expected'      => $error->expected,
            '@column_number' => $error->column_number
          ));
          break;
          
        case CSVManager::ERROR_FIELD_VALIDATION:
          $error->message = strtr("Error column @column: '@value' is invalid", array(
            '@column' => $error->column,
            '@value'  => $error->value
          ));
          break;
          
        default:
          break;
      }
    }
  }
}
```

### extract callback

If you don't care about save each lines in a php array, you can use a callback function.

```php
// with anonymous function:
$cm->extract(function($line, $row) {
  // do something...
});

// or with defined function:
$cm->extract('my_callback_function');

// or in POO way
$cm->extract(array($object, 'my_callback_method'));
```