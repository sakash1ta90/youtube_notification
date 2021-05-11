<?php

/**
 * 配信予定一覧API
 */
new class {
    /**
     * 通知したいチャンネルのID
     */
    private const CHANNEL_IDS = [
        'UCQ0UDLQCjY0rmuxCDE38FGg',
        'UCdn5BQ06XqgXoAxIhbqw5Rg',
        'UCZlDXzGoo7d44bwdNObFacg',
        'UChAnqc_AY5_I3Px5dig3X1Q',
        'UCCzUftO8KOVkV4wQG1vkUvg',
        'UCvaTdHTWBGv3MKj3KVqJVCw',
        'UCvzGlP9oQwU--Y0r9id_jnA',
        'UC1DCedRgGHBdm81E1llLhOQ',
        'UCp-5t9SrOQwXMU7iIjQfARg',
        'UCdyqAaZDKHXg4Ahi7VENThQ',
    ];
    private const SEARCH_URL_BASE = 'https://www.googleapis.com/youtube/v3/search';
    private const SEARCH_PARAM = [
        'part' => 'snippet',
        'type' => 'video',
        'key' => '%s',
        'channelId' => '%s',
        'order' => 'date',
        'eventType' => 'upcoming',
    ];
    private const VIDEO_URL_BASE = 'https://www.googleapis.com/youtube/v3/videos';
    private const VIDEO_PARAM = [
        'part' => 'liveStreamingDetails',
        'id' => '%s',
        'key' => '%s',
    ];
    private const LINK_URL_BASE = 'https://www.youtube.com/watch?v=%s';

    /**
     * SlackのWebhookURL
     */
    private const SLACK_WEBHOOK_URL = 'https://hooks.slack.com/services/xxx/yyy/zzz';
    //YouTube API v3
    private const API_KEY = 'aaa';
    private const JSON_FLAGS = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    /**
     * constructor.
     *
     */
    public function __construct()
    {
        date_default_timezone_set('Asia/Tokyo');
        foreach (self::CHANNEL_IDS as $channelId) {
            $searchUrl = $this->urlGenerate(self::SEARCH_URL_BASE, self::SEARCH_PARAM, [self::API_KEY, $channelId,]);
            $result = file_get_contents($searchUrl);
            if (false === $result || null === $result) {
                continue;
            }
            ['items' => $items] = json_decode($result, true);
            foreach ($items as $item) {
                // Slackに通知する
                $this->sendSlack([
                    'pretext' => $item['snippet']['channelTitle'],
                    'text' => "{$this->getStartTime($item['id']['videoId'])}\n{$item['snippet']['description']}",
                    'fallback' => $item['snippet']['title'],
                    'color' => 'good',
                    'title' => $item['snippet']['title'],
                    'title_link' => sprintf(self::LINK_URL_BASE, $item['id']['videoId']),
                    'image_url' => $item['snippet']['thumbnails']['medium']['url'],
                ]);
            }
        }
    }

    /**
     * 配信開始時刻の取得
     *
     * @param string $videoId
     * @return string
     */
    private function getStartTime(string $videoId): string
    {
        $videoUrl = $this->urlGenerate(self::VIDEO_URL_BASE, self::VIDEO_PARAM, [$videoId, self::API_KEY,]);
        $getJson = file_get_contents($videoUrl);
        $getArray = json_decode($getJson, self::JSON_FLAGS);
        return date('Y-m-d H:i:s', strtotime($getArray['items'][0]['liveStreamingDetails']['scheduledStartTime'] ?? ''));
    }

    /**
     * Slack通知
     * https://qiita.com/daikiojm/items/759ea40c00f9b539a4c8
     *
     * @param array $jsonInner Attachmentの中
     * @return void
     */
    private function sendSlack(array $jsonInner): void
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => self::SLACK_WEBHOOK_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode(['attachments' => [$jsonInner,],], JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => [
                'Content-type: application/json'
            ],
        ]);
        curl_exec($curl);
        curl_close($curl);
    }

    /**
     * URL生成
     *
     * @param string $baseUrl 元のURL
     * @param array $baseParams パラメタのKey-Value連想配列
     * @param array $add 外部から適用する変数
     * @return string 生成されたURL
     */
    private function urlGenerate(string $baseUrl, array $baseParams, array $add): string
    {
        $query = '?';
        $last = array_key_last($baseParams);
        foreach ($baseParams as $key => $baseParam) {
            $query .= sprintf('%s=%s%s', $key, $baseParam, $last !== $key ? '&' : '');
        }
        return sprintf($baseUrl . $query, ...$add);
    }
};