# Client

This is the client that will display slides from OS2Display.
See
[https://github.com/os2display/display-docs/blob/main/client.md](https://github.com/os2display/display-docs/blob/main/client.md)
for more info about the client.

## Config

The client can be configured by creating `public/config.json` with relevant values.
See `public/example_config.json` for example values.

Values explained:

* apiEndpoint - The endpoint where the API is located.
* loginCheckTimeout - How often (milliseconds) should the screen check for
status when it is not logged in, and waiting for being activated in the
administration.
* configFetchInterval - How often (milliseconds) should a fresh
config.json be fetched.
* refreshTokenTimeout - How often (milliseconds) should it be checked
whether the token needs to be refreshed?
* releaseTimestampIntervalTimeout - How often (milliseconds) should the
code check if a new release has been deployed, and reload if true?
* dataStrategy.config.interval - How often (milliseconds) should data be fetched
for the logged in screen?
* colorScheme.lat - Where is the screen located? Used for darkmode.
* colorScheme.lng - Where is the screen located? Used for darkmode.
* schedulingInterval - How often (milliseconds) should scheduling for the
screen be checked.
* debug - Should the screen be in debug mode? If true, the cursor will be
invisible.

All endpoint should be configured without a trailing slash. The endpoints `apiEndpoint` can be
left empty if the api is hosted from the root of the same domain as the client. E.g. if the api is at
<https://example.org> and the client is at <https://example.org/client>
