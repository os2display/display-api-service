# Upgrade Guide

## 2.x -> 3.0.0

When upgrading from 2.x to 3.0.0 of OS2Display, a mayor change has been introduced to the project.
The Admin and Client apps that previously existed in separate repositories from the API, have been included in the API
repository.
The API repository has been renamed to https://github.com/os2display/display instead of
https://github.com/os2display/display-api-service since is consists of the complete OS2Display project.
The repositories for Admin and Client will be marked as deprecated.

Because of these changes, it will be necessary to adjust the server setup to match the new structure.

TODO: Describe how standard infrastructure is set up after the change.

### Upgrade steps

1. Upgrade the API to the latest version of 2.x.
2. Add the following environment variables to `.env.local`:

  ```dotenv
  ###> Admin configuration ###
  ADMIN_REJSEPLANEN_APIKEY=
  ADMIN_SHOW_SCREEN_STATUS=false
  ADMIN_TOUCH_BUTTON_REGIONS=false
  ADMIN_LOGIN_METHODS='[{"type":"username-password","enabled":true,"provider":"username-password","label":""}]'
  ADMIN_ENHANCED_PREVIEW=false
  ###< Admin configuration ###

  ###> Client configuration ###
  CLIENT_LOGIN_CHECK_TIMEOUT=20000
  CLIENT_REFRESH_TOKEN_TIMEOUT=300000
  CLIENT_RELEASE_TIMESTAMP_INTERVAL_TIMEOUT=600000
  CLIENT_SCHEDULING_INTERVAL=60000
  CLIENT_PULL_STRATEGY_INTERVAL=90000
  CLIENT_COLOR_SCHEME='{"type":"library","lat":56.0,"lng":10.0}'
  CLIENT_DEBUG=false
  ###< Client configuration ###
  ```

   These values were previously added to Admin and Client: `/public/config.json`.
   See [README.md](./README.md) for a description of the configuration options.
3. Run doctrine migrate.
