import { describe, it, expect, vi, afterEach } from "vitest";
import { render, act, cleanup } from "@testing-library/react";
import imageTextTemplate from "../../shared/templates/image-text.jsx";

const images = [];

// jsdom never loads images, so record them and let each test decide when.
class FakeImage {
  constructor() {
    images.push(this);
  }
}

const slide = {
  executionId: "image-text-execution-id",
  mediaData: { "/v2/media/1": { assets: { uri: "/media/test.jpg" } } },
  content: { title: "Overskrift", text: "Tekst", image: ["/v2/media/1"] },
};

const renderImageText = () =>
  render(imageTextTemplate.renderSlide(slide, false, () => {}));

describe("Image-text template image loading", () => {
  afterEach(() => {
    cleanup();
    images.length = 0;
    vi.unstubAllGlobals();
  });

  it("holds the text back until the image has loaded", () => {
    vi.stubGlobal("Image", FakeImage);

    const { container } = renderImageText();

    expect(container.querySelector(".box")).toBeNull();

    act(() => images.find((image) => image.src === "/media/test.jpg").onload());

    expect(container.querySelector(".box")).not.toBeNull();
    expect(container.querySelector(".background-image")).not.toBeNull();
  });

  it("shows the text if the image fails to load", () => {
    vi.stubGlobal("Image", FakeImage);

    const { container } = renderImageText();

    act(() =>
      images.find((image) => image.src === "/media/test.jpg").onerror(),
    );

    expect(container.querySelector(".box")).not.toBeNull();
  });
});
