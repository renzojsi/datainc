<?php
// gettopsymbols.php - Gets most actively traded stock symbols from Wall Street Journal
// Description - downloads all symbols automatically from Wall Street Journal and inserts them into MySQL db (created via datainc.sql script).
//
// Author - R. Silva
// 

// Connect to MySQL database
$db = mysql_connect('localhost', 'datauser', 'datainc');

if (!$db) {
  echo "Unable to establish connection to database server";
  exit;
}

if (!mysql_select_db('datainc', $db)) {
  echo "Unable to connect to database";
  exit;
}

// Date formats to convert to MySQL timestamps
date_default_timezone_set('America/New_York');
$datefmt = 'Y-m-d H:i:s';

// Data sources to scrape from web - Wall St Journal
$url = 'http://www.wsj.com/mdc/public/page/2_3021-activnyse-actives.html';

// Main - Find all the symbol links in the web page, extract symbol and compnay data, insert it into database after cleansing
$html = file_get_contents($url);
if(preg_match_all("/^.*class=\"linkb\".*\$/m", $html, $matches)){
   foreach ($matches[0] as $row) {
     preg_match("/>(.*) \((.*)\)/", $row, $c);
     //var_dump($c);
     if ($c[1] != '' and $c[2]!= '') {
       $symbol = mysql_real_escape_string($c[2]);
       $company = mysql_real_escape_string($c[1]);
       $symbols[] = "'$symbol', '$company'";
     }
   }
}
else{
   echo "No matches found";
}

$symbolstr = implode('),(', $symbols);
$q = "insert ignore into symbols (symbol, company) values ($symbolstr)";

$r = mysql_query($q);
$numinserts = mysql_affected_rows();

if ($r) print "success: ($numinserts) $url\n";
else print "fail: $url\n";
?>
