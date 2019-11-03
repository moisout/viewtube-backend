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
  { }

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
}
