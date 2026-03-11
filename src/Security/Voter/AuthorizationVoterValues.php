<?php

namespace App\Security\Voter;

use App\Utils\Roles;

class AuthorizationVoterValues
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

    public const string EDIT = 'EDIT';
    public const string VIEW = 'VIEW';
    public const string LIST = 'LIST';
    public const string CREATE = 'CREATE';
    public const string DELETE = 'DELETE';

    public const array ATTRIBUTES = [
        self::EDIT,
        self::VIEW,
        self::LIST,
        self::CREATE,
        self::DELETE,
    ];

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

    public const array AUTHORIZATION_DEFAULTS = [
        self::CAMPAIGN_SCREEN => [
            self::EDIT => [Roles::ROLE_EDITOR],
            self::VIEW => [Roles::ROLE_EDITOR],
            self::CREATE => [Roles::ROLE_EDITOR],
            self::DELETE => [Roles::ROLE_EDITOR],
            self::LIST => [Roles::ROLE_EDITOR],
        ],
        self::CAMPAIGN_SCREEN_GROUP => [
            self::EDIT => [Roles::ROLE_EDITOR],
            self::VIEW => [Roles::ROLE_EDITOR],
            self::CREATE => [Roles::ROLE_EDITOR],
            self::DELETE => [Roles::ROLE_EDITOR],
            self::LIST => [Roles::ROLE_EDITOR],
        ],
        self::FEED => [
            self::EDIT => [Roles::ROLE_EDITOR],
            self::VIEW => [Roles::ROLE_EDITOR],
            self::CREATE => [Roles::ROLE_EDITOR],
            self::DELETE => [Roles::ROLE_EDITOR],
            self::LIST => [Roles::ROLE_EDITOR],
        ],
        self::FEED_SOURCE => [
            self::EDIT => [Roles::ROLE_EDITOR],
            self::VIEW => [Roles::ROLE_EDITOR],
            self::CREATE => [Roles::ROLE_EDITOR],
            self::DELETE => [Roles::ROLE_EDITOR],
            self::LIST => [Roles::ROLE_EDITOR],
        ],
        self::FEED_SOURCE_SLIDES => [
            self::EDIT => [Roles::ROLE_EDITOR],
            self::VIEW => [Roles::ROLE_EDITOR],
            self::CREATE => [Roles::ROLE_EDITOR],
            self::DELETE => [Roles::ROLE_EDITOR],
            self::LIST => [Roles::ROLE_EDITOR],
        ],
        self::LAYOUT => [
            self::EDIT => [Roles::ROLE_EDITOR],
            self::VIEW => [Roles::ROLE_EDITOR],
            self::CREATE => [Roles::ROLE_EDITOR],
            self::DELETE => [Roles::ROLE_EDITOR],
            self::LIST => [Roles::ROLE_EDITOR],
        ],
        self::MEDIA => [
            self::EDIT => [Roles::ROLE_EDITOR],
            self::VIEW => [Roles::ROLE_EDITOR],
            self::CREATE => [Roles::ROLE_EDITOR],
            self::DELETE => [Roles::ROLE_EDITOR],
            self::LIST => [Roles::ROLE_EDITOR],
        ],
        self::PLAYLIST => [
            self::EDIT => [Roles::ROLE_EDITOR],
            self::VIEW => [Roles::ROLE_EDITOR],
            self::CREATE => [Roles::ROLE_EDITOR],
            self::DELETE => [Roles::ROLE_EDITOR],
            self::LIST => [Roles::ROLE_EDITOR],
        ],
        self::PLAYLIST_SCREEN_REGION => [
            self::EDIT => [Roles::ROLE_EDITOR],
            self::VIEW => [Roles::ROLE_EDITOR],
            self::CREATE => [Roles::ROLE_EDITOR],
            self::DELETE => [Roles::ROLE_EDITOR],
            self::LIST => [Roles::ROLE_EDITOR],
        ],
        self::PLAYLIST_SLIDE => [
            self::EDIT => [Roles::ROLE_EDITOR],
            self::VIEW => [Roles::ROLE_EDITOR],
            self::CREATE => [Roles::ROLE_EDITOR],
            self::DELETE => [Roles::ROLE_EDITOR],
            self::LIST => [Roles::ROLE_EDITOR],
        ],
        self::SCREEN => [
            self::EDIT => [Roles::ROLE_EDITOR],
            self::VIEW => [Roles::ROLE_EDITOR],
            self::CREATE => [Roles::ROLE_EDITOR],
            self::DELETE => [Roles::ROLE_EDITOR],
            self::LIST => [Roles::ROLE_EDITOR],
        ],
        self::SCREEN_CAMPAIGN => [
            self::EDIT => [Roles::ROLE_EDITOR],
            self::VIEW => [Roles::ROLE_EDITOR],
            self::CREATE => [Roles::ROLE_EDITOR],
            self::DELETE => [Roles::ROLE_EDITOR],
            self::LIST => [Roles::ROLE_EDITOR],
        ],
        self::SCREEN_GROUP => [
            self::EDIT => [Roles::ROLE_EDITOR],
            self::VIEW => [Roles::ROLE_EDITOR],
            self::CREATE => [Roles::ROLE_EDITOR],
            self::DELETE => [Roles::ROLE_EDITOR],
            self::LIST => [Roles::ROLE_EDITOR],
        ],
        self::SCREEN_GROUP_CAMPAIGN => [
            self::EDIT => [Roles::ROLE_EDITOR],
            self::VIEW => [Roles::ROLE_EDITOR],
            self::CREATE => [Roles::ROLE_EDITOR],
            self::DELETE => [Roles::ROLE_EDITOR],
            self::LIST => [Roles::ROLE_EDITOR],
        ],
        self::SCREEN_GROUP_SCREEN => [
            self::EDIT => [Roles::ROLE_EDITOR],
            self::VIEW => [Roles::ROLE_EDITOR],
            self::CREATE => [Roles::ROLE_EDITOR],
            self::DELETE => [Roles::ROLE_EDITOR],
            self::LIST => [Roles::ROLE_EDITOR],
        ],
        self::SCREEN_LAYOUT_REGION => [
            self::EDIT => [Roles::ROLE_EDITOR],
            self::VIEW => [Roles::ROLE_EDITOR],
            self::CREATE => [Roles::ROLE_EDITOR],
            self::DELETE => [Roles::ROLE_EDITOR],
            self::LIST => [Roles::ROLE_EDITOR],
        ],
        self::SCREEN_SCREEN_GROUP => [
            self::EDIT => [Roles::ROLE_EDITOR],
            self::VIEW => [Roles::ROLE_EDITOR],
            self::CREATE => [Roles::ROLE_EDITOR],
            self::DELETE => [Roles::ROLE_EDITOR],
            self::LIST => [Roles::ROLE_EDITOR],
        ],
        self::SLIDE => [
            self::EDIT => [Roles::ROLE_EDITOR],
            self::VIEW => [Roles::ROLE_EDITOR],
            self::CREATE => [Roles::ROLE_EDITOR],
            self::DELETE => [Roles::ROLE_EDITOR],
            self::LIST => [Roles::ROLE_EDITOR],
        ],
        self::SLIDE_PLAYLIST => [
            self::EDIT => [Roles::ROLE_EDITOR],
            self::VIEW => [Roles::ROLE_EDITOR],
            self::CREATE => [Roles::ROLE_EDITOR],
            self::DELETE => [Roles::ROLE_EDITOR],
            self::LIST => [Roles::ROLE_EDITOR],
        ],
        self::TEMPLATE => [
            self::EDIT => [Roles::ROLE_EDITOR],
            self::VIEW => [Roles::ROLE_EDITOR],
            self::CREATE => [Roles::ROLE_EDITOR],
            self::DELETE => [Roles::ROLE_EDITOR],
            self::LIST => [Roles::ROLE_EDITOR],
        ],
        self::TENANT => [
            self::EDIT => [Roles::ROLE_EDITOR],
            self::VIEW => [Roles::ROLE_EDITOR],
            self::CREATE => [Roles::ROLE_EDITOR],
            self::DELETE => [Roles::ROLE_EDITOR],
            self::LIST => [Roles::ROLE_EDITOR],
        ],
        self::THEME => [
            self::EDIT => [Roles::ROLE_ADMIN],
            self::VIEW => [Roles::ROLE_EDITOR],
            self::CREATE => [Roles::ROLE_ADMIN],
            self::DELETE => [Roles::ROLE_ADMIN],
            self::LIST => [Roles::ROLE_EDITOR],
        ],
        self::USER => [
            self::EDIT => [Roles::ROLE_EDITOR],
            self::VIEW => [Roles::ROLE_EDITOR],
            self::CREATE => [Roles::ROLE_EDITOR],
            self::DELETE => [Roles::ROLE_EDITOR],
            self::LIST => [Roles::ROLE_EDITOR],
        ],
    ];
}
