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
