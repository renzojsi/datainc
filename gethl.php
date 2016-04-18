<?php
// gethl.php   - Get Headlines
// Description - downloads all RSS news from Yahoo Finance for all US symbols and inserts them into MySQL db (created via datainc.sql script).
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

// MySQL date formats
$datefmt = 'Y-m-d H:i:s';
date_default_timezone_set('America/New_York');

// Base URL to get per symbol RSS news headlines in XML format
$urlbase = 'https://feeds.finance.yahoo.com/rss/2.0/headline?region=US&lang=en-US';

// Gets all symbols in database
function get_symbols() {
  $symbols = array();
  $q = "select distinct symbol from symbols where symbol";
  $r = mysql_query($q);

  if (!$r) return(0);

  while ($i = mysql_fetch_array($r)) {
    $symbols[] = strtoupper($i['symbol']);
  }

  return $symbols;
}

$symbols = get_symbols();

// Main - For each symbol in the database, get RSS news headlines in XML from Yhaoo, extract necessary data, insert into database.
if ($symbols) foreach ($symbols as $s) {
  $url = "${urlbase}&s=$s";
  $hls = file_get_contents($url);

  $obj = new SimpleXMLElement($hls);
  $items = $obj->channel->item;

  foreach ($items as $i) {
    $values = array($s);
    $values[] = mysql_real_escape_string($i->title);
    $values[] = mysql_real_escape_string($i->description);
    $values[] = mysql_real_escape_string($i->link);
    $values[] = mysql_real_escape_string($i->guid);
    $values[] = mysql_real_escape_string($i->pubDate);
    $values[] = date($datefmt, strtotime($i->pubDate));
    $allvalues[] = "'" . implode("','", $values) . "'";
  }

  $allvaluestr = implode('),(', $allvalues);
  $q = "insert ignore into headlines (symbol, title, link, description, guid, pubDate, time) values ($allvaluestr)";

  $r = mysql_query($q);
  $numinserts = mysql_affected_rows();

  if ($r) print "$s successful ($numinserts)\n";
  else print "$s failed\n";
}
?>
