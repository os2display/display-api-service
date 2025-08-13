import bookReviewConfig from "./book-review/book-review.json";
import calendarConfig from "./calendar/calendar.json";
import contactsConfig from "./contacts/contacts.json";
import iframeConfig from "./iframe/iframe.json";
import imageTextConfig from "./image-text/image-text.json";
import posterConfig from "./poster/poster.json";
import rssConfig from "./rss/rss.json";
import slideshowConfig from "./slideshow/slideshow.json";
import instagramFeedConfig from "./instagram-feed/instagram-feed.json";
import newsFeedConfig from "./news-feed/news-feed.json";
import tableConfig from "./table/table.json";
import travelConfig from "./travel/travel.json";
import videoConfig from "./video/video.json";
import vimeoPlayerConfig from "./vimeo-player/vimeo-player.json";

function getSlideConfig(templateUlid) {
  switch (templateUlid) {
    // BookReview
    case "01FP2SME0ENTXWF362XHM6Z1B4":
      return bookReviewConfig;
    // Calendar
    case "01FRJPF4XATRN8PBZ35XN84PS6":
      return calendarConfig;
    // Contacts
    case "01FPZ19YEHX7MQ5Q6ZS0WK0VEA":
      return contactsConfig;
    // IFrame
    case "01FQBJQ2M3544ZKAADPWBXHY71":
      return iframeConfig;
    // ImageText
    case "01FP2SNGFN0BZQH03KCBXHKYHG":
      return imageTextConfig;
    // Poster
    case "01FWJZQ25A1868V63CWYYHQFKQ":
      return posterConfig;
    // RSS
    case "01FQC300GGWCA7A8H0SXY6P9FG":
      return rssConfig;
    // Slideshow
    case "01FP2SNSC9VXD10ZKXQR819NS9":
      return slideshowConfig;
    // InstagramFeed
    case "01FTZC0RKJYHG4JVZG5K709G46":
      return instagramFeedConfig;
    // NewsFeed
    case "01JEWPAFF93YSF418TH72W1SBA":
      return newsFeedConfig;
    // Table
    case "01FQBJFKM0YFX1VW5K94VBSNCP":
      return tableConfig;
    // Travel
    case "01FZD7K807VAKZ99BGSSCHRJM6":
      return travelConfig;
    // Video
    case "01FQBJFKM0YFX1VW5K94VBSNCC":
      return videoConfig;
    // Vimeo
    case "01FQBJQ2M3544ZKAADPWBXHY17":
      return vimeoPlayerConfig;
    default:
      return [];
  }
}

export default getSlideConfig;
