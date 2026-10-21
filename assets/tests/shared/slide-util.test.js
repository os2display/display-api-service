import { describe, it, expect, vi, afterEach } from "vitest";

import { getMediaUrl } from "../../shared/slide-utils/slide-util";

const variants = (sizes) =>
  Object.fromEntries(
    sizes.map(([key, width, height]) => [
      key,
      { uri: `/media/cache/resolve/screen_${key}/x.jpg`, width, height },
    ]),
  );

const landscape = {
  assets: {
    uri: "/media/original.jpg",
    variants: variants([
      ["1920", 1920, 1037],
      ["3840", 3840, 2074],
      ["7680", 7680, 4149],
    ]),
  },
};

const portrait = {
  assets: {
    uri: "/media/original.jpg",
    variants: variants([
      ["1920", 1037, 1920],
      ["3840", 2074, 3840],
      ["7680", 4149, 7680],
    ]),
  },
};

describe("getMediaUrl", () => {
  it("picks the smallest variant covering both sides of the screen", () => {
    expect(getMediaUrl(landscape, { width: 1280, height: 720 })).toBe(
      "/media/cache/resolve/screen_1920/x.jpg",
    );
  });

  it("accepts a variant up to 10% short of the screen", () => {
    // 1920×1037 fills 1920×1080 at 1.04×; the 3840 variant would be 4× the
    // pixels for that.
    expect(getMediaUrl(landscape, { width: 1920, height: 1080 })).toBe(
      "/media/cache/resolve/screen_1920/x.jpg",
    );
    // 1920×1037 fills 1920×1200 at 1.16×: too soft, take the next size.
    expect(getMediaUrl(landscape, { width: 1920, height: 1200 })).toBe(
      "/media/cache/resolve/screen_3840/x.jpg",
    );
  });

  it("covers the screen when image and screen orientation differ", () => {
    // A portrait image filling a landscape 4K screen must be 3840 wide.
    expect(getMediaUrl(portrait, { width: 3840, height: 2160 })).toBe(
      "/media/cache/resolve/screen_7680/x.jpg",
    );
    // A landscape image filling a portrait HD screen must be 1920 tall.
    expect(getMediaUrl(landscape, { width: 1080, height: 1920 })).toBe(
      "/media/cache/resolve/screen_3840/x.jpg",
    );
  });

  it("falls back to the original when no variant covers the screen", () => {
    // 7680×4149 would fill 7680×4800 at 1.16×.
    expect(getMediaUrl(landscape, { width: 7680, height: 4800 })).toBe(
      "/media/original.jpg",
    );
  });

  it("falls back to the original without variants or a known screen size", () => {
    expect(
      getMediaUrl(
        { assets: { uri: "blob:preview" } },
        { width: 1920, height: 1080 },
      ),
    ).toBe("blob:preview");
    expect(getMediaUrl(landscape, { width: 0, height: 0 })).toBe(
      "/media/original.jpg",
    );
  });

  it("uses url for media without assets and null for missing media", () => {
    expect(
      getMediaUrl(
        { url: "https://example.com/feed.jpg" },
        { width: 1, height: 1 },
      ),
    ).toBe("https://example.com/feed.jpg");
    expect(getMediaUrl(undefined, { width: 1, height: 1 })).toBeNull();
  });

  describe("without a screen size", () => {
    afterEach(() => {
      vi.unstubAllGlobals();
    });

    it("reads the current screen size on every call", () => {
      // The display may settle on its resolution after the client starts.
      vi.stubGlobal("devicePixelRatio", 1);
      vi.stubGlobal("screen", { width: 1280, height: 720 });
      expect(getMediaUrl(landscape)).toBe(
        "/media/cache/resolve/screen_1920/x.jpg",
      );

      vi.stubGlobal("screen", { width: 3840, height: 2160 });
      expect(getMediaUrl(landscape)).toBe(
        "/media/cache/resolve/screen_3840/x.jpg",
      );
    });
  });
});
