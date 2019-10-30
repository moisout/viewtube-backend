<?php
include_once '../config/secret.php';
include_once '../utils/scraper.php';
require "../../vendor/autoload.php";

header("Access-Control-Allow-Origin: " . $corsOrigin);
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == "OPTIONS") {
  http_response_code(204);
} else {

  if (isset($_GET['videoId'])) {

    $data = json_decode(file_get_contents("php://input"));

    $scraper = new Scraper();
    $response = $scraper->getVideoEndscreenData($_GET['videoId']);

    if (!is_null($response)) {
      http_response_code(200);

      echo json_encode($response);
    } else {
      http_response_code(500);

      echo json_encode(array(
        "message" => "Error retrieving data",
        "error" => "Endscreen data could not be retrieved"
      ));
    }
  } else {
    http_response_code(400);

    echo json_encode(array(
      "message" => "VideoId not set",
      "error" => "Parameter videoId not set"
    ));
  }
}
