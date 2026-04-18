import { describe, it, expect } from "vitest";
import {
  screenForPlaylistPreview,
  screenForSlidePreview,
} from "../../client/util/preview";

describe("screenForPlaylistPreview", () => {
  const playlist = {
    "@id": "/v2/playlists/TEST01234567890123456789",
    "@type": "Playlist",
    title: "Test playlist",
  };

  it("returns a screen object with expected structure", () => {
    const screen = screenForPlaylistPreview(playlist);
    expect(screen["@id"]).toBe("/v2/screens/SCREEN01234567890123456789");
    expect(screen["@type"]).toBe("Screen");
    expect(screen.title).toBe("Preview");
  });

  it("has a single region", () => {
    const screen = screenForPlaylistPreview(playlist);
    expect(screen.regions).toHaveLength(1);
  });

  it("includes the playlist in regionData", () => {
    const screen = screenForPlaylistPreview(playlist);
    expect(screen.regionData.REGION01234567890123456789).toEqual([playlist]);
  });

  it("has a 1x1 grid layout", () => {
    const screen = screenForPlaylistPreview(playlist);
    expect(screen.layoutData.grid).toEqual({ rows: 1, columns: 1 });
  });

  it("has a single region in layout with gridArea ['a']", () => {
    const screen = screenForPlaylistPreview(playlist);
    expect(screen.layoutData.regions).toHaveLength(1);
    expect(screen.layoutData.regions[0].gridArea).toEqual(["a"]);
  });
});

describe("screenForSlidePreview", () => {
  const slide = {
    "@id": "/v2/slides/SLIDE01234567890123456789",
    "@type": "Slide",
    title: "Test slide",
  };

  it("wraps the slide in a playlist", () => {
    const screen = screenForSlidePreview(slide);
    const playlists = screen.regionData.REGION01234567890123456789;
    expect(playlists).toHaveLength(1);
    expect(playlists[0]["@type"]).toBe("Playlist");
    expect(playlists[0].slidesData).toEqual([slide]);
  });

  it("the wrapper playlist has empty schedules", () => {
    const screen = screenForSlidePreview(slide);
    const playlists = screen.regionData.REGION01234567890123456789;
    expect(playlists[0].schedules).toEqual([]);
  });

  it("produces a valid screen structure", () => {
    const screen = screenForSlidePreview(slide);
    expect(screen["@type"]).toBe("Screen");
    expect(screen.layoutData.grid).toEqual({ rows: 1, columns: 1 });
  });
});
