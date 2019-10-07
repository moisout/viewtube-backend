<?php
include_once './config/database.php';

header("Access-Control-Allow-Origin: https://viewtube.eu");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Access-Control-Allow-Origin, Authorization, X-Requested-With, Origin");

if ($_SERVER['REQUEST_METHOD'] == "OPTIONS") {
  http_response_code(204);
} else {

  $username = '';
  $password = '';
  $conn = null;

  $databaseService = new DatabaseService();
  $conn = $databaseService->getConnection();

  $data = json_decode(file_get_contents("php://input"));

  if (isset($data->username) && isset($data->password)) {
    $username = $data->username;
    $password = $data->password;

    if (strlen($username) > 0 && strlen($password) > 0) {

      $table_name = 'users';

      $usernameQuery = "SELECT id, username FROM " . $table_name . " WHERE username = ? LIMIT 0,1";

      $stmt = $conn->prepare($usernameQuery);
      $stmt->bindParam(1, $username);
      $stmt->execute();
      $num = $stmt->rowCount();

      if ($num <= 0) {
        $query = "INSERT INTO " . $table_name . "
      SET username = :username,
          password = :password";

        $stmt = $conn->prepare($query);

        $stmt->bindParam(':username', $username);

        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt->bindParam(':password', $password_hash);

        if ($stmt->execute()) {
          http_response_code(200);

          echo json_encode(array(
            "message" => "Registration successful",
            "username" => $username
          ));
        } else {
          http_response_code(400);

          echo json_encode(array("message" => "Registration failed"));
        }
      } else {
        http_response_code(409);

        echo json_encode(array(
          "message" => "Username $username already taken",
          "username" => $username
        ));
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
