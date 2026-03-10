<?php

namespace App\Security\Voter;

class AuthorizationVoterHelper
{
    private const array editorClasses = [
        "Screen",
        "ActivationCode",
        "Feed",
        "FeedInput",
        "FeedSource",
        "FeedSourceInput",
        "InteractiveSlideActionInput",
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

        // TODO: Adjust to sensible defaults.

        foreach (self::editorClasses as $class) {
            $defaults[$class] = [
                AuthorizationVoter::EDIT => ['ROLE_EDITOR'],
                AuthorizationVoter::EDIT . AuthorizationVoter::OWN => ['ROLE_EDITOR'],
                AuthorizationVoter::VIEW => ['ROLE_EDITOR'],
                AuthorizationVoter::VIEW . AuthorizationVoter::OWN => ['ROLE_EDITOR'],
                AuthorizationVoter::CREATE => ['ROLE_EDITOR'],
                AuthorizationVoter::CREATE . AuthorizationVoter::OWN => ['ROLE_EDITOR'],
                AuthorizationVoter::DELETE => ['ROLE_EDITOR'],
                AuthorizationVoter::DELETE . AuthorizationVoter::OWN => ['ROLE_EDITOR'],
            ];
        }

        foreach (self::adminClasses as $class) {
            $defaults[$class] = [
                AuthorizationVoter::EDIT => ['ROLE_ADMIN'],
                AuthorizationVoter::EDIT . AuthorizationVoter::OWN => ['ROLE_ADMIN'],
                AuthorizationVoter::VIEW => ['ROLE_ADMIN'],
                AuthorizationVoter::VIEW . AuthorizationVoter::OWN => ['ROLE_ADMIN'],
                AuthorizationVoter::CREATE => ['ROLE_ADMIN'],
                AuthorizationVoter::CREATE . AuthorizationVoter::OWN => ['ROLE_ADMIN'],
                AuthorizationVoter::DELETE => ['ROLE_ADMIN'],
                AuthorizationVoter::DELETE . AuthorizationVoter::OWN => ['ROLE_ADMIN'],
            ];
        }

        return $defaults;
    }
}
