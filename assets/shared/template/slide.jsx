import React from "react";
import BookReview from "./book-review/book-review.jsx";
import Calendar from "./calendar/calendar.jsx";
import Contacts from "./contacts/contacts.jsx";
import ImageText from "./image-text/image-text.jsx";
import IFrame from "./iframe/iframe.jsx";
import Poster from "./poster/poster.jsx";
import RSS from "./rss/rss.jsx";
import Slideshow from "./slideshow/slideshow.jsx";
import InstagramFeed from "./instagram-feed/instagram-feed.jsx";
import NewsFeed from "./news-feed/news-feed.jsx";
import Table from "./table/table.jsx";
import Video from "./video/video.jsx";
import Travel from "./travel/travel.jsx";
import VimeoPlayer from "./vimeo-player/vimeo-player.jsx";

const renderSlide = (slide, run, slideDone) => {
  switch (slide?.templateData?.id) {
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
      return (<div>Slide type not found!</div>);
  }
};

export default renderSlide;
