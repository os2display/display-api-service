<?php

namespace App\Feed;

class SupportedFeedOutputs
{
    /**
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
     * TODO: Describe data structure.
     */
    final public const string POSTER_OUTPUT = 'poster';

    /**
     * TODO: Describe data structure.
     */
    final public const string INSTAGRAM_OUTPUT = 'instagram';

    /**
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
    final public const string RSS_OUTPUT = 'rss';
}