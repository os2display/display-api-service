import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import {
  render,
  cleanup,
  waitFor,
  fireEvent,
  screen,
} from "@testing-library/react";

const { initiateMock, dispatchMock, setModalMock } = vi.hoisted(() => ({
  initiateMock: vi.fn(),
  dispatchMock: vi.fn(),
  setModalMock: vi.fn(),
}));

vi.mock("react-i18next", () => ({
  useTranslation: () => ({ t: (key) => key }),
}));

vi.mock("react-redux", () => ({
  useDispatch: () => dispatchMock,
}));

vi.mock("react-router-dom", () => ({
  Link: ({ to, children }) => <a href={to}>{children}</a>,
  useLocation: () => ({ pathname: "/screen/edit/x" }),
}));

vi.mock("../../shared/redux/enhanced-api.ts", () => ({
  enhancedApi: {
    endpoints: { getV2PlaylistsByIdSlides: { initiate: initiateMock } },
  },
}));

import UserContext from "../../admin/context/user-context";
import DeleteModalContext from "../../admin/context/modal-context/modal-context";
import { SelectPlaylistColumns } from "../../admin/components/playlist/playlists-columns";

const PLAYLIST_ULID = "01JQ0M8V0X1F0M3A5N9T4C7B2D";
const SLIDE_ULID = "01JQ0M8V0X1F0M3A5N9T4C7B2Z";

// The shape served by GET /v2/screens/{id}/regions/{regionId}/playlists. That
// operation normalizes with the `playlist-screen-region:read` group, which does
// not include the bare `id` property - only the JSON-LD `@id`.
const regionPlaylist = {
  "@id": `/v2/playlists/${PLAYLIST_ULID}`,
  "@type": "Playlist",
  title: "Region playlist",
  slidesLength: 2,
  region: "01JQ0M8V0X1F0M3A5N9T4C7B2E",
  weight: 0,
  published: { from: null, to: null },
};

// The shape served by GET /v2/playlists, which does include `id`.
const listPlaylist = {
  ...regionPlaylist,
  id: PLAYLIST_ULID,
};

function ColumnProbe({ playlist }) {
  const columns = SelectPlaylistColumns({
    handleDelete: vi.fn(),
    editTarget: "playlist",
  });
  const column = columns.find(({ key }) => key === "playlist");

  return <div>{column.content(playlist)}</div>;
}

function renderProbe(playlist) {
  return render(
    <UserContext.Provider
      value={{ selectedTenant: { get: { tenantKey: "current" } } }}
    >
      <DeleteModalContext.Provider
        value={{ setModal: setModalMock, selected: [], setSelected: vi.fn() }}
      >
        <ColumnProbe playlist={playlist} />
      </DeleteModalContext.Provider>
    </UserContext.Provider>,
  );
}

beforeEach(() => {
  initiateMock.mockReset();
  dispatchMock.mockReset();
  setModalMock.mockReset();

  initiateMock.mockImplementation((params) => ({ __initiate: true, params }));
  dispatchMock.mockImplementation((action) => {
    const params = action?.params ?? {};

    // An unknown (or empty) playlist id resolves to no slides, the way the
    // 404 from /v2/playlists//slides does.
    if (params.id !== PLAYLIST_ULID) {
      return Promise.resolve({
        data: { "hydra:member": [], "hydra:view": {} },
      });
    }

    return Promise.resolve({
      data: {
        "hydra:member": [
          {
            slide: { "@id": `/v2/slides/${SLIDE_ULID}`, title: "Slide A" },
          },
        ],
        "hydra:view": {},
      },
    });
  });
});

afterEach(() => cleanup());

describe("Playlist slides button", () => {
  it("looks up the slides of a playlist served without a bare id", async () => {
    renderProbe(regionPlaylist);

    fireEvent.click(screen.getByRole("button", { name: "2" }));

    await waitFor(() => expect(initiateMock).toHaveBeenCalled());

    expect(initiateMock.mock.calls[0][0].id).toBe(PLAYLIST_ULID);
  });

  it("links to the slide edit page from the info modal", async () => {
    renderProbe(regionPlaylist);

    fireEvent.click(screen.getByRole("button", { name: "2" }));

    await waitFor(() => expect(setModalMock).toHaveBeenCalled());

    cleanup();
    render(setModalMock.mock.calls[0][0].content);

    expect(screen.getByRole("link", { name: "Slide A" })).toHaveAttribute(
      "href",
      `/slide/edit/${SLIDE_ULID}`,
    );
  });

  it("still looks up the slides of a playlist from the playlist list", async () => {
    renderProbe(listPlaylist);

    fireEvent.click(screen.getByRole("button", { name: "2" }));

    await waitFor(() => expect(initiateMock).toHaveBeenCalled());

    expect(initiateMock.mock.calls[0][0].id).toBe(PLAYLIST_ULID);
  });
});
