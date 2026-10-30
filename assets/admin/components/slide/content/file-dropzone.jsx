import { useDropzone } from "react-dropzone";
import { useTranslation } from "react-i18next";

/**
 * @param {object} props - The props.
 * @param {Function} props.onFilesAdded - Callback when files are added.
 * @param {Array | null} props.acceptedMimetypes - Mimetypes to accept.
 * @param {number} props.maxSizeMb - Maximum allowed file size in megabytes.
 * @returns {object} Dropzone component.
 */
function FileDropzone({
  onFilesAdded,
  acceptedMimetypes = null,
  maxSizeMb = 200,
}) {
  const { t } = useTranslation("common");

  // Use binary MiB so this threshold aligns with Symfony's `Assert\File`
  // `maxSize: 'NM'` semantics — otherwise a file rejected backend-side could
  // slip past the dropzone.
  const allowedSize = maxSizeMb * 1024 * 1024;

  const fileValidator = (file) => {
    if (file.size > allowedSize) {
      const largerThanText = t("file-dropzone.larger-than-allowed-size");
      return {
        code: "file-too-large",
        message: `${file.name} (${Math.floor(
          file.size / 1000000,
        )} MB) ${largerThanText} (${maxSizeMb} MB)`,
      };
    }

    return null;
  };

  const { getRootProps, getInputProps, fileRejections } = useDropzone({
    validator: fileValidator,
    accept: acceptedMimetypes,
    onDrop: (acceptedFiles) => {
      onFilesAdded([...acceptedFiles]);
    },
  });

  return (
    <>
      {/* TODO: Fix styling for dropzone: https://react-dropzone.js.org/#section-styling-dropzone */}
      {/* eslint-disable react/jsx-props-no-spreading */}
      <div {...getRootProps({ className: "dropzone drag-drop-area" })}>
        <input {...getInputProps()} />
        <div>
          <span>{t("file-dropzone.drag-and-drop-text")}</span>
        </div>
      </div>
      {/* eslint-enable react/jsx-props-no-spreading */}
      {fileRejections &&
        fileRejections.map(({ errors }) => (
          <>
            {errors.map((e) => (
              <div className="alert-danger p-2 mt-3 mb-3" key={e.code}>
                {e.message}
              </div>
            ))}
          </>
        ))}
    </>
  );
}

export default FileDropzone;
