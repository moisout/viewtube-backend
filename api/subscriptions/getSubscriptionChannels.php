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

        switch ($_SERVER['REQUEST_METHOD']) {
          case "OPTIONS":
            http_response_code(204);

            break;
          case "GET":
            $userId = $decoded->data->id;

            if (isset($_GET['channelId'])) {

              if (isSubscribedToChannel($userId, $_GET['channelId'])) {
                http_response_code(200);
                echo json_encode(array(
                  "isSubscribed" => true,
                  "channelId" => $_GET['channelId']
                ));
              } else {
                http_response_code(200);
                echo json_encode(array(
                  "isSubscribed" => false,
                  "channelId" => $_GET['channelId']
                ));
              }
            } else {
              $subscriptions = getSubscribedChannels($userId);

              $channels = array();

              foreach ($subscriptions as $key => $value) {
                $channel = getJsonFromXmlUrl('https://www.youtube.com/feeds/videos.xml?channel_id=' . $value);

                $mappedChannel = array(
                  'author' => $channel['title'],
                  'authorId' => str_replace('yt:channel:', '', $channel['id'])
                );

                array_push($channels, $mappedChannel);
              }

              http_response_code(200);
              echo json_encode(array(
                "subscriptions" => $channels
              ));
            }
            break;
          case "PUT":
            if (isset($data->channelId)) {
              $channelId = $data->channelId;
              $userId = $decoded->data->id;

              $result = addSubscribedChannel($userId, $channelId);
              if ($result == true) {
                http_response_code(201);

                echo json_encode(array(
                  "message" => "subscription successful",
                  "channelId" => $channelId
                ));
              } else {
                http_response_code(409);

                echo json_encode(array(
                  "message" => "subscription failed",
                  "error" => "subscription already exists"
                ));
              }
            } else {
              http_response_code(400);

              echo json_encode(array(
                "message" => "channel id not set",
                "error" => "channel id not set"
              ));
            }
            break;
          case "POST":
            http_response_code(405);

            echo json_encode(array(
              "message" => "POST not allowed",
              "error" => "POST not allowed"
            ));
            break;
          case "DELETE":
            if (isset($data->channelId)) {
              $channelId = $data->channelId;
              $userId = $decoded->data->id;

              $result = removeSubscribedChannel($userId, $channelId);
              if ($result == true) {
                http_response_code(200);

                echo json_encode(array(
                  "message" => "subscription removal successful",
                  "channelId" => $channelId
                ));
              } else {
                http_response_code(409);

                echo json_encode(array(
                  "message" => "subscription removal failed",
                  "error" => "subscription doesn't exist"
                ));
              }
            } else {
              http_response_code(400);

              echo json_encode(array(
                "message" => "channel id not set",
                "error" => "channel id not set"
              ));
            }
            break;
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
