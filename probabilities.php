<?php
// probabilities.php - Uses news headlines data to determine probability of positive/negative sentiment per stock
// Description - Uses simple sentiment scoring algorithm to determine positive or negative sentiment per headline, per stock, and 
//               calculates corresponding probabilities.
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

//  Super simple sentiment dictionaries of positive/negative words.  If text contains word, sentiment score is increased/decreased by 1
$good = array('good', 'buy', 'long', 'upgrade', 'beat','improve', 'profitable', 'efficient');
$bad = array('bad', 'sell', 'short', 'head winds', 'downgrade', 'amend', 'deficit', 'forbear', 'delist', 'default', 'felony', 'deterioration', 'termination');

// Select all news headlines in database
$q = "select * from headlines";
$r = mysql_query($q);

$num = $pos = $neg = $ts = $ds = array();

// Main - Loop through all headlines and determine aggregate positive and negative sentiment scores and total headlines, per stock.
if ($r) while ($i = mysql_fetch_array($r)) {
  $s = $i['symbol'];
  $t = $i['title'];
  $d = $i['description'];
  $time = $i['time'];

  $tscore = sentiment_score($t);
  $dscore = sentiment_score($d);
 
  if (isset($num["$s"])) { $num["$s"] = $num["$s"] + 1; } else { $num["$s"] = 1; }

  if ($tscore > 0) {
    if (isset($pos["$s"])) { $pos["$s"] = $pos["$s"] + 1; } else { $pos["$s"] = 1; }
  } else if ($tscore < 0) {
    if (isset($neg["$s"])) { $neg["$s"] = $neg["$s"] + 1; } else { $neg["$s"] = 1; }
  }
}

// For all symbols, print out symbol, positive headline probability, negative headline probability, in "CSV" format
foreach (array_keys($num) as $s) {
  $posprob = $negprob = 0;
  if (isset($pos[$s])) $posprob = $pos[$s]/$num[$s];
  if (isset($neg[$s])) $negprob = $neg[$s]/$num[$s];
  print "$s,$num[$s],$posprob,$negprob\n";
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
