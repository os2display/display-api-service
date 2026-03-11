<?php

namespace App\Security\Voter;

class AuthorizationVoterHelper
{
    private const array editorClasses = [
        "Screen",
        "Feed",
        "FeedInput",
        "FeedSource",
        "FeedSourceInput",
        "Media",
        "Playlist",
        "PlaylistInput",
        "PlaylistScreenRegion",
        "PlaylistSlide",
        "ScreenCampaign",
        "ScreenGroup",
        "ScreenGroupCampaign",
        "ScreenGroupInput",
        "ScreenInput",
        "ScreenLayout",
        "ScreenLayoutRegions",
        "Slide",
        "SlideInput",
        "Template",
    ];

    private const array adminClasses = [
        "ActivationCode",
        "Tenant",
        "Theme",
        "ThemeInput",
        "User",
        "UserActivationCode",
        "UserActivationCodeInput",
        "UserInput",
        "Screen",
    ];

    public static function getAuthorizationDefaults(): array
    {
        $defaults = [];

        foreach (self::editorClasses as $class) {
            $defaults[$class] = [
                AuthorizationVoter::EDIT => ['ROLE_EDITOR'],
                AuthorizationVoter::VIEW => ['ROLE_EDITOR'],
                AuthorizationVoter::CREATE => ['ROLE_EDITOR'],
                AuthorizationVoter::DELETE => ['ROLE_EDITOR'],
            ];
        }

        foreach (self::adminClasses as $class) {
            $defaults[$class] = [
                AuthorizationVoter::EDIT => ['ROLE_ADMIN'],
                AuthorizationVoter::VIEW => ['ROLE_ADMIN'],
                AuthorizationVoter::CREATE => ['ROLE_ADMIN'],
                AuthorizationVoter::DELETE => ['ROLE_ADMIN'],
            ];
        }

        return $defaults;
    }
}
