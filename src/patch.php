<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../secrets.php';

use \Ovh\Api;

$ovh = new Api(APP_KEY, APP_SECRET, ENDPOINT, CONSUMER_KEY);

/**
 * Validate server status
 * @param Api $api OVH API objet
 * @param string $server Server name
 * @return bool Returns true if OK
 * @throws JsonException
 */
function validateStatus(Api $api, string $server): bool {
    return $api->get("/dedicated/server/" . $server . "/serviceInfos")["status"] === "ok";
}

/**
 * Validate server commercial range
 * @param Api $api OVH API objet
 * @param string $server Server name
 * @return bool Returns true if is GAME server
 * @throws JsonException
 */
function validateCommercialRange(Api $api, string $server): bool {
    return str_contains($api->get("/dedicated/server/" . $server)["commercialRange"], "GAME");
}

try {
    $servers = $ovh->get("/dedicated/server");
    $serversToPatch = [];

    echo "Total servers in account: " . count($servers) . PHP_EOL;

    foreach ($servers as $server) {
        if (validateStatus($ovh, $server) && validateCommercialRange($ovh, $server)) {
            echo "Found valid server: " . $server . PHP_EOL;
            /** @noinspection PhpArrayPushWithOneElementInspection */
            array_push($serversToPatch, $server);
        }
    }

    echo "Matching servers: " . count($serversToPatch) . PHP_EOL;

    foreach ($serversToPatch as $server) {
        echo PHP_EOL;
        echo "-------- " . $server . " --------" . PHP_EOL;
        $routedNets = $ovh->get("/ip?routedTo.serviceName=" . $server . "&version=4");

        foreach ($routedNets as $net) {
            echo "Processing net " . $net . PHP_EOL;
            $ips = $ovh->get("/ip/" . urlencode($net) . "/game");

            foreach ($ips as $ip) {
                echo "Processing IP " . $ip . " in net " . $net . "... ";
                $ovh->put("/ip/" . urlencode($net) . "/game/" . urlencode($ip), array(
                    "firewallModeEnabled" => false
                ));
                echo " done!" . PHP_EOL;
            }
        }


        echo "---------------------------------------------" . PHP_EOL;
    }
} catch (JsonException $e) {
    echo $e->getMessage();
}
