<?php
require "../../vendor/autoload.php";
include_once 'mapper.php';

use voku\helper\HtmlDomParser;

class Scraper
{
  private function unescapeJsonText(string $text): string
  {
    $text = str_replace('\"', '"', $text);

    $text = str_replace('"{', '{', $text);
    $text = str_replace('}"', '}', $text);
    $text = str_replace('\/', '/', $text);
    $text = str_replace('\&', '&', $text);
    $text = str_replace('\\"', '/', $text);

    return $text;
  }

  private function scrapeVideoInfo($videoId)
  {
    $replaceBeginString = 'var ytplayer = ytplayer || {};ytplayer.config = ';
    $replaceEndString =  ';ytplayer.load = function() {yt.player.Application.create("player-api", ytplayer.config);ytplayer.config.loaded = true;};(function() {if (!!window.yt && yt.player && yt.player.Application) {ytplayer.load();}}());';

    $url = 'https://www.youtube.com/watch?v=' . $videoId;
    $dom = HtmlDomParser::file_get_html($url);

    $textResponse = $dom->getElementByTagName('body')->getElementsByTagName('script', 1)->text;

    $textResponse = str_replace($replaceBeginString, '', $textResponse);
    $textResponse = str_replace($replaceEndString, '', $textResponse);

    $unescapedText = $this->unescapeJsonText($textResponse);

    return json_decode($unescapedText, TRUE);
  }

  public function getVideoEndscreenData($videoId)
  {
    $videoData = $this->scrapeVideoInfo($videoId);
    $replaceBeginString = ')]}';

    $endscreenUrl = $videoData['args']['player_response']['endscreen']['endscreenUrlRenderer']['url'];

    $endscreenRawData = file_get_contents('https:' . $endscreenUrl);

    $endscreenRawData = str_replace($replaceBeginString, '', $endscreenRawData);
    $endscreenRawData = trim(preg_replace('/\s+/', ' ', $endscreenRawData));

    $endscreenData = $this->unescapeJsonText($endscreenRawData);

    $endscreenData = json_decode($endscreenData, TRUE);

    $mapper = new Mapper();
    $mappedEndscreenData = $mapper->mapEndscreenData($endscreenData);

    return $mappedEndscreenData;
  }
}
