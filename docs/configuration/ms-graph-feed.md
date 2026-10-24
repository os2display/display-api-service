# MS Graph Feed (MsGraphFeedType)

`App\Feed\MsGraphFeedType` shows meetings from Microsoft 365 room (resource) mailboxes on calendar
slides. It outputs the shared **calendar** output model (`FeedOutputModels::CALENDAR_OUTPUT`), so it
renders in the same templates as `CalendarApiFeedType`.

The feed type needs **no environment variables** — everything is per tenant and stored encrypted in
the feed source's secrets.

## What you need in Azure/Entra ID

Create an **app registration** in the Azure portal (Entra ID → App registrations → New registration).
It needs no redirect URI; the feed authenticates with the OAuth2 *client credentials* flow, i.e. as
the application itself with no signed-in user.

From the app registration you need three values:

| Secret key      | Where in the portal                                              | Note                                                            |
| --------------- | ---------------------------------------------------------------- | --------------------------------------------------------------- |
| `tenant_id`     | Overview → **Directory (tenant) ID**                              | A GUID. A verified domain name (`contoso.onmicrosoft.com`) works too. |
| `client_id`     | Overview → **Application (client) ID**                            | This *is* the "App ID" — the portal uses both names for the same GUID. |
| `client_secret` | Certificates & secrets → New client secret → **Value**            | Copy the *Value*, not the *Secret ID*. It is only shown once and it expires (max 24 months) — the feed stops working when it does. |

### API permissions (application permissions, admin consent required)

| Permission       | Needed for                                                                                   |
| ---------------- | -------------------------------------------------------------------------------------------- |
| `Place.Read.All` | Listing room lists (locations) and rooms, i.e. the dropdowns in the admin and the room names.  |
| `Calendars.Read` | Reading the meetings of the room mailboxes.                                                    |

Pick **Application permissions**, not Delegated — there is no user in this flow. Then click
**Grant admin consent**.

> ⚠️ `Calendars.Read` as an application permission grants read access to **every** mailbox in the
> tenant, not just the rooms. Narrow it in Exchange Online to only the room mailboxes used for
> signage:
>
> ```powershell
> New-DistributionGroup -Name "Display Rooms" -Alias displayrooms -Type Security -Members room1@example.com
> New-ApplicationAccessPolicy -AppId <client_id> -PolicyScopeGroupId displayrooms@example.com `
>     -AccessRight RestrictAccess -Description "OS2display signage"
> ```

### Make the room subjects readable

Room mailboxes delete the meeting subject by default, which would leave every event without a title.
The feed falls back to "Optaget" in that case. To show the real subject (and the organizer), run per
room mailbox:

```powershell
Set-CalendarProcessing -Identity room1@example.com -DeleteSubject $false -AddOrganizerToSubject $false
```

Consider whether that is acceptable privacy-wise before putting it on a public screen.

### Room lists

The "locations" dropdown lists **room lists** (`Get-DistributionGroup -RoomList` in Exchange). If the
organisation has not organised its rooms into room lists, leave the selection empty — the feed then
offers all rooms in the tenant.

## Setting up the feed source

1. Admin → "Datakilder" → create a feed source of type **MS Graph kalender**.
2. Fill in Directory (tenant) ID, Application (client) ID and Client Secret, and save.
3. Re-open the feed source — the **Lokationer** dropdown is now populated from Graph. Pick the room
   lists the editors may use, or leave it empty for all rooms.

Secrets stored on the feed source:

```json
{
  "tenant_id": "11111111-2222-3333-4444-555555555555",
  "client_id": "66666666-7777-8888-9999-000000000000",
  "client_secret": "the secret value",
  "locations": ["building-a@example.com"]
}
```

Only `locations` is readable through the API; the three credentials are write-only.

## Per-feed configuration

When an editor creates a calendar slide on this feed source they get:

| Key                    | Input                       | Meaning                                                                             |
| ---------------------- | --------------------------- | ----------------------------------------------------------------------------------- |
| `resources`            | `multiselect-from-endpoint` | The rooms to show. Populated from the rooms of the selected room lists. **Required.** |
| `cache_expire_minutes` | number                      | How long Graph data may be reused. 1–1440 minutes, default 5.                         |

## Data window

`getData()` returns the events from **midnight today through midnight seven days later** — today plus
the following six calendar days — computed in the application time zone and queried from Graph as
UTC. Cancelled events are dropped and recurring meetings are expanded (the Graph `calendarView`
endpoint is used, not `events`).

Output (calendar output model):

```json
[
  {
    "id": "01HZX0P8Y1N5V4S3Q2R1T0W9ZA",
    "title": "Projektmøde",
    "startTime": 1719820800,
    "endTime": 1719824400,
    "resourceTitle": "Mødelokale 1",
    "resourceId": "room1@example.com"
  }
]
```

`startTime`/`endTime` are Unix timestamps; `resourceTitle` is the room's display name (falling back to
its email address if `Place.Read.All` is missing).

## Caching

Microsoft Graph throttles per app and per mailbox, and a wall of screens polls the same feed, so
everything is cached in the dedicated `ms.graph.cache` pool:

| Cached               | TTL                                                      |
| -------------------- | -------------------------------------------------------- |
| Access token         | `expires_in` minus 5 minutes (≈55 minutes)                |
| Room lists and rooms | 1 hour                                                    |
| Calendar view per room | `cache_expire_minutes` of the feed (default 5 minutes)  |

Cache keys include a fingerprint of the credentials, so rotating the client secret immediately
invalidates the cached token and data.

On top of this, `FeedService` caches the assembled feed response per feed (default 5 minutes, or the
feed's `cache_expire` in seconds if set) and caches an empty result for 30 seconds when the feed
fails — so a Graph outage does not hammer the API.

## Troubleshooting

Failures are logged on the `feed` channel.

| Symptom                                              | Likely cause                                                                       |
| ---------------------------------------------------- | ----------------------------------------------------------------------------------- |
| Dropdowns are empty after saving the feed source     | Wrong credentials, missing admin consent, or `Place.Read.All` not granted.            |
| `token request was rejected … invalid_client`        | Wrong client secret, or the secret has expired.                                       |
| Rooms list fine but a feed returns no events         | `Calendars.Read` missing, or an application access policy excludes that room mailbox. |
| All events are titled "Optaget"                      | The room mailbox deletes subjects — see "Make the room subjects readable".            |
| HTTP 429 from Graph                                  | Raise `cache_expire_minutes` on the feeds.                                            |
