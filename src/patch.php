<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../secrets.php';

use \Ovh\Api;

if (ENDPOINT === null || ENDPOINT === "" || ENDPOINT === "CHANGEME" ||
    APP_KEY === "" || APP_SECRET === "" || CONSUMER_KEY === "") {
    echo "You've not finished setting up your secrets.php file!" . PHP_EOL;
    die(1);
}

$ovh = new Api(APP_KEY, APP_SECRET, ENDPOINT, CONSUMER_KEY);

/**
 * Validates that a server is active.
 * @param Api $api OVH API object
 * @param string $server Server name
 * @return bool Returns true if service status is OK
 * @throws JsonException
 */
function validateStatus(Api $api, string $server): bool {
    return $api->get("/dedicated/server/" . $server . "/serviceInfos")["status"] === "ok";
}

/**
 * Validates that a server belongs to the GAME commercial range.
 * @param Api $api OVH API objet
 * @param string $server Server name
 * @return bool Returns true if the server is a GAME server
 * @throws JsonException
 */
function validateCommercialRange(Api $api, string $server): bool {
    return str_contains($api->get("/dedicated/server/" . $server)["commercialRange"], "GAME");
}

try {
    // Attempt to verify that we can access the API with the credentials provided
    $authCheck = $ovh->get("/me");
    if (array_key_exists("class", $authCheck) && $authCheck["class"] === "Client::Unauthorized") {
        echo "Received 401 Unauthorized, unable to continue." . PHP_EOL;
        die(1);
    }

    // Obtain list of all servers in the account
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

    $count = count($serversToPatch);

    if ($count < 1) {
        echo "Found 0 matching servers, nothing to do!" . PHP_EOL;
        die(0);
    }

    echo "Matching servers: " . $count . PHP_EOL . PHP_EOL;

    foreach ($serversToPatch as $server) {
        echo "-------- " . $server . " --------" . PHP_EOL;

        // Obtain subnets routed to each server
        $routedNets = $ovh->get("/ip?routedTo.serviceName=" . $server . "&version=4");

        foreach ($routedNets as $net) {
            echo PHP_EOL;

            echo "Processing net " . $net . PHP_EOL;

            // Obtain all IP addresses for the subnet
            $ips = $ovh->get("/ip/" . urlencode($net) . "/game");

            foreach ($ips as $ip) {
                echo "Processing IP " . $ip . " in net " . $net . "... ";

                // Disable GAME firewall for IP address
                $ovh->put("/ip/" . urlencode($net) . "/game/" . urlencode($ip), array(
                    "firewallModeEnabled" => false
                ));

                echo "done!" . PHP_EOL;
            }

            echo PHP_EOL;
        }

        echo "---------------------------------------------" . PHP_EOL . PHP_EOL;
    }

    echo "Finished execution." . PHP_EOL;
} catch (JsonException $e) {
    echo $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString();
}
