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

function set_cache_array($array, $userId)
{
  $filename = dirname(__FILE__) . '\cache\subscriptions' . $userId . '.php';
  if (file_exists($filename)) {
    unlink($filename);
  }
  $file = fopen($filename, 'w');
  $date = new DateTime('+15 minutes');
  fwrite($file, '<?php $date = ' . $date->format('U') . '; $subscriptionFeedArray = ' . var_export($array, true) . ';');
  fclose($file);

  return $filename;
}

function get_cache_array($userId)
{
  $filename = dirname(__FILE__) . '\cache\subscriptions' . $userId . '.php';
  if (file_exists($filename)) {

    include_once('cache/subscriptions' . $userId . '.php');

    if (date_create_from_format('U', $date) > date_create()) {
      return $subscriptionFeedArray;
    }
  }

  return false;
}

function importSubscriptionsFromFile($data)
{
  $subscriptions = $data['body']['outline']['outline'];
  $subscriptionIds = array();

  foreach ($subscriptions as $key => $value) {
    $sUrl = (string) $value['@attributes']['xmlUrl'];
    $sId = str_replace('https://www.youtube.com/feeds/videos.xml?channel_id=', '', $sUrl);
    array_push($subscriptionIds, $sId);
  }
  return $subscriptionIds;
}

function isSubscribedToChannel($userId, $channelId)
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

  return $num > 0;
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

function addSubscribedChannels($userId, $channelIds)
{
  $databaseService = new DatabaseService();
  $conn = $databaseService->getConnection();

  $table_name = 'subscriptions';

  $channelIdsSql = array();

  for ($i = 0; $i < count($channelIds); $i++) {
    if ($i == 0) {
      array_push($channelIdsSql, 'channelId = :channelid' . $i);
    } else {
      array_push($channelIdsSql, 'OR channelId = :channelid' . $i);
    }
  }

  $query = "SELECT channelId FROM " . $table_name . " WHERE fkUserId = :userid AND (" . implode(' ', $channelIdsSql) . ")";

  $stmt = $conn->prepare($query);
  $stmt->bindParam(':userid', $userId);
  foreach ($channelIds as $key => $value) {
    $stmt->bindParam(':channelid' . $key, $value);
  }
  $stmt->execute();

  $num = $stmt->rowCount();

  $existingSubscriptionIds = array();

  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $channelId = $row['channelId'];
    array_push($existingSubscriptionIds, $channelId);
  }

  $subscriptionsToAdd = array_diff($channelIds, $existingSubscriptionIds);

  $subscriptionsToAddSql = array();

  for ($i = 0; $i < count($subscriptionsToAdd); $i++) {
    if ($i == count($subscriptionsToAdd) - 1) {
      array_push($subscriptionsToAddSql, '(:channelid' . $i . ', :userid)');
    } else {
      array_push($subscriptionsToAddSql, '(:channelid' . $i . ', :userid),');
    }
  }

  $query = "INSERT INTO " . $table_name . " (channelId, fkUserId) VALUES " . implode(' ', $subscriptionsToAddSql);

  $stmt = $conn->prepare($query);
  $stmt->bindParam(':userid', $userId);
  foreach ($subscriptionsToAdd as $key => $value) {
    $stmt->bindParam(':channelid' . $key, $value);
  }
  $stmt->execute();
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
      ], [
        'quality' => 'medium',
        'url' => $thumbnailUrl . $element['id'] . '/mqdefault.jpg',
        'width' => 320,
        'height' => 180
      ]
    ],
    'description' => '',
    'descriptionHtml' => '',
    'published' => $element['published'],
    'publishedText' => time_elapsed_string($element['published']),
    'viewCount' => 0,
    'lengthSeconds' => 0,
    'author' => $element['author']['name'],
    'authorId' => str_replace('https://www.youtube.com/channel/', '', $element['author']['uri'])
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
  if (getHttpResponseCode($url) == "200") {
    $raw = file_get_contents($url);

    $xml = simplexml_load_string($raw);
    $json = json_encode($xml);

    $result = json_decode($json, TRUE);
    return $result;
  } else {
    return false;
  }
}

function getHttpResponseCode($url)
{
  $headers = get_headers($url);
  return substr($headers[0], 9, 3);
}
