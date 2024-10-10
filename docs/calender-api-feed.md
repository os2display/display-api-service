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

* The `id` should be unique for the location and is used to identify it in the resource relation.
* The `displayName` is the name of the location in the admin.

The environment variables

```dotenv
CALENDAR_API_FEED_SOURCE_MAPPING_LOCATION_ID=id
CALENDAR_API_FEED_SOURCE_MAPPING_LOCATION_DISPLAY_NAME=displayName
```

can be overridden if the feed properties have other names.

### Resources

```json
[
    {
        "id": "Resource Id 1",
        "locationId": "Location Id 1",
        "displayName": "Resource 1",
        "includedInEvents": true
    },
    {
        "id": "Resource Id 2",
        "locationId": "Location Id 1",
        "displayName": "Resource 2",
        "includedInEvents": false
    }
]
```

* The `id` should be unique for the resource.
* The `locationId` is the id of the location the resource belongs to.
* The `displayName` is the name the resource is presented by in templates and admin.
* The `includedInEvents` determines if the resource is included in the events endpoint.
  This property can be excluded in the data. If this is the case, it defaults to `true`.

The environment variables

```dotenv
CALENDAR_API_FEED_SOURCE_MAPPING_RESOURCE_ID=id
CALENDAR_API_FEED_SOURCE_MAPPING_RESOURCE_LOCATION_ID=locationId
CALENDAR_API_FEED_SOURCE_MAPPING_RESOURCE_DISPLAY_NAME=displayName
CALENDAR_API_FEED_SOURCE_MAPPING_RESOURCE_INCLUDED_IN_EVENTS=includedInEvents
```

can be overridden if the feed properties have other names.

### Events

```json
[
    {
        "id": "Event Id 1",
        "title": "Event Title 1",
        "startTime": "2025-02-15T13:00:00+02:00",
        "endTime": "2025-02-15T13:30:00+02:00",
        "resourceDisplayName": "Resource 1",
        "resourceId": "Resource Id 1"
    },
    {
        "id": "Event Id 2",
        "title": "Event Title 2",
        "startTime": "2025-02-15T15:00:00+02:00",
        "endTime": "2025-02-15T15:30:00+02:00",
        "resourceDisplayName": "Resource 1",
        "resourceId": "Resource Id 1"
    }
]
```

* The `id` should be a unique id for the event.
* The `title` is the title of the event.
* The `startTime` is the start time of the event. Should be formatted as an `ISO 8601 date`, e.g. `2004-02-15T15:00:00+02:00`.
* The `endTime` is the end time of the event. Should be formatted as an `ISO 8601 date`, e.g. `2004-02-15T15:30:00+02:00`.
* The `resourceDisplayName` is display name of the resource the event belongs to.
* The `resourceId` is the id of the resource the event belongs to.

The environment variables

```dotenv
CALENDAR_API_FEED_SOURCE_MAPPING_EVENT_ID=id
CALENDAR_API_FEED_SOURCE_MAPPING_EVENT_TITLE=title
CALENDAR_API_FEED_SOURCE_MAPPING_EVENT_START_TIME=startTime
CALENDAR_API_FEED_SOURCE_MAPPING_EVENT_END_TIME=endTime
CALENDAR_API_FEED_SOURCE_MAPPING_EVENT_RESOURCE_ID=resourceId
CALENDAR_API_FEED_SOURCE_MAPPING_EVENT_RESOURCE_DISPLAY_NAME=resourceDisplayName
```

can be overridden if the feed properties have other names.
