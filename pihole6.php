#!/usr/bin/env php
<?php

# A CheckMK local check for Pi-Hole servers
# Written in PHP because, well, I know it and Pi-Hole runs on it.
# Update to v6 by wmtech-1 with minor edits - thank you!

# Get the sess:ion ID (sid) to authorize us...
# Set to an empty string if you do not have a password set.
$MP = 'YOUR-PIHOLE-APP-PASSWORD';
$MP = '';

if ( $MP == '' ) {
    $url = 'http://localhost/api/auth';
    $payload = json_encode( array( "password" => $MP ) );
    $ch = curl_init($url);

    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    curl_setopt($ch,CURLOPT_POST,1);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$payload);

    $result = json_decode(curl_exec($ch), TRUE);
    curl_close($ch);

    if ( ! $result['session']['valid'] ) {
        die("Failed to contact Pi-Hole API endpoint.  Aborting!");
    }

    $sid = '?sid=' . $result['session']['sid'];

} else {
    $sid = '';
}

$status = file_get_contents('http://localhost/api/dns/blocking' . $sid);
# Decode the summary JSON to an array so that we can work with it.
$statusArray = json_decode($status, TRUE);

# Pi-Hole enable/disable status (warn on disable)
if ( isset($statusArray['blocking']) && $statusArray['blocking'] == "enabled" ) {
    print '0 "Pi-Hole Status" enabled=1 Pi-Hole ad blocking is enabled' . PHP_EOL;
} else {
    print '1 "Pi-Hole Status" enabled=0 Pi-Hole ad blocking is disabled' . PHP_EOL;
}

$summary = file_get_contents('http://localhost/api/stats/summary' . $sid);

# Decode the summary JSON to an array so that we can work with it.
$summaryArray = json_decode($summary, TRUE);

if ( !isset ($summaryArray['queries']) ) {
    die("Failed to contact Pi-Hole API endpoint.  Aborting!");
}

# Give us some blocking stats to put in RRD.
$metricsArray=array();
$metricsArray['dns_queries'] = 'dns_queries=' . str_replace(",","",$summaryArray['queries']['total']);
$metricsArray['ads_blocked'] = 'ads_blocked=' . str_replace(",","",$summaryArray['queries']['blocked']);
$metricsArray['ads_percentage'] = 'ads_percentage=' . round(str_replace(",","",$summaryArray['queries']['percent_blocked']),1);
$metricsArray['domains_blocked'] = 'domains_blocked=' . str_replace(",","",$summaryArray['gravity']['domains_being_blocked']);
$metricsArray['clients'] = 'clients=' . str_replace(",","",$summaryArray['clients']['total']);

print('0 "Pi-Hole Stats" ' . implode("|",$metricsArray) . ' Pi-Hole stats') . PHP_EOL;

# Now lets see when Gravity was last updated.  WARN if over 8 days, CRIT if over 15 days since
# Pi-Hole uses that as a default schedule.
$lastgravity=date('U') - $summaryArray['gravity']['last_update'];
print('P "Pi-Hole Gravity" daysold=' . round($lastgravity/86400,0) . ';8;15 Pi-Hole Gravity lists were updated ');
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
$updates = file_get_contents('http://localhost/api/info/version' . $sid);
$updatesArray = json_decode($updates, TRUE);
foreach ( array('core', 'web', 'ftl') as $key ) {
    if ( $updatesArray['version'][$key]['local']['hash'] !== $updatesArray['version'][$key]['remote']['hash'] ) {
        $metricsArray[]=$key . '=1';
        $update=1;
        $message="At least one Pi-Hole update is available (run 'pihole -up')";
    } else {
        $metricsArray[]=$key . '=0';
    }
}
print($update . ' "Pi-Hole Update" ' . implode("|",$metricsArray) . ' ' . $message) . PHP_EOL;
?>
