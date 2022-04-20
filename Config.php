<?php

class Config
{
    /**
     * 通知したいチャンネルのID
     */
    public const CHANNEL_IDS = [
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
        'UC5CwaMl1eIgY8h02uZw7u8A', // 星街すいせい
        'UCZlDXzGoo7d44bwdNObFacg', // 天音かなた
        'UCqm3BQLlJfvkTsX_hvm0UmA', // 角巻わため
        'UCK9V2B22uJYu3N7eR_BT9QA', // 尾丸ポルカ
        'UCAoy6rzhSf4ydcYjJw3WoVg', // アイラニ・イオフィフティーン
    ];

    /**
     * SlackのWebhookURL
     */
    public const SLACK_WEBHOOK_URL = 'https://hooks.slack.com/services/xxx/yyy/zzz';

    /**
     * DiscordのWebhookURL
     */
    public const DISCORD_WEBHOOK_URL = 'https://discord.com/api/webhooks/aaa/bbb';


    //YouTube API v3
    public const API_KEY = 'aaa';

    public const JSON_FLAGS = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    public const WEEK_ARRAY = ['日', '月', '火', '水', '木', '金', '土',];

    public const SKIP_NOTIFICATION_BASE = '%s件中%s件スキップしますた';
    public const PRINT_DATE_FORMAT = 'Y/m/d(%s) H:i';
}