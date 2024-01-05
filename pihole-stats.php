#!/usr/bin/php
<?php

try {
    $db = new SQLite3('/etc/pihole/pihole-FTL.db', SQLITE3_OPEN_READONLY);
    $db->enableExceptions(true);
    $result = $db->query("SELECT count(1) as queriesperminute, ifnull(round(avg(reply_time),5)*1000,0) AS averagereplyms FROM query_storage WHERE timestamp > strftime('%s') - 60");
    if ( $db->lastErrorCode() != 0 ) {
        // Something broke
        file_put_contents('/tmp/SQLite3error.' . date("U"), $db->lastErrorMsg());
    }
    $output=$result->fetchArray();
    $db->close();
} catch (Exception $e) {
    file_put_contents('/tmp/SQLite3error.' . date("U"), $e->getMessage());
}

print 'P "Pi-hole stats" queriesperminute=' . $output['queriesperminute'] . "|averagereplyms=" . $output['averagereplyms'] . " Pi-Hole statistics for the last minute" . PHP_EOL;

?>
