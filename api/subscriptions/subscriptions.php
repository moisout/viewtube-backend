<?php
function getSubscribedChannels($userId): array
{
  $databaseService = new DatabaseService();
  $conn = $databaseService->getConnection();

  $table_name = 'subscriptions';

  $query = "SELECT channelId FROM " . $table_name . " WHERE fkUserId = ?";

  $stmt = $conn->prepare($query);
  $stmt->bindParam(1, $userId);
  $stmt->execute();

  $num = $stmt->rowCount();

  $subscriptions = array();

  if ($num > 0) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      array_push($subscriptions, $row['channelId']);
    }
  }

  return $subscriptions;
}

function addSubscribedChannel($userId, $channelId)
{
  $databaseService = new DatabaseService();
  $conn = $databaseService->getConnection();

  $table_name = 'subscriptions';

  $query = "SELECT channelId FROM " . $table_name . " WHERE fkUserId = :userid AND channelId = :channelid";

  $stmt = $conn->prepare($query);
  $stmt->bindParam(':userid', $userId);
  $stmt->bindParam(':channelid', $channelId);
  $stmt->execute();

  $num = $stmt->rowCount();

  if ($num > 0) {
    return false;
  } else {
    $query = "INSERT INTO " . $table_name . " (channelId, fkUserId) VALUES (:channelid, :userid)";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':userid', $userId);
    $stmt->bindParam(':channelid', $channelId);
    $stmt->execute();

    return true;
  }
}

function removeSubscribedChannel($userId, $channelId)
{
  $databaseService = new DatabaseService();
  $conn = $databaseService->getConnection();

  $table_name = 'subscriptions';

  $query = "SELECT id, channelId FROM " . $table_name . " WHERE fkUserId = :userid AND channelId = :channelid";

  $stmt = $conn->prepare($query);
  $stmt->bindParam(':userid', $userId);
  $stmt->bindParam(':channelid', $channelId);
  $stmt->execute();

  $num = $stmt->rowCount();

  if ($num > 0) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $rowId = $row['id'];
      $query = "DELETE FROM " . $table_name . " WHERE " . $table_name . ".id = :rowid";

      $stmt = $conn->prepare($query);
      $stmt->bindParam(':rowid', $rowId);
      $stmt->execute();
    }

    return true;
  } else {
    return false;
  }
}

function getJsonFromXmlUrl($url)
{
  $queryUrl = $url;
  $raw = file_get_contents($queryUrl);

  $xml = simplexml_load_string($raw);
  $json = json_encode($xml);

  $result = json_decode($json, TRUE);
  return $result;
}
