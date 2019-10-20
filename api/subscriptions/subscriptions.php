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

function mapSubscriptionVideoFeed($data)
{
  $mappedData = array_map('subscriptionVideoFeedCallback', $data);
  return $mappedData;
}

function subscriptionVideoFeedCallback($element)
{
  $thumbnailUrl = 'https://i.ytimg.com/vi/';
  $result = [
    'title' => $element['title'],
    'videoId' => $element['id'],
    'videoThumbnails' => [
      [
        'quality' => 'maxres',
        'url' => 'https://invidio.us/vi/' . $element['id'] . '/maxres.jpg',
        'width' => 1280,
        'height' => 720
      ], [
        'quality' => 'maxresdefault',
        'url' => $thumbnailUrl . $element['id'] . '/maxresdefault.jpg',
        'width' => 1280,
        'height' => 720
      ], [
        'quality' => 'sddefault',
        'url' => $thumbnailUrl . $element['id'] . '/sddefault.jpg',
        'width' => 640,
        'height' => 480
      ], [
        'quality' => 'high',
        'url' => $thumbnailUrl . $element['id'] . '/hqdefault.jpg',
        'width' => 480,
        'height' => 360
      ]
    ],
    'description' => '',
    'descriptionHtml' => '',
    'published' => $element['published'],
    'publishedText' => time_elapsed_string($element['published']),
    'viewCount' => 0,
    'lengthSeconds' => 0,
    'author' => $element['author']['name'],
    'authorId' => $element['author']['uri']
  ];
  return $result;
}

function time_elapsed_string($datetime, $full = false)
{
  $now = new DateTime;
  $ago = new DateTime($datetime);
  $diff = $now->diff($ago);

  $diff->w = floor($diff->d / 7);
  $diff->d -= $diff->w * 7;

  $string = array(
    'y' => 'year',
    'm' => 'month',
    'w' => 'week',
    'd' => 'day',
    'h' => 'hour',
    'i' => 'minute',
    's' => 'second',
  );
  foreach ($string as $k => &$v) {
    if ($diff->$k) {
      $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
    } else {
      unset($string[$k]);
    }
  }

  if (!$full) $string = array_slice($string, 0, 1);
  return $string ? implode(', ', $string) . ' ago' : 'just now';
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
