<?php

/**
 * 配信予定一覧API
 */
new class {
    /**
     * 通知したいチャンネルのID
     */
    private const CHANNEL_IDS = [
        'UCJFZiqLMntJufDCHc6bQixg', // hololive ホロライブ - VTuber Group
        'UCdn5BQ06XqgXoAxIhbqw5Rg', // 白上フブキ
        'UCQ0UDLQCjY0rmuxCDE38FGg', // 夏色まつり
        'UC1opHUrw8rvnsadT-iGp7Cg', // 湊あくあ
        'UC1suqwovbL1kzsoaZgFZLKg', // 癒月ちょこ
        'UCvzGlP9oQwU--Y0r9id_jnA', // 大空スバル
        'UCp-5t9SrOQwXMU7iIjQfARg', // 大神ミオ
        'UCvaTdHTWBGv3MKj3KVqJVCw', // 猫又おかゆ
        'UChAnqc_AY5_I3Px5dig3X1Q', // 戌神ころね
        'UCvInZx9h3jC2JzsIzoOebWg', // 不知火フレア
        'UCdyqAaZDKHXg4Ahi7VENThQ', // 白銀ノエル
        'UCCzUftO8KOVkV4wQG1vkUvg', // 宝鐘マリン
        'UC1DCedRgGHBdm81E1llLhOQ', // 兎田ぺこら
        'UCl_gCybOJRIgOXw6Qb4qJzQ', // 潤羽るしあ
        'UC5CwaMl1eIgY8h02uZw7u8A', // 星街すいせい
        'UCZlDXzGoo7d44bwdNObFacg', // 天音かなた
        'UCqm3BQLlJfvkTsX_hvm0UmA', // 角巻わため
        'UCK9V2B22uJYu3N7eR_BT9QA', // 尾丸ポルカ
        'UCAoy6rzhSf4ydcYjJw3WoVg', // アイラニ・イオフィフティーン
    ];
    private const SEARCH_URL_BASE = 'https://www.googleapis.com/youtube/v3/search';

    // https://developers.google.com/youtube/v3/docs/search/list?hl=ja
    private const SEARCH_PARAM = [
        'part' => 'snippet',
        'type' => 'video',
        'key' => '%s',
        'channelId' => '%s',
        'order' => 'date',
        'eventType' => 'upcoming',
        'publishedAfter' => '%s',
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
    private const DISCORD_WEBHOOK_URL = 'https://discord.com/api/webhooks/xxx/yyy';
    //YouTube API v3
    private const API_KEY = 'aaa';
    private const JSON_FLAGS = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    private const WEEK_ARRAY = ['日', '月', '火', '水', '木', '金', '土',];
    private int|false $now;
    /**
     * @var int 1:slack, 2:discord
     */
    private int $mode = 2;

    /**
     * constructor.
     */
    public function __construct()
    {
        $this->now = strtotime('now');
        $publishedAfter = $this->getUnix2utc(strtotime('-1 week'));
        date_default_timezone_set('Asia/Tokyo');
        $skipCount = 0;
        $count = 0;
        foreach (self::CHANNEL_IDS as $channelId) {
            $searchUrl = $this->urlGenerate(self::SEARCH_URL_BASE, self::SEARCH_PARAM, [self::API_KEY, $channelId, $publishedAfter,]);
            $result = file_get_contents($searchUrl);
            if (false === $result || null === $result) {
                continue;
            }
            ['items' => $items] = json_decode($result, true);
            foreach ($items as $item) {
                $startTime = $this->getStartTime($item['id']['videoId']);
                ++$count;
                if (false === $startTime) {
                    ++$skipCount;
                    continue;
                }
                match ($this->mode) {
                    1 => $this->postCurl(self::SLACK_WEBHOOK_URL, json_encode([
                        'attachments' => [
                            [
                                'pretext' => $item['snippet']['channelTitle'],
                                'text' => "{$startTime}\n{$item['snippet']['description']}",
                                'fallback' => $item['snippet']['title'],
                                'color' => 'good',
                                'title' => $item['snippet']['title'],
                                'title_link' => sprintf(self::LINK_URL_BASE, $item['id']['videoId']),
                                'image_url' => $item['snippet']['thumbnails']['medium']['url'],
                            ],
                        ],
                    ], JSON_UNESCAPED_UNICODE)),
                    2 => $this->postCurl(self::DISCORD_WEBHOOK_URL, json_encode([
                        'content' => $item['snippet']['channelTitle'],
                        'embeds' => [
                            [
                                'title' => $item['snippet']['title'],
                                'description' => "{$startTime}\n{$item['snippet']['description']}",
                                'url' => sprintf(self::LINK_URL_BASE, $item['id']['videoId']),
                                'color' => hexdec('FFFFFF'),
                                'image' => [
                                    'url' => $item['snippet']['thumbnails']['medium']['url'],
                                ],
                            ],
                        ],
                    ], JSON_UNESCAPED_UNICODE)),
                };
                // Slackに通知する
                // discordに通知する
            }
        }
        if (0 < $skipCount) {
            $json = ['content' => sprintf('%s件中%s件スキップしますた', $count, $skipCount),];
            $this->postCurl(self::DISCORD_WEBHOOK_URL, json_encode($json, JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * 配信開始時刻の取得
     *
     * @param string $videoId
     * @return string|bool
     */
    private function getStartTime(string $videoId): string|bool
    {
        $videoUrl = $this->urlGenerate(self::VIDEO_URL_BASE, self::VIDEO_PARAM, [$videoId, self::API_KEY,]);
        $getJson = file_get_contents($videoUrl);
        $getArray = json_decode($getJson, self::JSON_FLAGS);
        $time = strtotime($getArray['items'][0]['liveStreamingDetails']['scheduledStartTime'] ?? '');
        if ($time < $this->now) {
            return false;
        }

        $weekName = self::WEEK_ARRAY[date('w', $time)];
        return date("Y/m/d({$weekName}) H:i", $time);
    }

    /**
     * cURL共通処理
     *
     * @param string $url
     * @param string $json
     * @return bool|string
     */
    private function postCurl(string $url, string $json): bool|string
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => [
                'Content-type: application/json'
            ],
        ]);
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
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

    /**
     * @param string $unixTime
     * @return string
     */
    private function getUnix2utc(string $unixTime): string
    {
        return gmdate('Y-m-d', $unixTime) . 'T' . gmdate('H:i:s', $unixTime) . 'Z';
    }
};