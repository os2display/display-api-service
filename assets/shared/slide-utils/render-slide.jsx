import React from "react";
import BookReview from "../templates/book-review/book-review.jsx";
import Calendar from "../templates/calendar/calendar.jsx";
import Contacts from "../templates/contacts/contacts.jsx";
import ImageText from "../templates/image-text/image-text.jsx";
import IFrame from "../templates/iframe/iframe.jsx";
import Poster from "../templates/poster/poster.jsx";
import RSS from "../templates/rss/rss.jsx";
import Slideshow from "../templates/slideshow/slideshow.jsx";
import InstagramFeed from "../templates/instagram-feed/instagram-feed.jsx";
import NewsFeed from "../templates/news-feed/news-feed.jsx";
import Table from "../templates/table/table.jsx";
import Video from "../templates/video/video.jsx";
import Travel from "../templates/travel/travel.jsx";
import VimeoPlayer from "../templates/vimeo-player/vimeo-player.jsx";

const renderSlide = (slide, run, slideDone) => {
  switch (slide?.templateData?.id) {
    // BookReview
    case "01FP2SME0ENTXWF362XHM6Z1B4":
      return (
        <BookReview
          slide={slide}
          content={slide.content}
          run={run}
          slideDone={slideDone}
          executionId={slide.executionId}
        />
      );
    // Calendar
    case "01FRJPF4XATRN8PBZ35XN84PS6":
      return (
        <Calendar
          content={slide.content}
          slide={slide}
          run={run}
          slideDone={slideDone}
          executionId={slide.executionId}
        />
      );
    // Contacts
    case "01FPZ19YEHX7MQ5Q6ZS0WK0VEA":
      return (
        <Contacts
          content={slide.content}
          slide={slide}
          run={run}
          slideDone={slideDone}
          executionId={slide.executionId}
        />
      );
    // IFrame
    case "01FQBJQ2M3544ZKAADPWBXHY71":
      return (
        <IFrame
          content={slide.content}
          slide={slide}
          run={run}
          slideDone={slideDone}
          executionId={slide.executionId}
        />
      );
    // ImageText
    case "01FP2SNGFN0BZQH03KCBXHKYHG":
      return (
        <ImageText
          content={slide.content}
          slide={slide}
          run={run}
          slideDone={slideDone}
          executionId={slide.executionId}
        />
      );
    // Poster
    case "01FWJZQ25A1868V63CWYYHQFKQ":
      return (
        <Poster
          content={slide.content}
          slide={slide}
          run={run}
          slideDone={slideDone}
          executionId={slide.executionId}
        />
      );
    // RSS
    case "01FQC300GGWCA7A8H0SXY6P9FG":
      return (
        <RSS
          content={slide.content}
          slide={slide}
          run={run}
          slideDone={slideDone}
          executionId={slide.executionId}
        />
      );
    // Slideshow
    case "01FP2SNSC9VXD10ZKXQR819NS9":
      return (
        <Slideshow
          content={slide.content}
          slide={slide}
          run={run}
          slideDone={slideDone}
          executionId={slide.executionId}
        />
      );
    // InstagramFeed
    case "01FTZC0RKJYHG4JVZG5K709G46":
      return (
        <InstagramFeed
          content={slide.content}
          slide={slide}
          run={run}
          slideDone={slideDone}
          executionId={slide.executionId}
        />
      );
    // NewsFeed
    case "01JEWPAFF93YSF418TH72W1SBA":
      return (
        <NewsFeed
          content={slide.content}
          slide={slide}
          run={run}
          slideDone={slideDone}
          executionId={slide.executionId}
        />
      );
    // Table
    case "01FQBJFKM0YFX1VW5K94VBSNCP":
      return (
        <Table
          content={slide.content}
          slide={slide}
          run={run}
          slideDone={slideDone}
          executionId={slide.executionId}
        />
      );
    // Travel
    case "01FZD7K807VAKZ99BGSSCHRJM6":
      return (
        <Travel
          content={slide.content}
          slide={slide}
          run={run}
          slideDone={slideDone}
          executionId={slide.executionId}
        />
      );
    // Video
    case "01FQBJFKM0YFX1VW5K94VBSNCC":
      return (
        <Video
          content={slide.content}
          slide={slide}
          run={run}
          slideDone={slideDone}
          executionId={slide.executionId}
        />
      );
    // Vimeo
    case "01FQBJQ2M3544ZKAADPWBXHY17":
      return (
        <VimeoPlayer
          content={slide.content}
          slide={slide}
          run={run}
          slideDone={slideDone}
          executionId={slide.executionId}
        />
      );
    default:
      return (<div>Template not found!</div>);
  }
};

export default renderSlide;
