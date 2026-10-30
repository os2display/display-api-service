import { describe, it, expect } from "vitest";
import { render } from "@testing-library/react";
import videoTemplate from "../../shared/templates/video.jsx";

const slide = (content) => ({
  executionId: "video-execution-id",
  mediaData: { "/v2/media/1": { assets: { uri: "/media/test.mp4" } } },
  content: { video: ["/v2/media/1"], ...content },
});

// `run = false` so the useEffect never calls load()/play(), which jsdom lacks.
const renderVideo = (content) =>
  render(videoTemplate.renderSlide(slide(content), false, () => {}));

describe("Video template cropping", () => {
  it("fits the video by default, so it is not cropped on a portrait screen", () => {
    const { container } = renderVideo({});

    expect(container.querySelector("video")).toHaveClass("media-contain");
  });

  it("crops the video when the editor explicitly disabled fitting", () => {
    const { container } = renderVideo({ mediaContain: false });

    expect(container.querySelector("video")).not.toHaveClass("media-contain");
  });

  it("fits the video when the editor explicitly enabled fitting", () => {
    const { container } = renderVideo({ mediaContain: true });

    expect(container.querySelector("video")).toHaveClass("media-contain");
  });
});
