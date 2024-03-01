<?php
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
chdir('./EasyPost PHP Scripts');

$client = new \EasyPost\EasyPostClient($_ENV['TEST_KEY']);

$json = file_get_contents('./misc.JSON');
$json_data = json_decode($json, true);

$properties = [
    "created_at",
    "messages",
    "status",
    "tracking_code",
    "updated_at",
    "batch_id",
    "batch_status",
    "batch_message",
    "id",
    "order_id",
    "postage_label",
    "tracker",
    "selected_rate",
    "scan_form",
    "usps_zone",
    "refund_status",
    "mode",
    "fees",
    "object",
    "rates",
    "insurance",
    "forms",
    "verifications"
];

$nest = ["to_address", "from_address", "return_address", "buyer_address", "parcel"];

foreach ($properties as $x) {
    unset($json_data[$x]);
}

foreach ($nest as $y) {
    foreach ($properties as $i) {
        unset($json_data[$y][$i]);
    }
}

$json_data = array_filter($json_data);

if (count($json_data["customs_info"]) > 0) {
    foreach ($properties as $x) {
        unset($json_data["customs_info"][$x]);
    }
    ;
    foreach ($json_data["customs_info"]["customs_items"] as $c) {
        foreach ($properties as $i) {
            unset($json_data[$c][$i]);
        }
    }
} else {
    unset($json_data["customs_info"]);
}
;

if (count($json_data["options"]["print_custom"]) > 0) {
    unset($json_data["options"]["print_custom"]);
}

try {
    $shipment = $client->shipment->create([
        "from_address" => $json_data["from_address"],
        "to_address" => $json_data["to_address"],
        "return_address" => $json_data["return_address"],
        "buyer_address" => $json_data["buyer_address"],
        "parcel" => $json_data["parcel"],
        "customs_info" => $json_data["customs_info"],
        "options" => $json_data["options"],
        "is_return" => $json_data["is_return"],
        "reference" => $json_data["reference"],
        // "carrier_accounts" => [""],
        // // "service" => "yada yada yada"
    ]);

    echo $shipment;

    $created = json_decode($shipment, true);

    if ($created["postage_label"] == null) {
        if (count($created["messages"]) > 0) {
            foreach ($created["messages"] as $message) {
                echo "Type: " . $message["type"] . "\n";
                echo "Carrier: " . $message["carrier"] . "\n";
                echo "Message: " . $message["message"] . "\n\n";
            }
        } else {
            echo "No rate_errors returned.";
        }

        if (count($created["rates"]) > 0) {
            foreach ($created["rates"] as $r) {
                echo $r["carrier"] . " - " . $r["service"] . "\n";
                echo $r["rate"] . " - " . $r["id"] . "\n\n";
            };
            echo $created["id"];
        } else {
            echo "No rates available.";
            exit();
        }

        $user = readline("\n\nEnter the rate you wish to purchase, or press enter to quit: ");

        if (str_contains($user, "rate_")) {
            $boughtShipment = $client->shipment->buy($created["id"],['rate' => ['id' => $user]]);
            shell_exec('open ' . escapeshellarg($boughtShipment["postage_label"]["label_url"]));
            // Return or display whatever you desire
        } else {
            // Return or display whatever you desire
        }

    } else {
        shell_exec('open ' . escapeshellarg($created["postage_label"]["label_url"]));
        // Return or display whatever you desire
    }
} catch (\EasyPost\Exception\Api\ApiException $error) {
    echo $error->prettyPrint();
}