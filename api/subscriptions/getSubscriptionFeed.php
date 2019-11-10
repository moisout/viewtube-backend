<?php
include_once '.././config/database.php';
include_once '.././config/secret.php';
include_once './subscriptions.php';
require "../../vendor/autoload.php";

use \Firebase\JWT\JWT;

header("Access-Control-Allow-Origin: " . $corsOrigin);
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
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

        $cachedVideos = get_cache_array($userId);

        $newestVideos = array();

        if ($cachedVideos == false) {

          $subscriptions = getSubscribedChannels($userId);

          foreach ($subscriptions as $key => $value) {
            $queryUrl = 'https://www.youtube.com/feeds/videos.xml?channel_id=' . $value;
            $videos = getJsonFromXmlUrl($queryUrl);
            if ($videos) {

              $videoEntries = $videos['entry'];

              foreach ($videoEntries as $key => $value) {
                $videoEntries[$key]['id'] = str_replace('yt:video:', '', $videoEntries[$key]['id']);
              }

              $mappedVideos = mapSubscriptionVideoFeed($videoEntries);

              $newestVideos = array_merge($newestVideos, $mappedVideos);
            }
          }

          function sortByDate($a, $b)
          {
            if ($a['published'] == $b['published']) {
              return 0;
            }
            return ($a['published'] > $b['published']) ? -1 : 1;
          }

          usort($newestVideos, 'sortByDate');

          set_cache_array($newestVideos, $userId);
        } else {
          $newestVideos = $cachedVideos;
        }

        if (isset($_GET['limit'])) {
          $newestVideos = array_slice($newestVideos, 0, $_GET['limit']);
        } else {
          $newestVideos = array_slice($newestVideos, 0, 40);
        }

        echo json_encode(array(
          "subscriptions" => $newestVideos
        ));
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
