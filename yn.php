<?php
spl_autoload_register(function (string $className) {
    if (is_readable($file = sprintf('%s/%s.php', realpath(__DIR__), $className))) {
        require_once $file;
    }
});

/**
 * 配信予定一覧API
 */
new class {
    /**
     * YoutubeAPI 一覧
     */
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

    /**
     * YoutubeAPI 動画詳細
     */
    private const VIDEO_URL_BASE = 'https://www.googleapis.com/youtube/v3/videos';
    private const VIDEO_PARAM = [
        'part' => 'liveStreamingDetails',
        'id' => '%s',
        'key' => '%s',
    ];

    /**
     * Youtubeの動画リンク
     */
    private const LINK_URL_BASE = 'https://www.youtube.com/watch?v=%s';

    private int|false $now;

    /**
     * constructor.
     * @param int $mode 1:slack, 2:discord, 3:LINE(未実装)
     */
    public function __construct(private int $mode = 2)
    {
        $this->now = strtotime('now');
        $publishedAfter = $this->getUnix2utc(strtotime('-1 week'));
        date_default_timezone_set('Asia/Tokyo');
        $skipCount = 0;
        $count = 0;

        // チャンネルごとにAPIを叩く
        foreach (Config::CHANNEL_IDS as $channelID) {
            // 一覧API
            $searchURL = $this->urlGenerate(self::SEARCH_URL_BASE, self::SEARCH_PARAM, [Config::API_KEY, $channelID, $publishedAfter,]);
            $result = file_get_contents($searchURL);

            // 結果が不正だった場合弾く
            if (false === $result) {
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
                    // Slackに通知する
                    1 => $this->postCurl(Config::SLACK_WEBHOOK_URL, json_encode([
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
                    // discordに通知する
                    2 => $this->postCurl(Config::DISCORD_WEBHOOK_URL, json_encode([
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
                    // TODO: LINEに通知する
                    3 => null,
                };
            }
        }

        // スキップした投稿がある場合、通知する
        if (0 < $skipCount) {
            $json = ['content' => sprintf(Config::SKIP_NOTIFICATION_BASE, $count, $skipCount),];
            $this->postCurl(Config::DISCORD_WEBHOOK_URL, json_encode($json, JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * 配信開始時刻の取得
     *
     * @param string $videoId 一覧APIの結果 items.[].id.videoId
     * @return string|bool フォーマット後の文字列
     */
    private function getStartTime(string $videoId): string|bool
    {
        // 動画詳細
        $videoURL = $this->urlGenerate(self::VIDEO_URL_BASE, self::VIDEO_PARAM, [$videoId, Config::API_KEY,]);
        $getJSON = file_get_contents($videoURL);
        $getArray = json_decode($getJSON, Config::JSON_FLAGS);
        $time = strtotime($getArray['items'][0]['liveStreamingDetails']['scheduledStartTime'] ?? '');
        if ($time < $this->now) {
            return false;
        }

        $weekName = Config::WEEK_ARRAY[date('w', $time)];
        return date(sprintf(Config::PRINT_DATE_FORMAT, $weekName), $time);
    }

    /**
     * cURL共通処理
     *
     * @param string $url URL
     * @param string $json パラメータ
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
     * @param string $baseURL 元のURL
     * @param array $baseParams パラメタのKey-Value連想配列
     * @param array $add 外部から適用する変数
     * @return string 生成されたURL
     */
    private function urlGenerate(string $baseURL, array $baseParams, array $add): string
    {
        foreach ($baseParams as $key => $baseParam) {
            $baseParams[$key] = $baseParam === '%s' ? sprintf($baseParam, array_shift($add)) : $baseParam;
        }
        return sprintf('%s?%s', $baseURL, http_build_query($baseParams));
    }

    /**
     * UTC変換
     *
     * @param string $unixTime UnixTime
     * @return string UTC
     */
    private function getUnix2utc(string $unixTime): string
    {
        return gmdate('Y-m-d', $unixTime) . 'T' . gmdate('H:i:s', $unixTime) . 'Z';
    }
};
