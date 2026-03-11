<?php

namespace App\Security\Voter;

class AuthorizationVoterHelper
{
    public const string CAMPAIGN_SCREEN = "CAMPAIGN_SCREEN";
    public const string CAMPAIGN_SCREEN_GROUP = "CAMPAIGN_SCREEN_GROUP";
    public const string FEED = "FEED";
    public const string FEED_SOURCE = "FEED_SOURCE";
    public const string FEED_SOURCE_SLIDES = "FEED_SOURCE_SLIDES";
    public const string LAYOUT = "LAYOUT";
    public const string MEDIA = "MEDIA";
    public const string PLAYLIST = "PLAYLIST";
    public const string PLAYLIST_SCREEN_REGION = "PLAYLIST_SCREEN_REGION";
    public const string PLAYLIST_SLIDE = "PLAYLIST_SLIDE";
    public const string SCREEN = "SCREEN";
    public const string SCREEN_CAMPAIGN = "SCREEN_CAMPAIGN";
    public const string SCREEN_GROUP = "SCREEN_GROUP";
    public const string SCREEN_GROUP_CAMPAIGN = "SCREEN_GROUP_CAMPAIGN";
    public const string SCREEN_GROUP_SCREEN = "SCREEN_GROUP_SCREEN";
    public const string SCREEN_LAYOUT_REGION = "SCREEN_LAYOUT_REGION";
    public const string SCREEN_SCREEN_GROUP = "SCREEN_SCREEN_GROUP";
    public const string SLIDE = "SLIDE";
    public const string SLIDE_PLAYLIST = "SLIDE_PLAYLIST";
    public const string TEMPLATE = "TEMPLATE";
    public const string TENANT = "TENANT";
    public const string THEME = "THEME";
    public const string USER = "USER";

    public const array TYPES = [
        self::CAMPAIGN_SCREEN,
        self::CAMPAIGN_SCREEN_GROUP,
        self::FEED,
        self::FEED_SOURCE,
        self::FEED_SOURCE_SLIDES,
        self::LAYOUT,
        self::MEDIA,
        self::PLAYLIST,
        self::PLAYLIST_SCREEN_REGION,
        self::PLAYLIST_SLIDE,
        self::SCREEN,
        self::SCREEN_CAMPAIGN,
        self::SCREEN_GROUP,
        self::SCREEN_GROUP_CAMPAIGN,
        self::SCREEN_GROUP_SCREEN,
        self::SCREEN_LAYOUT_REGION,
        self::SCREEN_SCREEN_GROUP,
        self::SLIDE,
        self::SLIDE_PLAYLIST,
        self::TEMPLATE,
        self::TENANT,
        self::THEME,
        self::USER,
    ];

