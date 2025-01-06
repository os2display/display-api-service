<?php

declare(strict_types=1);

namespace App\Feed;

class FeedOutputModels
{
    /**
     * Output Model: src/Feed/OutputModel/Calendar/CalendarOutput.php
     *
     * Data example:
     * [
     *   {
     *     "id": "abc123",
     *     "title": "Example Title",
     *     "startTime": 1234567890,
     *     "endTime": 1234567890,
     *     "resourceTitle": "Example Resource Title",
     *     "resourceId": "def456"
     *   }
     * ];
     *
     * Start/end time are unix timestamps.
     */
    final public const string CALENDAR_OUTPUT = 'calendar';

    /**
     * Output Model: src/Feed/OutputModel/Poster/PosterOutput.php
     */
    final public const string POSTER_OUTPUT = 'poster';

    /**
     * Output Model: src/Feed/OutputModel/Story/StoryOutput.php
     *
     * Data example:
     * [
     *   {
     *     "textMarkup": "<div class=\"text\">Sed nulla lorem, varius sodales justo ac, ultrices placerat nunc.</div>\n<div class=\"tags\"><span class=\"tag\">#mountains</span> <span class=\"tag\">#horizon</span> Lorem ipsum ...</div>",
     *     "mediaUrl": "https://raw.githubusercontent.com/os2display/display-templates/refs/heads/develop/src/fixtures/images/mountain1.jpeg",
     *     "videoUrl": null,
     *     "username": "username",
     *     "createdTime": "2022-02-03T08:50:07",
     *   },
     *   {
     *     "textMarkup": "<div class=\"text\">Sed nulla lorem, varius sodales justo ac, ultrices placerat nunc.</div>\n<div class=\"tags\"><span class=\"tag\">#mountains</span> <span class=\"tag\">#video</span> Lorem ipsum ...</div>",
     *     "mediaUrl"": null,
     *     "videoUrl": "https://github.com/os2display/display-templates/raw/refs/heads/develop/src/fixtures/videos/test.mp4",
     *     "username": "username2",
     *     "createdTime": "2022-01-03T08:50:07",
     *   }
     * ]
     */
    final public const string STORY_OUTPUT = 'instagram';

    /**
     * Output Model: src/Feed/OutputModel/News/NewsOutput.php
     *
     * Data example:
     *
     * [
     *   {
     *     title: "Lorem ipsum dolor sit amet.",
     *     lastModified: "2023-02-13T07:00:00.360Z",
     *     content: "Vestibulum sagittis lobortis purus quis tempor. Aliquam pretium vitae risus id condimentum.",
     *   }
     * ]
     */
    final public const string NEWS_OUTPUT = 'rss';
}
