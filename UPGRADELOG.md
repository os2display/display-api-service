# Upgrade log

## 2.x -> 3.0.0

1. Upgrade the API to the latest version of 2.x.
2. Add the following environment variables to `.env.local`:

   These values were previously added to admin|client: `/public/config.json`.
   See `README.md` for configuration options.
3. Run doctrine migrate
4.
