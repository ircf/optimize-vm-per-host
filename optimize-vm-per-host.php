<?php

/**
 * Optimize VM per host repartition program
 * @author IRCF
 */

// Config
if (!file_exists(dirname(__FILE__) . '/config.php')) die("config.php file not found\n");
require_once 'config.php';

// Functions

// @see https://stackoverflow.com/questions/28510864/find-n-digit-in-a-number
function ndigit($in, $digit, $base=10) {
  if( $in == 0 ) { return 0; }
  if( $in < 0 ) { $in *= -1; }
  $len = (int)floor(log($in, $base)) + 1;
  if( $digit > $len ) { return 0; }
  $rpos = $len - $digit;
  $tmp = $in - ($in % pow($base, $rpos));
  return ($tmp % pow($base, $rpos+1)) / pow($base, $rpos);
}

// @see http://php.net/manual/fr/function.stats-standard-deviation.php
if (!function_exists('stats_standard_deviation')) {
  function stats_standard_deviation(array $a, $sample = false) {
    $n = count($a);
    if ($n === 0) {
        trigger_error("The array has zero elements", E_USER_WARNING);
        return false;
    }
    if ($sample && $n === 1) {
        trigger_error("The array has only 1 element", E_USER_WARNING);
        return false;
    }
    $mean = array_sum($a) / $n;
    $carry = 0.0;
    foreach ($a as $val) {
        $d = ((double) $val) - $mean;
        $carry += $d * $d;
    };
    if ($sample) {
       --$n;
    }
    return sqrt($carry / $n);
  }
}

// Main program
// Loop through all possible configs
$max = $hosts**count($servers);
$fitness = array();
for ($i=0; $i<$max; $i++){
  // Compute usage
  $usage = array('cpu' => array(), 'ram' => array());
  for ($h=0; $h<$hosts; $h++){
    $usage['cpu'][$h] = 0;
    $usage['ram'][$h] = 0;
  }
  $j = 0;
  foreach($servers as $name => $config){
    $h = ndigit($i, $j, $hosts);
    $usage['cpu'][$h] += $config['cpu'];
    $usage['ram'][$h] += $config['ram'];
    $j++;
  }
  // Compute fitness
  $deviation = array(
  'cpu' => stats_standard_deviation($usage['cpu']),
  'ram' => stats_standard_deviation($usage['ram']),
  );
  $fitness[$i] = $deviation['cpu']**2 + $deviation['ram']**2;
}

// Display result
$best = current(array_keys($fitness, min($fitness)));
$j = 0;
foreach($servers as $name => $config){
  echo $name . " : " . ndigit($best, $j, $hosts) ."\n";
  $j++;
}
