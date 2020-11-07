<?php


namespace Koenvh\PublicBroadcasting\Broadcaster;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Response;
use Koenvh\PublicBroadcasting\StreamInformation;

class FranceTv extends Broadcaster
{
    function retrieve(string $url): StreamInformation
    {
        $response = $this->client->request("GET", $url);
        $body = $response->getBody()->getContents();
        preg_match_all('/videoId":"([^"]+)"/', $body, $output_array);
        $videoId = $output_array[1][0];

        $response = $this->client->request("GET", "https://player.webservices.francetelevisions.fr/v1/videos/$videoId?country_code=NL&w=720&h=405&version=5.40.1&domain=www.france.tv&device_type=desktop&browser=firefox&browser_version=79&os=windows&os_version=10.0&diffusion_mode=tunnel_first&video_product_id=1838133");
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);

        $response = $this->client->request("GET", $data["video"]["token"]);
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);

        $video = $data["url"];

        $response = $this->client->request("GET", $video, [
            'allow_redirects' => [
                'track_redirects' => true
            ]
        ]);
        $actualUrl = $response->getHeader(\GuzzleHttp\RedirectMiddleware::HISTORY_HEADER)[0];
        $body = $response->getBody()->getContents();
        $xml = simplexml_load_string($body);
        $baseUrl = pathinfo($actualUrl, PATHINFO_DIRNAME) . "/" . $xml->Period->BaseURL;
        $adaptationSet = null;
        foreach ($xml->Period->AdaptationSet as $item) {
            if ((string)$item["contentType"] != "text") continue;
            $adaptationSet = $item;
        }
        $initialization = (string)$adaptationSet->SegmentTemplate["initialization"];
        $media = (string)$adaptationSet->SegmentTemplate["media"];
        $representationId = (string)$adaptationSet->Representation["id"];
        $timeStart = (int)$adaptationSet->SegmentTemplate->SegmentTimeline->S[0]["t"];
        $timeInterval = (int)$adaptationSet->SegmentTemplate->SegmentTimeline->S[0]["d"];
        $timeRounds = (int)$adaptationSet->SegmentTemplate->SegmentTimeline->S[0]["r"];

        $initialization = str_replace('$RepresentationID$', $representationId, $initialization);
        $media = str_replace('$RepresentationID$', $representationId, $media);

        $vtt = "WEBVTT \n\n";

        $vttParts = array_fill(0, $timeRounds, "");

        $fulfilled = function (Response $response, $index) use (&$vttParts) {
            $content = strstr($response->getBody()->getContents(), "<?xml");
            $vtt = "";
            $xml = simplexml_load_string($content);
            foreach ($xml->body->div->p as $p) {
                $begin = ltrim($p["begin"], "1");
                $end = ltrim($p["end"], "1");
                $text = trim(strip_tags($p->asXML()));
                $text = preg_replace("/[\r\n]+/", "\n", $text);

                $vtt .= "$begin --> $end\n";
                $vtt .= "$text\n\n";
            }
            $vttParts[$index] = $vtt;
        };

        $requests = function () use ($timeStart, $timeInterval, $timeRounds, $baseUrl, $media) {
            for ($i = $timeStart; $i < ($timeRounds * $timeInterval); $i += $timeInterval) {
                $mediaUrl = $baseUrl . str_replace('$Time$', $i, $media);
                yield new \GuzzleHttp\Psr7\Request("GET", $mediaUrl);
            }
        };

        $client = new Client();

        $pool = new Pool($client, $requests(), [
            "concurrency" => 100,
            "fulfilled" => $fulfilled,
            "rejected" => function (RequestException $reason, $index) {
                var_dump($reason);
            }
        ]);

        $promise = $pool->promise();
        $promise->wait();

        $vtt = $vtt . implode("", $vttParts);

        $subtitles = "data:text/vtt;base64," . base64_encode($vtt);
        return new StreamInformation("fr", $video, $subtitles);
    }

    static function getRegex(): string
    {
        return "/france\\.tv/i";
    }
}
