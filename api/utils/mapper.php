<?php
class Mapper
{
  private function cleanRedirectUrl($url)
  {
    $parts = parse_url($url);
    parse_str($parts['query'], $query);
    return $query['q'];
  }

  public function mapVideoData($data)
  {
    $mappedData = array();

    $videoDetails = $data['args']['player_response']['videoDetails'];
    $additionalDetails = $data['args']['player_response']['microformat']['playerMicroformatRenderer'];

    $mappedData = array_merge($mappedData, [
      'title' => $videoDetails['title'],
      'videoId' => $videoDetails['videoId'],
      'videoThumbnails' =>
      array(
        0 =>
        array(
          'quality' => 'String',
          'url' => 'String',
          'width' => 'Int32',
          'height' => 'Int32',
        ),
      ),
      'description' => $videoDetails['shortDescription'],
      'descriptionHtml' => '[redacted]',
      'published' => date_create((string)$additionalDetails['publishDate']),
      'publishedText' => $this->time_elapsed_string($additionalDetails['publishDate']),
      'keywords' => $videoDetails['keywords'],
      'viewCount' => $videoDetails['viewCount'],
      'likeCount' => 'Int32',
      'dislikeCount' => 'Int32',
      'paid' => 'Bool',
      'premium' => 'Bool',
      'isFamilyFriendly' => $additionalDetails['isFamilySafe'],
      'allowedRegions' => $additionalDetails['availableCountries'],
      'genre' => 'String',
      'genreUrl' => 'String',
      'author' => 'String',
      'authorId' => 'String',
      'authorUrl' => 'String',
      'authorThumbnails' =>
      array(
        0 =>
        array(
          'url' => 'String',
          'width' => 'Int32',
          'height' => 'Int32',
        ),
      ),
      'subCountText' => 'String',
      'lengthSeconds' => 'Int32',
      'allowRatings' => 'Bool',
      'rating' => 'Float32',
      'isListed' => 'Bool',
      'liveNow' => 'Bool',
      'isUpcoming' => 'Bool',
      'premiereTimestamp' => 'Int64?',
      'hlsUrl' => 'String?',
      'adaptiveFormats' =>
      array(
        0 =>
        array(
          'index' => 'String',
          'bitrate' => 'String',
          'init' => 'String',
          'url' => 'String',
          'itag' => 'String',
          'type' => 'String',
          'clen' => 'String',
          'lmt' => 'String',
          'projectionType' => 'Int32',
          'container' => 'String',
          'encoding' => 'String',
          'qualityLabel' => 'String?',
          'resolution' => 'String?',
        ),
      ),
      'formatStreams' =>
      array(
        0 =>
        array(
          'url' => 'String',
          'itag' => 'String',
          'type' => 'String',
          'quality' => 'String',
          'container' => 'String',
          'encoding' => 'String',
          'qualityLabel' => 'String',
          'resolution' => 'String',
          'size' => 'String',
        ),
      ),
      'captions' =>
      array(
        0 =>
        array(
          'label' => 'String',
          'languageCode' => 'String',
          'url' => 'String',
        ),
      ),
      'recommendedVideos' =>
      array(
        0 =>
        array(
          'videoId' => 'String',
          'title' => 'String',
          'videoThumbnails' =>
          array(
            0 =>
            array(
              'quality' => 'String',
              'url' => 'String',
              'width' => 'Int32',
              'height' => 'Int32',
            ),
          ),
          'author' => 'String',
          'lengthSeconds' => 'Int32',
          'viewCountText' => 'String',
        ),
      )
    ]);
    return $mappedData;
  }

  public function mapEndscreenData($data)
  {
    $mappedData = array();
    foreach ($data['elements'] as $key => $value) {
      $element = $value['endscreenElementRenderer'];
      $mappedElement = array();

      $elementStyle = $element['style'];

      switch ($elementStyle) {
        case 'CHANNEL':
          $subCount = '0';
          if (array_key_exists('hovercardButton', $element)) {
            $subCount = $element['hovercardButton']['subscribeButtonRenderer']['shortSubscriberCountText']['runs'][0]['text'];
          } else if (array_key_exists('subscribersText', $element)) {
            $subCount = str_replace(' subscribers', '', $element['subscribersText']['runs'][0]['text']);
          }
          $mappedElement = array_merge($mappedElement, [
            'type' => 'channel',
            'author' => $element['title']['simpleText'],
            'description' => $element['metadata']['runs'][0]['text'],
            'authorId' => $element['endpoint']['browseEndpoint']['browseId'],
            'subCount' => $subCount,
            'authorThumbnails' => $element['image']['thumbnails'],
            'dimensions' => [
              'left' => $element['left'],
              'top' => $element['top'],
              'width' => $element['width'],
              'aspectRatio' => $element['aspectRatio']
            ],
            'timing' => [
              'start' => $element['startMs'],
              'end' => $element['endMs']
            ]
          ]);
          break;
        case 'VIDEO':
          $mappedElement = array_merge($mappedElement, [
            'type' => 'video',
            'title' => $element['title']['simpleText'],
            'videoId' => str_replace('/watch?v=', '', $element['endpoint']['urlEndpoint']['url']),
            'videoUrl' => $element['endpoint']['urlEndpoint']['url'],
            'videoThumbnails' => $element['image']['thumbnails'],
            'viewCountText' => $element['metadata']['runs'][0]['text'],
            'lengthText' => $element['videoDuration']['runs'][0]['text'],
            'dimensions' => [
              'left' => $element['left'],
              'top' => $element['top'],
              'width' => $element['width'],
              'aspectRatio' => $element['aspectRatio']
            ],
            'timing' => [
              'start' => $element['startMs'],
              'end' => $element['endMs']
            ]
          ]);
          break;
        case 'WEBSITE':
          $mappedElement = array_merge($mappedElement, [
            'type' => 'website',
            'title' => $element['title']['simpleText'],
            'websiteUrl' => $this->cleanRedirectUrl($element['endpoint']['urlEndpoint']['url']),
            'websiteThumbnails' => $element['image']['thumbnails'],
            'dimensions' => [
              'left' => $element['left'],
              'top' => $element['top'],
              'width' => $element['width'],
              'aspectRatio' => $element['aspectRatio']
            ],
            'timing' => [
              'start' => $element['startMs'],
              'end' => $element['endMs']
            ]
          ]);
          break;
        case 'PLAYLIST':
          $mappedElement = array_merge($mappedElement, [
            'type' => 'playlist',
            'title' => $element['title']['simpleText'],
            'playlistUrl' => $element['endpoint']['urlEndpoint']['url'],
            'playlistThumbnails' => $element['image']['thumbnails'],
            'authorText' => $element['metadata']['runs'][0]['text'],
            'playlistLengthText' => $element['playlistLength']['runs'][0]['text'],
            'dimensions' => [
              'left' => $element['left'],
              'top' => $element['top'],
              'width' => $element['width'],
              'aspectRatio' => $element['aspectRatio']
            ],
            'timing' => [
              'start' => $element['startMs'],
              'end' => $element['endMs']
            ]
          ]);
          break;
        default:
          $mappedElement = array_merge($mappedElement, [
            'devObject' => $element
          ]);
          break;
      }

      array_push($mappedData, $mappedElement);
    }
    return $mappedData;
  }

  public function time_elapsed_string($datetime, $full = false)
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
}
