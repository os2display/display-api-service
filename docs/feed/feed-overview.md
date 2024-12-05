# Feed Overview

"Feeds" in OS2display are external data sources that can provide up-to-data to slides. The idea is that if you can set 
up slide based on a feed and publish it. The Screen Client will then fetch new data from the feed whenever the Slide is 
shown on screen.

The simplest example is a classic RSS news feed. You can set up a slide based on the RSS slide template, configure the 
RSS source URL, and whenever the slide is on screen it will show the latest entries from the RSS feed.

This means that administrators can set up slides and playlists that stays up to date automatically.

## Architecture

The "Feed" architecture is designed to enable both generic and custom feed types. To enable this all feed based screen 
templates are designed to support a given "feed output model". These are normalized data sets from a given feed type.

Each feed implementation defines which output model it supports. Thereby multiple feed implementations can support the 
same output model. This is done to enable decoupling of the screen templates from the feed implementation. 

For example:

* If you have a news source that is not a RSS feed you can implement a "FeedSource" that fetches data from your source
  then normalizes the data and outputs it as the RSS output model. When setting up RSS slides this feed source can then 
  be selected as the source for the slide.
* OS2display has calendar templates that can show bookings or meetings. To show data from your specific calendar or 
  booking system you can implement a "FeedSource" that fetches booking data from your source and normalizes it to match 
  the calendar output model.

@todo

Slide -> Feed -> FeedSource
Auth
Caching