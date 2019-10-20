<?php
include_once './config/database.php';
include_once './config/secret.php';
require "../vendor/autoload.php";

use \Firebase\JWT\JWT;

header("Access-Control-Allow-Origin: " . $corsOrigin);
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == "OPTIONS") {
  http_response_code(204);
} else {

  $username = '';
  $password = '';

  $databaseService = new DatabaseService();
  $conn = $databaseService->getConnection();

  $data = json_decode(file_get_contents("php://input"));

  if (isset($data->username) && isset($data->password)) {
    $username = $data->username;
    $password = $data->password;

    if (strlen($username) > 0 && strlen($password) > 0) {

      $table_name = 'users';

      $query = "SELECT id, username, password FROM " . $table_name . " WHERE username = ? LIMIT 0,1";

      $stmt = $conn->prepare($query);
      $stmt->bindParam(1, $username);
      $stmt->execute();
      $num = $stmt->rowCount();

      if ($num > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $id = $row['id'];
        $username = $row['username'];
        $password2 = $row['password'];
        $timestamp = new DateTime();
        $expiration_timestamp = new DateTime('+1 day');

        if (password_verify($password, $password2)) {
          $secret_key = $secret_jwt_key;
          $issuer_claim = "ViewTube";
          $audience_claim = "https://viewtube.eu";
          $issuedat_claim = $timestamp->format('U');
          $expiration_claim = $expiration_timestamp->format('U');
          $token = array(
            "iss" => $issuer_claim,
            "aud" => $audience_claim,
            "iat" => $issuedat_claim,
            "exp" => $expiration_claim,
            "data" => array(
              "id" => $id,
              "username" => $username
            )
          );

          http_response_code(200);

          $jwt = JWT::encode($token, $secret_key);
          echo json_encode(
            array(
              "message" => "Successful login.",
              "jwt" => $jwt,
              "username" => $username
            )
          );
        } else {

          http_response_code(401);
          echo json_encode(array("message" => "Login failed."));
        }
      }
    } else if (strlen($username) <= 0 && strlen($password) > 0) {
      http_response_code(400);

      echo json_encode(array("message" => "Username can't be empty"));
    } else if (strlen($username) > 0 && strlen($password) <= 0) {
      http_response_code(400);

      echo json_encode(array("message" => "Password can't be empty"));
    } else {
      http_response_code(400);

      echo json_encode(array("message" => "Username or password can't be empty"));
    }
  } else {
    http_response_code(400);

    echo json_encode(array("message" => "Username or password missing"));
  }
}
