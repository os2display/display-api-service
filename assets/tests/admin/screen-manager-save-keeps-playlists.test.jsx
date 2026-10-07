import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, cleanup, act } from "@testing-library/react";

const { capturedFormProps, putMock, postMock } = vi.hoisted(() => ({
  capturedFormProps: { current: null },
  putMock: vi.fn(),
  postMock: vi.fn(),
}));

vi.mock("../../admin/components/screen/screen-form", () => ({
  default: (props) => {
    capturedFormProps.current = props;
    return null;
  },
}));

vi.mock("react-i18next", () => ({
  useTranslation: () => ({ t: (key) => key }),
}));

vi.mock("react-router-dom", () => ({
  useNavigate: () => vi.fn(),
}));

vi.mock(
  "../../admin/components/util/list/toast-component/display-toast",
  () => ({
    displaySuccess: vi.fn(),
    displayError: vi.fn(),
  }),
);

vi.mock("../../shared/redux/enhanced-api.ts", () => ({
  usePutV2ScreensByIdMutation: () => [
    putMock,
    { error: undefined, isSuccess: false },
  ],
  usePostV2ScreensMutation: () => [
    postMock,
    { data: undefined, error: undefined, isSuccess: false },
  ],
}));

import ScreenManager from "../../admin/components/screen/screen-manager";

const SCREEN_ID = "01HZZZZZZZZZZZZZZZZZZZSCRN";
const REGION_ID = "01HZZZZZZZZZZZZZZZZZZZREGN";
const PLAYLIST_ID = "01HZZZZZZZZZZZZZZZZZZZPLST";
const GROUP_ID = "01HZZZZZZZZZZZZZZZZZZZGRUP";

const screenV1 = {
  "@id": `/v2/screens/${SCREEN_ID}`,
  "@type": "Screen",
  title: "Screen title",
  description: "",
  size: "55",
  layout: "/v2/layouts/01HZZZZZZZZZZZZZZZZZZZLAYO",
  location: "Somewhere",
  orientation: "horizontal",
  resolution: "HD",
  enableColorSchemeChange: false,
  createdBy: "someone",
  modifiedBy: "someone",
  modified: "2026-01-01T00:00:00+00:00",
  inScreenGroups: `/v2/screens/${SCREEN_ID}/screen-groups`,
  regions: [`/v2/screens/${SCREEN_ID}/regions/${REGION_ID}/playlists`],
};

/** Mimics what the form children push into the manager once they have loaded. */
function injectRelationsFromChildren() {
  act(() => {
    capturedFormProps.current.handleInput({
      target: {
        id: "regions",
        value: [{ "@id": `/v2/layouts/regions/${REGION_ID}` }],
      },
    });
  });
  act(() => {
    capturedFormProps.current.handleInput({
      target: {
        id: "playlists",
        value: [
          {
            "@id": `/v2/playlists/${PLAYLIST_ID}`,
            region: REGION_ID,
            weight: 0,
          },
        ],
      },
    });
  });
  act(() => {
    capturedFormProps.current.handleInput({
      target: {
        id: "inScreenGroups",
        value: [`/v2/screen-groups/${GROUP_ID}`],
      },
    });
  });
}

function lastPutPayload() {
  const call = putMock.mock.calls[putMock.mock.calls.length - 1][0];
  return JSON.parse(call.screenScreenInputJsonld);
}

beforeEach(() => {
  capturedFormProps.current = null;
  putMock.mockReset();
  postMock.mockReset();
});

afterEach(() => cleanup());

describe("ScreenManager repeated save", () => {
  it("keeps region playlists and screen groups when saved after a refetch", () => {
    const { rerender } = render(
      <ScreenManager saveMethod="PUT" id={SCREEN_ID} initialState={screenV1} />,
    );

    injectRelationsFromChildren();

    act(() => capturedFormProps.current.handleSubmitWithoutRedirect());

    // Sanity: the first save carries the playlist and the group.
    expect(lastPutPayload().regions).toEqual([
      { regionId: REGION_ID, playlists: [{ id: PLAYLIST_ID, weight: 0 }] },
    ]);
    expect(lastPutPayload().groups).toEqual([GROUP_ID]);

    // The save invalidates the "Screens" tag, so GET /v2/screens/{id} refetches
    // and hands down a new object identity with a bumped `modified`.
    rerender(
      <ScreenManager
        saveMethod="PUT"
        id={SCREEN_ID}
        initialState={{ ...screenV1, modified: "2026-01-02T00:00:00+00:00" }}
      />,
    );

    // No child re-injects its relations, so the next save must not be destructive.
    act(() => capturedFormProps.current.handleSubmitWithoutRedirect());

    expect(lastPutPayload().regions).toEqual([
      { regionId: REGION_ID, playlists: [{ id: PLAYLIST_ID, weight: 0 }] },
    ]);
    expect(lastPutPayload().groups).toEqual([GROUP_ID]);
  });
});
