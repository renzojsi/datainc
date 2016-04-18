<?php
// sentiment.php - Generates sentiment scores for all symbols and all headlines.
// Description - Output in CSV format to generate plot 1.
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

// Super simple sentiment dictionaries of positive/negative words.  If text contains word, sentiment score is increased/decreased by 1
$good = array('good', 'buy', 'long', 'upgrade', 'beat','improve', 'profitable', 'efficient');
$bad = array('bad', 'sell', 'short', 'head winds', 'downgrade', 'amend', 'deficit', 'forbear', 'delist', 'default', 'felony', 'deterioration', 'termination');

// Select all news headlines in database
$q = "select * from headlines";
$r = mysql_query($q);

$ts = $ds = array();

// Main - Loop through all headlines and determine aggregate sentiment scores per stock.
if ($r) while ($i = mysql_fetch_array($r)) {
  $s = $i['symbol'];
  $t = $i['title'];
  $d = $i['description'];
  $time = $i['time'];

  $tscore = sentiment_score($t);
  $dscore = sentiment_score($d);
 
  if (isset($ts["$s"])) { $ts["$s"] = $ts["$s"] + $tscore; } else { $ts["$s"] = $tscore; }
  if (isset($ds["$s"])) { $ds["$s"] = $ds["$s"] + $dscore; } else { $ds["$s"] = $dscore; }
}

// For all symbols, print out symbol, headline total sentiment score, description total sentiment score, per stock, in "CSV" format
foreach (array_keys($ts) as $s) {
  print "$s,$ts[$s],$ds[$s]\n";
}
 
// Determine sentiment score of text (+/- 1 per positive/negative word found)
function sentiment_score($text) {
  global $good, $bad;
  $s = 0;

  $t = strtolower($text);
  foreach ($good as $g) {
    if(preg_match("[$g]", $t)) { $s = $s + 1; } 
  }
  foreach ($bad as $b) {
    if(preg_match("[$b]", $t)) { $s = $s - 1; } 
  }

  return($s);
}
?>
