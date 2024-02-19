<?php

namespace App\Interactive;

use App\Entity\Tenant\Slide;
use App\Exceptions\InteractiveException;

/**
 * Interactive slide that allows for performing quick bookings of resources.
 *
 * Only resources attached to the slide through slide.feed.configuration.resources can be booked from the slide.
 */
class MicrosoftGraphQuickBook implements InteractiveInterface
{
    private const ACTION_GET_QUICK_BOOK_OPTIONS = 'ACTION_GET_QUICK_BOOK_OPTIONS';
    private const ACTION_QUICK_BOOK = 'ACTION_QUICK_BOOK';

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

    /**
     * @throws InteractiveException
     */
    public function performAction(Slide $slide, Interaction $interaction): array
    {
        return match ($interaction->action) {
            self::ACTION_GET_QUICK_BOOK_OPTIONS => $this->getQuickBookOptions($slide, $interaction),
            self::ACTION_QUICK_BOOK => $this->quickBook($slide, $interaction),
            default => throw new InteractiveException("Action not allowed"),
        };
    }

    private function getQuickBookOptions(Slide $slide, Interaction $interaction): array
    {
        return ["test1" => "test2"];
    }

    private function quickBook(Slide $slide, Interaction $interaction): array
    {
        return ["test3" => "test4"];
    }
}
