import { describe, it, expect, vi } from "vitest";
import { render, fireEvent, waitFor } from "@testing-library/react";
import FileDropzone from "../../admin/components/slide/content/file-dropzone";

vi.mock("react-i18next", () => ({
  useTranslation: () => ({ t: (key) => key }),
}));

const makeFileWithSize = (name, mime, sizeBytes) => {
  const file = new File(["x"], name, { type: mime });
  Object.defineProperty(file, "size", { value: sizeBytes });
  return file;
};

describe("FileDropzone", () => {
  it("rejects files larger than the configured limit and shows the limit in the message", async () => {
    const onFilesAdded = vi.fn();
    const { container, findByText } = render(
      <FileDropzone onFilesAdded={onFilesAdded} maxSizeMb={10} />,
    );

    const oversizeFile = makeFileWithSize(
      "huge.png",
      "image/png",
      11 * 1024 * 1024,
    );

    const input = container.querySelector('input[type="file"]');
    fireEvent.change(input, { target: { files: [oversizeFile] } });

    const errorRow = await findByText(/10 MB/);
    expect(errorRow).toBeInTheDocument();
    expect(onFilesAdded).not.toHaveBeenCalled();
  });

  it("accepts files within the configured limit", async () => {
    const onFilesAdded = vi.fn();
    const { container } = render(
      <FileDropzone onFilesAdded={onFilesAdded} maxSizeMb={10} />,
    );

    const okFile = makeFileWithSize("ok.png", "image/png", 5 * 1024 * 1024);

    const input = container.querySelector('input[type="file"]');
    fireEvent.change(input, { target: { files: [okFile] } });

    await waitFor(() => {
      expect(onFilesAdded).toHaveBeenCalledTimes(1);
    });
    expect(onFilesAdded.mock.calls[0][0][0].name).toBe("ok.png");
  });

  it("uses the default 200 MB limit when no maxSizeMb prop is passed", async () => {
    const onFilesAdded = vi.fn();
    const { container, findByText } = render(
      <FileDropzone onFilesAdded={onFilesAdded} />,
    );

    const oversizeFile = makeFileWithSize(
      "huge.png",
      "image/png",
      201 * 1024 * 1024,
    );

    const input = container.querySelector('input[type="file"]');
    fireEvent.change(input, { target: { files: [oversizeFile] } });

    const errorRow = await findByText(/200 MB/);
    expect(errorRow).toBeInTheDocument();
  });
});
