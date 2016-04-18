<?php
// getallsymbols.php
// Description - downloads all symbol files automatically from Nasdaq and inserts them into MySQL db (created via datainc.sql script).
//               Inserts data into MySQL, without duplicate symbols.
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

// Data sources to automatically scrape from the web.
$urls[] = 'http://www.nasdaq.com/screening/companies-by-industry.aspx?exchange=NASDAQ&render=download';
$urls[] = 'http://www.nasdaq.com/screening/companies-by-industry.aspx?exchange=NYSE&render=download';
$urls[] = 'http://www.nasdaq.com/screening/companies-by-industry.aspx?exchange=AMEX&render=download';

// Data cleansing.  Translate all symbols into Yahoo Finance symbols in order to query RSS feeds.
function translate($symbol) { // translate to Yahoo symbols
  if (strpos($symbol, '.') !== FALSE) $symbol = str_replace(".", "-", $symbol);
  if (strpos($symbol, 'WS') !== FALSE) $symbol = str_replace("S", "T", $symbol);
  if (strpos($symbol, 'WT-') !== FALSE) $symbol = str_replace("WT-", "WT", $symbol);
  if (strpos($symbol, '.CL') !== FALSE) $symbol = str_replace(".CL", "", $symbol);
  if (strpos($symbol, '^') !== FALSE) $symbol = str_replace("^", "-P", $symbol);
  return($symbol);
}

// Main - loop through data sources, extract csv row info, insert into database
foreach ($urls as $url) {
  $symbols = $s = array();
  $csv = file_get_contents($url);
  $rows = explode("\n",$csv);
  unset($rows[0]);
  foreach($rows as $row) {
    $s = str_getcsv($row);
    if (isset($s[0])) $symbol = mysql_real_escape_string(strtoupper(trim($s[0])));
    if (isset($s[1])) $company = mysql_real_escape_string(trim($s[1]));
    if ($symbol != '' and $company != '') {
      $symbol = translate($symbol);
      $symbols[] = "'$symbol', '$company'";
    }
  }

  $symbolstr = implode('),(', $symbols);
  $q = "insert ignore into symbols (symbol, company) values ($symbolstr)";
  $r = mysql_query($q);
  $numinserts = mysql_affected_rows();

  if ($r) print "success: ($numinserts) $url\n";
  else print "fail: $url\n";
}
?>
