<?php

namespace App\Interactive;

/**
 * Interactive slide that allows for performing quick bookings of resources.
 *
 * Only resources attached to the slide through slide.feed.configuration.resources can be booked from the slide.
 */
class MicrosoftGraphQuickBook implements InteractiveInterface
{
    public function getConfigOptions(): array
    {
        return [
            'username' => [
                'required' => true,
                'description' => 'The Microsoft Graph username that should perform the action.',
            ],
            'password' => [
                'required' => true,
                'description' => 'The password of the user.',
            ],
        ];
    }

    public function performAction(): array
    {
        return [];
    }
}
