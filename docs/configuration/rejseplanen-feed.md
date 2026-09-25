# Rejseplanen Feed

The RejseplanenFeedType supplies the stations shown by the "Rejseplanen" (travel) slide template. Editors
search for stations in the admin; the search runs on the server, so the Rejseplanen API key is never sent
to the browser.

## API key

The key is a single installation-wide environment variable:

```dotenv
ADMIN_REJSEPLANEN_APIKEY=
```

See [https://labs.rejseplanen.dk/](https://labs.rejseplanen.dk/) for information about acquiring a key.

The variable keeps its 2.x name so existing installations need no configuration change, but it is no
longer part of the public `/config/admin` response. Without a key the station search returns
`404 Not Found` and the admin shows an error.

## Feed source

Each tenant needs a feed source of type "Rejseplanen" before editors can set up travel slides. It has
no secrets. Create it in the admin under *Datakilder* → *Opret ny datakilde*, or with:

```shell
docker compose exec phpfpm bin/console app:feed:create-feed-source
```

When a tenant has exactly one Rejseplanen feed source, the admin selects it automatically on travel
slides.

## How it works

- The admin searches through `GET /v2/feed-sources/{id}/config/stations?search=<text>`, which requires
  an authenticated user. The server calls Rejseplanen's `location.name` endpoint and returns
  `[{"id": "<extId>", "name": "<name>"}]`.
- Search results are cached per search string for 24 hours in the `rejseplanen.cache` pool. Failed
  lookups are not cached.
- The selected stations are stored in the slide's feed configuration. The feed data (the `travel`
  output model) is the list of selected stations; the template builds the Rejseplanen departure board
  iframe from them.

## Slides created before 3.0

Travel slides from earlier versions store their stations in the slide content. They keep rendering
unchanged, but the old station field can no longer be edited: the admin shows a notice explaining how
to upgrade the slide (select the Rejseplanen feed source and the stations, then save). Once stations
are chosen in the feed they take precedence over the old ones. There is no automatic migration.
