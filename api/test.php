<?php
require "../vendor/autoload.php";
include_once "./utils/scraper.php";

use voku\helper\HtmlDomParser;

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$url = 'https://www.youtube.com/watch?v=clI1FoygWiU';

$scraper = new Scraper();

$response = $scraper->getVideoEndscreenData('clI1FoygWiU');

echo json_encode($response);