    public static function getAuthorizationDefaults(): array
    {
        return [
            "CAMPAIGN_SCREEN" => [
                AuthorizationVoter::EDIT => ['ROLE_EDITOR'],
                AuthorizationVoter::VIEW => ['ROLE_EDITOR'],
                AuthorizationVoter::CREATE => ['ROLE_EDITOR'],
                AuthorizationVoter::DELETE => ['ROLE_EDITOR'],
                AuthorizationVoter::LIST => ['ROLE_EDITOR'],
            ],
            "CAMPAIGN_SCREEN_GROUP" => [
                AuthorizationVoter::EDIT => ['ROLE_EDITOR'],
                AuthorizationVoter::VIEW => ['ROLE_EDITOR'],
                AuthorizationVoter::CREATE => ['ROLE_EDITOR'],
                AuthorizationVoter::DELETE => ['ROLE_EDITOR'],
                AuthorizationVoter::LIST => ['ROLE_EDITOR'],
            ],
            "FEED" => [
                AuthorizationVoter::EDIT => ['ROLE_EDITOR'],
                AuthorizationVoter::VIEW => ['ROLE_EDITOR'],
                AuthorizationVoter::CREATE => ['ROLE_EDITOR'],
                AuthorizationVoter::DELETE => ['ROLE_EDITOR'],
                AuthorizationVoter::LIST => ['ROLE_EDITOR'],
            ],
            "FEED_SOURCE" => [
                AuthorizationVoter::EDIT => ['ROLE_EDITOR'],
                AuthorizationVoter::VIEW => ['ROLE_EDITOR'],
                AuthorizationVoter::CREATE => ['ROLE_EDITOR'],
                AuthorizationVoter::DELETE => ['ROLE_EDITOR'],
                AuthorizationVoter::LIST => ['ROLE_EDITOR'],
            ],
            "FEED_SOURCE_SLIDES" => [
                AuthorizationVoter::EDIT => ['ROLE_EDITOR'],
                AuthorizationVoter::VIEW => ['ROLE_EDITOR'],
                AuthorizationVoter::CREATE => ['ROLE_EDITOR'],
                AuthorizationVoter::DELETE => ['ROLE_EDITOR'],
                AuthorizationVoter::LIST => ['ROLE_EDITOR'],
            ],
            "LAYOUT" => [
                AuthorizationVoter::EDIT => ['ROLE_EDITOR'],
                AuthorizationVoter::VIEW => ['ROLE_EDITOR'],
                AuthorizationVoter::CREATE => ['ROLE_EDITOR'],
                AuthorizationVoter::DELETE => ['ROLE_EDITOR'],
                AuthorizationVoter::LIST => ['ROLE_EDITOR'],
            ],
            "MEDIA" => [
                AuthorizationVoter::EDIT => ['ROLE_EDITOR'],
                AuthorizationVoter::VIEW => ['ROLE_EDITOR'],
                AuthorizationVoter::CREATE => ['ROLE_EDITOR'],
                AuthorizationVoter::DELETE => ['ROLE_EDITOR'],
                AuthorizationVoter::LIST => ['ROLE_EDITOR'],
            ],
            "PLAYLIST" => [
                AuthorizationVoter::EDIT => ['ROLE_EDITOR'],
                AuthorizationVoter::VIEW => ['ROLE_EDITOR'],
                AuthorizationVoter::CREATE => ['ROLE_EDITOR'],
                AuthorizationVoter::DELETE => ['ROLE_EDITOR'],
                AuthorizationVoter::LIST => ['ROLE_EDITOR'],
            ],
            "PLAYLIST_SCREEN_REGION" => [
                AuthorizationVoter::EDIT => ['ROLE_EDITOR'],
                AuthorizationVoter::VIEW => ['ROLE_EDITOR'],
                AuthorizationVoter::CREATE => ['ROLE_EDITOR'],
                AuthorizationVoter::DELETE => ['ROLE_EDITOR'],
                AuthorizationVoter::LIST => ['ROLE_EDITOR'],
            ],
            "PLAYLIST_SLIDE" => [
                AuthorizationVoter::EDIT => ['ROLE_EDITOR'],
                AuthorizationVoter::VIEW => ['ROLE_EDITOR'],
                AuthorizationVoter::CREATE => ['ROLE_EDITOR'],
                AuthorizationVoter::DELETE => ['ROLE_EDITOR'],
                AuthorizationVoter::LIST => ['ROLE_EDITOR'],
            ],
            "SCREEN" => [
                AuthorizationVoter::EDIT => ['ROLE_EDITOR'],
                AuthorizationVoter::VIEW => ['ROLE_EDITOR'],
                AuthorizationVoter::CREATE => ['ROLE_EDITOR'],
                AuthorizationVoter::DELETE => ['ROLE_EDITOR'],
                AuthorizationVoter::LIST => ['ROLE_EDITOR'],
            ],
            "SCREEN_CAMPAIGN" => [
                AuthorizationVoter::EDIT => ['ROLE_EDITOR'],
                AuthorizationVoter::VIEW => ['ROLE_EDITOR'],
                AuthorizationVoter::CREATE => ['ROLE_EDITOR'],
                AuthorizationVoter::DELETE => ['ROLE_EDITOR'],
                AuthorizationVoter::LIST => ['ROLE_EDITOR'],
            ],
            "SCREEN_GROUP" => [
                AuthorizationVoter::EDIT => ['ROLE_EDITOR'],
                AuthorizationVoter::VIEW => ['ROLE_EDITOR'],
                AuthorizationVoter::CREATE => ['ROLE_EDITOR'],
                AuthorizationVoter::DELETE => ['ROLE_EDITOR'],
                AuthorizationVoter::LIST => ['ROLE_EDITOR'],
            ],
            "SCREEN_GROUP_CAMPAIGN" => [
                AuthorizationVoter::EDIT => ['ROLE_EDITOR'],
                AuthorizationVoter::VIEW => ['ROLE_EDITOR'],
                AuthorizationVoter::CREATE => ['ROLE_EDITOR'],
                AuthorizationVoter::DELETE => ['ROLE_EDITOR'],
                AuthorizationVoter::LIST => ['ROLE_EDITOR'],
            ],
            "SCREEN_GROUP_SCREEN" => [
                AuthorizationVoter::EDIT => ['ROLE_EDITOR'],
                AuthorizationVoter::VIEW => ['ROLE_EDITOR'],
                AuthorizationVoter::CREATE => ['ROLE_EDITOR'],
                AuthorizationVoter::DELETE => ['ROLE_EDITOR'],
                AuthorizationVoter::LIST => ['ROLE_EDITOR'],
            ],
            "SCREEN_LAYOUT_REGION" => [
                AuthorizationVoter::EDIT => ['ROLE_EDITOR'],
                AuthorizationVoter::VIEW => ['ROLE_EDITOR'],
                AuthorizationVoter::CREATE => ['ROLE_EDITOR'],
                AuthorizationVoter::DELETE => ['ROLE_EDITOR'],
                AuthorizationVoter::LIST => ['ROLE_EDITOR'],
            ],
            "SCREEN_SCREEN_GROUP" => [
                AuthorizationVoter::EDIT => ['ROLE_EDITOR'],
                AuthorizationVoter::VIEW => ['ROLE_EDITOR'],
                AuthorizationVoter::CREATE => ['ROLE_EDITOR'],
                AuthorizationVoter::DELETE => ['ROLE_EDITOR'],
                AuthorizationVoter::LIST => ['ROLE_EDITOR'],
            ],
            "SLIDE" => [
                AuthorizationVoter::EDIT => ['ROLE_EDITOR'],
                AuthorizationVoter::VIEW => ['ROLE_EDITOR'],
                AuthorizationVoter::CREATE => ['ROLE_EDITOR'],
                AuthorizationVoter::DELETE => ['ROLE_EDITOR'],
                AuthorizationVoter::LIST => ['ROLE_EDITOR'],
            ],
            "SLIDE_PLAYLIST" => [
                AuthorizationVoter::EDIT => ['ROLE_EDITOR'],
                AuthorizationVoter::VIEW => ['ROLE_EDITOR'],
                AuthorizationVoter::CREATE => ['ROLE_EDITOR'],
                AuthorizationVoter::DELETE => ['ROLE_EDITOR'],
                AuthorizationVoter::LIST => ['ROLE_EDITOR'],
            ],
            "TEMPLATE" => [
                AuthorizationVoter::EDIT => ['ROLE_EDITOR'],
                AuthorizationVoter::VIEW => ['ROLE_EDITOR'],
                AuthorizationVoter::CREATE => ['ROLE_EDITOR'],
                AuthorizationVoter::DELETE => ['ROLE_EDITOR'],
                AuthorizationVoter::LIST => ['ROLE_EDITOR'],
            ],
            "TENANT" => [
                AuthorizationVoter::EDIT => ['ROLE_EDITOR'],
                AuthorizationVoter::VIEW => ['ROLE_EDITOR'],
                AuthorizationVoter::CREATE => ['ROLE_EDITOR'],
                AuthorizationVoter::DELETE => ['ROLE_EDITOR'],
                AuthorizationVoter::LIST => ['ROLE_EDITOR'],
            ],
            "THEME" => [
                AuthorizationVoter::EDIT => ['ROLE_ADMIN'],
                AuthorizationVoter::VIEW => ['ROLE_EDITOR'],
                AuthorizationVoter::CREATE => ['ROLE_ADMIN'],
                AuthorizationVoter::DELETE => ['ROLE_ADMIN'],
                AuthorizationVoter::LIST => ['ROLE_EDITOR'],
            ],
            "USER" => [
                AuthorizationVoter::EDIT => ['ROLE_EDITOR'],
                AuthorizationVoter::VIEW => ['ROLE_EDITOR'],
                AuthorizationVoter::CREATE => ['ROLE_EDITOR'],
                AuthorizationVoter::DELETE => ['ROLE_EDITOR'],
                AuthorizationVoter::LIST => ['ROLE_EDITOR'],
            ],
        ];
    }
}
