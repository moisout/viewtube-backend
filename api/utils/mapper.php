<?php
class Mapper
{
  private function cleanRedirectUrl($url)
  {
    $parts = parse_url($url);
    parse_str($parts['query'], $query);
    return $query['q'];
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
          $mappedElement = array_merge($mappedElement, [
            'type' => 'channel',
            'author' => $element['title']['simpleText'],
            'description' => $element['metadata']['runs'][0]['text'],
            'authorId' => $element['hovercardButton']['subscribeButtonRenderer']['channelId'],
            'subCount' => $element['hovercardButton']['subscribeButtonRenderer']['shortSubscriberCountText']['runs'][0]['text'],
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
      }

      array_push($mappedData, $mappedElement);
    }
    return $mappedData;
  }
}
