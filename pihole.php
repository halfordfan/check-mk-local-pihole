#!/usr/bin/env php
<?php

# A CheckMK local check for Pi-Hole servers
# Written in PHP because, well, I know it and Pi-Hole runs on it.

# Read the API key from setupVars.conf
$fh = fopen("/etc/pihole/setupVars.conf", "r");
while ( $line = fgets($fh) ) {
  $confVar = explode("=", $line);
  $confArray[$confVar[0]] = trim($confVar[1]);
}
fclose($fh);

$summary = file_get_contents('http://localhost/admin/api.php?summary&auth=' . $confArray['WEBPASSWORD']);
if ( ! $summary ) {
  die("Failed to contact Pi-Hole API endpoint.  Aborting!");
}

# Decode the summary JSON to an array so that we can work with it.
$summaryArray = json_decode($summary, TRUE);

# Pi-Hole enable/disable status (warn on disable)
if ( $summaryArray['status'] == "enabled" ) {
  print '0 "Pi-Hole Status" enabled=1 Pi-Hole ad blocking is enabled' . PHP_EOL;
} else {
  print '1 "Pi-Hole Status" enabled=0 Pi-Hole ad blocking is disabled' . PHP_EOL;
}

# Give us some blocking stats to put in RRD.
$metricsArray=array();
foreach ( array('dns_queries_today','ads_blocked_today','ads_percentage_today') as $key ) {
  $metricsArray[$key] = $key . '=' . str_replace(",","",$summaryArray[$key]);
}
print('0 "Pi-Hole Stats" ' . implode("|",$metricsArray) . ' Pi-Hole stats') . PHP_EOL;

# Now lets see when Gravity was last updated.  WARN if over a week since
# Pi-Hole uses that as a default schedule.
$lastgravity=date('U') - $summaryArray['gravity_last_updated']['absolute'];
print('P "Pi-Hole Gravity" daysold=' . round($lastgravity/86400,0) . ';7 Pi-Hole Gravity lists were updated ');
switch(TRUE){
  case 60 > $lastgravity:
    $number = $lastgravity;
    $unit = "second";
    break;
  case 3600 >= $lastgravity && $lastgravity > 60 :
    $number=round($lastgravity/60,0);
    $unit = "minute";
    break;
  case 86400 >= $lastgravity && $lastgravity > 3600 :
    $number = round($lastgravity/3600,0);
    $unit = "hour";
    break;
  default:
    $number = round($lastgravity/86400,0);
    $unit = "day";
    break;
}
# For proper English
if ( $number == 1 ) {
  print "$number $unit" . " ago" . PHP_EOL;
} else {
  print "$number $unit" . "s ago" . PHP_EOL;
}

# Check for available Pi-Hole updates in core, web, and FTL.
$update=0;
$message="Pi-Hole is up to date";
$metricsArray=array();
$updates = file_get_contents('http://localhost/admin/api.php?versions&auth=' . $confArray['WEBPASSWORD']);
$updatesArray = json_decode($updates, TRUE);
foreach ( array('core_update', 'web_update', 'FTL_update') as $key ) {
  if ( $updatesArray[$key] ) {
    $metricsArray[]=$key . '=1';
    $update=1;
    $message="At least one Pi-Hole update is available (run 'pihole -up')";
  } else {
    $metricsArray[]=$key . '=0';
  }
}
print($update . ' "Pi-Hole Update" ' . implode("|",$metricsArray) . ' ' . $message) . PHP_EOL;
?>
