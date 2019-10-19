<?php
include_once './config/database.php';
include_once './config/secret.php';
require "../vendor/autoload.php";

use \Firebase\JWT\JWT;

header("Access-Control-Allow-Origin: https://viewtube.eu");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$secret_key = $secret_jwt_key;
$jwt = null;
$databaseService = new DatabaseService();
$conn = $databaseService->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];

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

            echo json_encode(array(
                "message" => "Access granted: " . $jwt
            ));
        } catch (Exception $e) {
            http_response_code(401);

            echo json_encode(array(
                "message" => "Access denied.",
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
