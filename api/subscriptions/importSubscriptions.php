<?php
include_once '../config/database.php';
include_once '../config/secret.php';
include_once './subscriptions.php';
require "../../vendor/autoload.php";

use \Firebase\JWT\JWT;

header("Access-Control-Allow-Origin: " . $corsOrigin);
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == "OPTIONS") {
    http_response_code(204);
} else {
    $secret_key = $secret_jwt_key;
    $jwt = null;

    $data = json_decode(file_get_contents("php://input"));

    $headers = apache_request_headers();

    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];

        $jwtArr = explode(" ", $authHeader);

        $jwt = "";

        if (count($jwtArr) >= 1) {
            $jwt = $jwtArr[1];
        } else {
            $jwt = $jwtArr[0];
        }

        if ($jwt) {
            try {
                $decoded = JWT::decode($jwt, $secret_key, array('HS256'));

                $userId = $decoded->data->id;

                if (isset($_FILES['subscription_manager'])) {

                    $data = getJsonFromXmlUrl($_FILES['subscription_manager']['tmp_name']);
                    if ($data) {

                        $subscriptions = importSubscriptionsFromFile($data);

                        addSubscribedChannels($userId, $subscriptions);

                        http_response_code(200);
                        echo json_encode(array(
                            "message" => $subscriptions
                        ));
                    } else {
                        http_response_code(400);

                        echo json_encode(array(
                            "message" => "invalid file",
                            "error" => "error parsing file"
                        ));
                    }
                } else {
                    http_response_code(400);

                    echo json_encode(array(
                        "message" => "no file",
                        "error" => "file not uploaded"
                    ));
                }
            } catch (Exception $e) {
                http_response_code(401);

                echo json_encode(array(
                    "message" => "Access denied",
                    "error" => $e->getMessage()
                ));
            }
        } else {
            http_response_code(401);

            echo json_encode(array(
                "message" => "Access denied",
                "error" => "Invalid token"
            ));
        }
    } else {
        http_response_code(401);

        echo json_encode(array(
            "message" => "Access denied",
            "error" => "Token not set"
        ));
    }
}
