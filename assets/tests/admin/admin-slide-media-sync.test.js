import { describe, it, expect } from "vitest";
import rebuildMediaFromContent from "../../admin/components/slide/slide-media-utils";

describe("Slide media sync", () => {
  it("returns media IRIs referenced in content fields", () => {
    const content = {
      mainImage: ["/v2/media/1", "/v2/media/2"],
      backgroundVideo: ["/v2/media/3"],
    };

    const result = rebuildMediaFromContent(content);

    expect(result).toEqual(["/v2/media/1", "/v2/media/2", "/v2/media/3"]);
  });

  it("excludes TEMP-- IDs that have not been uploaded yet", () => {
    const content = {
      mainImage: ["TEMP--abc123", "/v2/media/1"],
    };

    const result = rebuildMediaFromContent(content);

    expect(result).toEqual(["/v2/media/1"]);
  });

  it("removes media no longer referenced in any content field", () => {
    const content = {
      mainImage: ["/v2/media/2"],
    };

    const result = rebuildMediaFromContent(content);

    expect(result).toEqual(["/v2/media/2"]);
    expect(result).not.toContain("/v2/media/1");
  });

  it("returns empty array when all media is removed from content", () => {
    const content = {
      mainImage: [],
      backgroundVideo: ["/v2/media/3"],
    };

    const result = rebuildMediaFromContent(content);

    expect(result).toEqual(["/v2/media/3"]);
  });

  it("deduplicates media used across multiple content fields", () => {
    const content = {
      mainImage: ["/v2/media/1"],
      thumbnail: ["/v2/media/1"],
    };

    const result = rebuildMediaFromContent(content);

    expect(result).toEqual(["/v2/media/1"]);
  });

  it("handles non-existent content fields gracefully", () => {
    const content = {};

    const result = rebuildMediaFromContent(content);

    expect(result).toEqual([]);
  });

  it("handles nested content field paths", () => {
    const content = {
      sections: {
        hero: ["/v2/media/1"],
      },
    };

    const result = rebuildMediaFromContent(content);

    expect(result).toEqual(["/v2/media/1"]);
  });

  it("ignores non-media content values when scanning top-level keys", () => {
    const content = {
      images: ["/v2/media/1"],
      title: "Some text",
      separator: true,
      contacts: [{ name: "John", image: ["/v2/media/2"], tags: ["news"] }],
    };

    const result = rebuildMediaFromContent(content);

    expect(result).toContain("/v2/media/1");
    expect(result).toContain("/v2/media/2");
    expect(result).not.toContain("news");
  });

  it("does not include non-media string arrays from content", () => {
    const content = {
      images: ["/v2/media/1"],
      tags: ["news", "sports"],
    };

    const result = rebuildMediaFromContent(content);

    expect(result).toEqual(["/v2/media/1"]);
    expect(result).not.toContain("news");
    expect(result).not.toContain("sports");
  });

  it("avoids infinite recursion when content contains circular references", () => {
    const circular = { images: ["/v2/media/1"] };
    circular.self = circular; // create an explicit cycle

    expect(() => rebuildMediaFromContent(circular)).not.toThrow();

    const result = rebuildMediaFromContent(circular);
    expect(result).toContain("/v2/media/1");
  });
});
