# Calendar API Feed

The CalendarApiFeedType retrieves locations, resources and events from 3 JSON endpoints
set in the environment variables:

```dotenv
CALENDAR_API_FEED_SOURCE_LOCATION_ENDPOINT=
CALENDAR_API_FEED_SOURCE_RESOURCE_ENDPOINT=
CALENDAR_API_FEED_SOURCE_EVENT_ENDPOINT=
```

## Mapping the json data

By default, the three endpoints should return data as follows:

### Locations

```json
[
    {
        "id": "Location Id 2",
        "displayName": "Location display name 1"
    },
    {
        "id": "Location Id 2",
        "displayName": "Location display name 2"
    }
]
```

* The `id` (Mapping key: LOCATION_ID) should be unique for the location and is used to identify it in the resource relation.
* The `displayName` (Mapping key: LOCATION_DISPLAY_NAME) is the name of the location in the admin.

### Resources

```json
[
    {
        "id": "Resource Id 1",
        "locationId": "Location Id 1",
        "name": "Resource-1",
        "displayName": "Resource 1",
        "includedInEvents": true
    },
    {
        "id": "Resource Id 2",
        "locationId": "Location Id 1",
        "name": "Resource-2",
        "displayName": "Resource 2",
        "includedInEvents": false
    }
]
```

* The `id` (Mapping key: RESOURCE_ID) should be unique for the resource.
* The `locationId` (Mapping key: RESOURCE_LOCATION_ID) is the id of the location the resource belongs to.
* The `name` (Mapping key: RESOURCE_NAME) is the name the resource is presented by in the admin selector.
* The `displayName` (Mapping key: RESOURCE_DISPLAY_NAME) is the name the resource is presented by in templates.
* The `includedInEvents` (Mapping key: RESOURCE_INCLUDED_IN_EVENTS) determines if the resource is included in the events
endpoint.
  This property can be excluded in the data. If this is the case, it defaults to `true`.

### Events

```json
[
    {
        "title": "Event Title 1",
        "startTime": "2025-02-15T13:00:00+02:00",
        "endTime": "2025-02-15T13:30:00+02:00",
        "resourceDisplayName": "Resource 1",
        "resourceId": "Resource Id 1"
    },
    {
        "title": "Event Title 2",
        "startTime": "2025-02-15T15:00:00+02:00",
        "endTime": "2025-02-15T15:30:00+02:00",
        "resourceDisplayName": "Resource 1",
        "resourceId": "Resource Id 1"
    }
]
```

* The `title` (Mapping key: EVENT_TITLE) is the title of the event.
* The `startTime` (Mapping key: EVENT_START_TIME) is the start time of the event.
Should be formatted as an `ISO 8601 date`, e.g. `2004-02-15T15:00:00+02:00`.
* The `endTime` (Mapping key: EVENT_END_TIME) is the end time of the event.
Should be formatted as an `ISO 8601 date`, e.g. `2004-02-15T15:30:00+02:00`.
* The `resourceDisplayName` (Mapping key: EVENT_RESOURCE_ID) is display name of the resource the event belongs to.
* The `resourceId` (Mapping key: EVENT_RESOURCE_DISPLAY_NAME) is the id of the resource the event belongs to.

## Overriding mappings

Mappings can be overridden changing the following environment variable:

```dotenv
CALENDAR_API_FEED_SOURCE_CUSTOM_MAPPINGS='{}'
```

E.g.

```dotenv
CALENDAR_API_FEED_SOURCE_CUSTOM_MAPPINGS='{
    "LOCATION_ID": "Example1",
    "LOCATION_DISPLAY_NAME": "Example2",
    "RESOURCE_ID": "Example3",
    "RESOURCE_LOCATION_ID": "Example4",
    "RESOURCE_NAME": "Example12",
    "RESOURCE_DISPLAY_NAME": "Example5",
    "RESOURCE_INCLUDED_IN_EVENTS": "Example6",
    "EVENT_TITLE": "Example7",
    "EVENT_START_TIME": "Example8",
    "EVENT_END_TIME": "Example9",
    "EVENT_RESOURCE_ID": "Example10",
    "EVENT_RESOURCE_DISPLAY_NAME": "Example11"
}'
```

## Dates

By default, dates are assumed to be `Y-m-d\TH:i:sP` e.g. `2004-02-15T15:00:00+02:00`.

If another date format is supplied for the date fields, these can be set with:

```dotenv
CALENDAR_API_FEED_SOURCE_DATE_FORMAT=
CALENDAR_API_FEED_SOURCE_DATE_TIMEZONE=
```

E.g.

```dotenv
CALENDAR_API_FEED_SOURCE_DATE_FORMAT="m/d/YH:i:s"
CALENDAR_API_FEED_SOURCE_DATE_TIMEZONE="Europe/Copenhagen"
```

## Modifiers

Modifiers can be set up to modify the output of the feed.

Two types of modifiers are available:

* EXCLUDE_IF_TITLE_NOT_CONTAINS: Removes entries from the feed if the title does not contain the pattern.
* REPLACE_TITLE_IF_CONTAINS: Changes the title if it contains the pattern.

Parameters:

* type: EXCLUDE_IF_TITLE_NOT_CONTAINS or REPLACE_TITLE_IF_CONTAINS
* id: Unique identifier for the modifier.
* title: Display name when showing the modifier in the admin.
* description: Description of the modifier.
* activateInFeed: Should this filter be optional? If false the rule will always apply.
* removeTrigger: Should the trigger word be filtered from the title?
* pattern: The PCRE regular expression. See <https://www.php.net/manual/en/reference.pcre.pattern.syntax.php>.
* replacement: The string to replace the title with. See <https://www.php.net/manual/en/function.preg-replace.php>.

Examples of modifiers:

```json
[
    {
        "type": "EXCLUDE_IF_TITLE_NOT_CONTAINS",
        "id": "excludeIfNotContainsListe",
        "title": "Vis kun begivenheder med (liste) i titlen.",
        "description": "Denne mulighed fjerner begivenheder, der IKKE har (liste) i titlen. Den fjerner også (liste) fra titlen.",
        "activateInFeed": true,
        "pattern": "\/\\(liste\\)\/i",
        "removeTrigger": true
    },
    {
        "type": "REPLACE_TITLE_IF_CONTAINS",
        "activateInFeed": false,
        "id": "replaceIfContainsOptaget",
        "pattern": "\/\\(optaget\\)\/i",
        "replacement": "Optaget",
        "removeTrigger": true
    },
    {
        "type": "REPLACE_TITLE_IF_CONTAINS",
        "activateInFeed": true,
        "id": "onlyShowAsOptaget",
        "title": "Overskriv alle titler med Optaget",
        "description": "Denne mulighed viser alle titler som Optaget.",
        "pattern": "\/\/",
        "replacement": "Optaget",
        "removeTrigger": false
    }
]
```
