<?php
include_once './config/database.php';
include_once './config/secret.php';

header("Access-Control-Allow-Origin: " . $corsOrigin);
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Access-Control-Allow-Origin, Authorization, X-Requested-With, Origin");

if ($_SERVER['REQUEST_METHOD'] == "OPTIONS") {
  http_response_code(204);
} else {

  $data = json_decode(file_get_contents("php://input"));

  // Captcha verification

  $url = 'https://captcheck.netsyms.com/api.php';
  $captcheckData = [
    'session_id' => $data->captcheck_session_code,
    'answer_id' => $data->captcheck_selected_answer,
    'action' => "verify"
  ];

  $options = [
    'http' => [
      'header' => "Content-type: application/x-www-form-urlencoded\r\n",
      'method' => 'POST',
      'content' => http_build_query($captcheckData)
    ]
  ];

  $context = stream_context_create($options);
  $result = file_get_contents($url, false, $context);
  $resp = json_decode($result, TRUE);

  if (!$resp['result']) {
    http_response_code(403);

    echo json_encode(array(
      "message" => "CAPTCHA not verified: " . $resp['msg']
    ));
  } else {
    // Registration

    $username = '';
    $password = '';
    $conn = null;

    $pw_min_length = 6;

    $databaseService = new DatabaseService();
    $conn = $databaseService->getConnection();

    if (isset($data->username) && isset($data->password)) {
      $username = $data->username;
      $password = $data->password;

      if (strlen($username) > 0 && strlen($password) >= $pw_min_length) {

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
            "message" => "Username \"$username\" already taken",
            "username" => $username
          ));
        }
      } else if (strlen($username) <= 0 && strlen($password) > 0) {
        http_response_code(400);

        echo json_encode(array("message" => "Username can't be empty"));
      } else if (strlen($username) > 0 && strlen($password) <= 0) {
        http_response_code(400);

        echo json_encode(array("message" => "Password can't be empty"));
      } else if (strlen($username) > 0 && strlen($password) < $pw_min_length) {
        http_response_code(400);

        echo json_encode(array("message" => "Password is too short - at least $pw_min_length characters"));
      } else {
        http_response_code(400);

        echo json_encode(array("message" => "Username or password can't be empty"));
      }
    } else {
      http_response_code(400);

      echo json_encode(array("message" => "Username or password missing"));
    }
  }
}
