# youtube_notification
好きなYoutuberの配信予定を通知する

## YouTube Data API
https://tro555-engineer.com/2022/02/09/youtube-data-api%e3%81%ae%e5%a7%8b%e3%82%81%e6%96%b9/  
取得したものを `API_KEY` に設定する  
https://github.com/sakash1ta90/youtube_notification/blob/1bdfc999a6a6d5bc54eef943537dd2c2a37bd0ca/Config.php#L41

## Slack WebhookURL
https://slack.com/intl/ja-jp/help/articles/115005265063-Slack-%E3%81%A7%E3%81%AE-Incoming-Webhook-%E3%81%AE%E5%88%A9%E7%94%A8  
取得したものを `SLACK_WEBHOOK_URL` に設定する  
https://github.com/sakash1ta90/youtube_notification/blob/1bdfc999a6a6d5bc54eef943537dd2c2a37bd0ca/Config.php#L32

## チャンネルID
YouTubeチャンネルのトップページのURLの末尾の英数字  
`https://www.youtube.com/channel/UCZlDXzGoo7d44bwdNObFacg` の `UCZlDXzGoo7d44bwdNObFacg` 部分  
取得したものを `CHANNEL_IDS` に設定する  
https://github.com/sakash1ta90/youtube_notification/blob/1bdfc999a6a6d5bc54eef943537dd2c2a37bd0ca/Config.php#L8-L27
