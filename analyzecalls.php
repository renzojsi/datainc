<?php
// analyzecalls.php
// Description - downloads all call files automatically and inserts them into MySQL db (created via datainc.sql script).
//               Inserts data into MySQL 100 records at a time.
//
// Author - R. Silva
// 
$db = mysql_connect('localhost', 'datauser', 'datainc');

if (!$db) {
  echo "Unable to establish connection to database server";
  exit;
}

if (!mysql_select_db('datainc', $db)) {
  echo "Unable to connect to database";
  exit;
}

// Data sources to scrape from web
$urls[] = 'https://data.nola.gov/api/views/28ec-c8d6/rows.csv?accessType=DOWNLOAD';
$urls[] = 'https://data.nola.gov/api/views/rv3g-ypg7/rows.csv?accessType=DOWNLOAD';
$urls[] = 'https://data.nola.gov/api/views/5fn8-vtui/rows.csv?accessType=DOWNLOAD';
$urls[] = 'https://data.nola.gov/api/views/jsyu-nz5r/rows.csv?accessType=DOWNLOAD';
$urls[] = 'https://data.nola.gov/api/views/w68y-xmk6/rows.csv?accessType=DOWNLOAD';

// Date formats to convert to MySQL timestamps
$datefmt = 'Y-m-d H:i:s';
date_default_timezone_set('America/New_York');

// Main - loop through each file 100 rows at a time
foreach ($urls as $url) {
  print "url = $url\n";
  $csv = file_get_contents($url);
  $rows = explode("\n",$csv);
  unset($rows[0]);
  $chunks = array_chunk($rows, 100);

  foreach ($chunks as $chunk) {
    print "here\n";
    $calls = array();
    foreach($chunk as $row) {

      $s = str_getcsv($row);

      if (isset($s[0])) $item = mysql_real_escape_string(strtoupper(trim($s[0])));
      if (isset($s[1])) $type = mysql_real_escape_string(trim($s[1]));
      if (isset($s[2])) $typetext = mysql_real_escape_string(trim($s[2]));
      if (isset($s[3])) $priority = mysql_real_escape_string(trim($s[3]));
      if (isset($s[4])) $mapx = mysql_real_escape_string(trim($s[4]));
      if (isset($s[5])) $mapy = mysql_real_escape_string(trim($s[5]));
      if (isset($s[6])) $created = mysql_real_escape_string(date($datefmt, strtotime(trim($s[6]))));
      if (isset($s[7])) $dispatched = mysql_real_escape_string(date($datefmt, strtotime(trim($s[7]))));
      if (isset($s[8])) $arrived = mysql_real_escape_string(date($datefmt, strtotime(trim($s[8]))));
      if (isset($s[9])) $closed = mysql_real_escape_string(date($datefmt, strtotime(trim($s[9]))));
      if (isset($s[10])) $disposition = mysql_real_escape_string(trim($s[10]));
      if (isset($s[11])) $dispositiontext = mysql_real_escape_string(trim($s[11]));
      if (isset($s[12])) $address = mysql_real_escape_string(trim($s[12]));
      if (isset($s[13])) $zip = mysql_real_escape_string(trim($s[13]));
      if (isset($s[14])) $pdistrict = mysql_real_escape_string(trim($s[14]));
      if (isset($s[15])) {
        $location = mysql_real_escape_string(trim($s[15]));
        preg_match("/\((.*),\s+(.*)\)/", $location, $c);
        $longitude = $c[1];
        $latitude = $c[2];
      }

      if ($item != '') {
        $calls[] = "'$item','$type','$typetext','$priority','$mapx','$mapy','$created','$dispatched','$arrived','$closed','$disposition','$dispositiontext','$address','$zip','$pdistrict','$longitude','$latitude'";
      }

    }
    $callstr = implode('),(', $calls);
    $q = "insert ignore into calls (item,type,typetext,priority,mapx,mapy,created,dispatched,arrived,closed,disposition,dispositiontext,address,zip,pdistrict,longitude,latitude) values ($callstr)";
 
    $r = mysql_query($q);
    $numinserts = mysql_affected_rows();

    if ($r) print "success: ($numinserts) $url\n";
    else print "fail: $url\n";
    
  }
}
?>
