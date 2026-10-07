import { describe, it, expect, vi } from "vitest";
import { render, waitFor } from "@testing-library/react";
import FileSelector from "../../admin/components/slide/content/file-selector";

vi.mock("react-i18next", () => ({
  useTranslation: () => ({ t: (key) => key }),
}));

vi.mock("../../admin/components/slide/content/media-selector-modal", () => ({
  default: () => null,
}));

vi.mock("../../admin/components/slide/content/file-form-element", () => ({
  default: () => null,
}));

vi.mock("../../admin/components/util/admin-config-loader", () => ({
  default: {
    loadConfig: vi.fn(),
  },
}));

import AdminConfigLoader from "../../admin/components/util/admin-config-loader";

describe("FileSelector", () => {
  it("renders the loading placeholder and hides the dropzone until config resolves", async () => {
    let resolveConfig;
    AdminConfigLoader.loadConfig.mockReturnValue(
      new Promise((resolve) => {
        resolveConfig = resolve;
      }),
    );

    const { container, queryByText, findByText } = render(
      <FileSelector files={[]} onFilesChange={() => {}} name="media" />,
    );

    // Loading placeholder visible, dropzone (file input) not yet rendered.
    expect(queryByText("file-selector.loading")).toBeInTheDocument();
    expect(container.querySelector('input[type="file"]')).toBeNull();
    // The "Max-size" label is also gated until config arrives.
    expect(queryByText(/file-selector.max-size/)).toBeNull();

    resolveConfig({ mediaMaxUploadSizeMb: 50 });

    // After config resolves the dropzone replaces the placeholder.
    await waitFor(() => {
      expect(container.querySelector('input[type="file"]')).not.toBeNull();
    });
    expect(queryByText("file-selector.loading")).toBeNull();
    expect(await findByText(/50 MB/)).toBeInTheDocument();
  });
});
