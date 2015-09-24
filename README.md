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
}
```

