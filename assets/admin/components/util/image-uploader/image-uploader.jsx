import { useEffect, useState, useCallback } from "react";
import { useDropzone } from "react-dropzone";
import { Button } from "react-bootstrap";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { faImage } from "@fortawesome/free-solid-svg-icons";
import { useTranslation } from "react-i18next";
import Image from "./image";
import MediaModal from "../../media-modal/media-modal";
import useModal from "../../../context/modal-context/modal-context-hook";
import "./image-uploader.scss";

/**
 * @param {object} props The props.
 * @param {array} props.inputImage The image object.
 * @param {Function} props.handleImageUpload Callback for image upload.
 * @param {string} props.name The name of the image field.
 * @param {boolean} props.multipleImages Whether the user should be able to
 *   upload multiple images.
 * @param {string | null} props.invalidText Text on error.
 * @param {boolean} props.showLibraryButton Whether to show the library button.
 * @returns {object} The image uploader.
 */
function ImageUploader({
  handleImageUpload,
  name,
  inputImage = [],
  multipleImages = false,
  invalidText = null,
  showLibraryButton = true,
}) {
  const { t } = useTranslation("common");
  const { setSelected } = useModal();
  const [images, setImages] = useState([]);
  const [error] = useState(false);
  const invalidInputText = invalidText || t("image-uploader.validation-text");
  const [showMediaModal, setShowMediaModal] = useState(false);

  /** @param {object} image The image with change. */
  const handleChange = (image) => {
    const localImages = [...images];
    const imageIndex = localImages.findIndex((img) => img.url === image.url);
    localImages[imageIndex] = image;

    const uniqueImages = [...new Set(localImages)];
    setImages(uniqueImages);

    const target = { value: uniqueImages, id: name };
    handleImageUpload({ target });
  };

  /** Sets the selected row in state. */
  const onCloseMediaModal = () => {
    setShowMediaModal(false);
  };
  /**
   * Sets the selected row in state.
   *
   * @param {Array} selectedImages The selected images from the modal
   */
  const onAcceptMediaModal = (selectedImages) => {
    setImages(selectedImages);
    const returnImages = selectedImages.map((image) => {
      return { "@id": image.id, ...image };
    });
    const target = { value: returnImages, id: name };
    handleImageUpload({ target });
    setShowMediaModal(false);
  };

  /** Load content from fixture. */
  useEffect(() => {
    if (inputImage) {
      setImages(Array.isArray(inputImage) ? inputImage : [inputImage]);
    }
  }, [inputImage]);

  const onDrop = useCallback(
    (acceptedFiles) => {
      const newImages = acceptedFiles.map((file) => ({
        file,
        url: URL.createObjectURL(file),
        title: "",
        description: "",
        license: "",
      }));

      const updatedImages = multipleImages
        ? [...images, ...newImages]
        : newImages;

      const uniqueImages = [
        ...new Map(updatedImages.map((item) => [item.url, item])).values(),
      ];

      setImages(uniqueImages);
      const target = { value: uniqueImages, id: name };
      handleImageUpload({ target });
    },
    [images, multipleImages, name, handleImageUpload]
  );

  const onImageRemove = (index) => {
    const updatedImages = images.filter((_, i) => i !== index);
    setImages(updatedImages);
    const target = { value: updatedImages, id: name };
    handleImageUpload({ target });
    setSelected([]);
  };

  const { getRootProps, getInputProps, isDragActive, open } = useDropzone({
    onDrop,
    accept: {
      "image/*": [".png", ".jpg", ".jpeg", ".gif", ".svg", ".webp"],
    },
    multiple: multipleImages,
    noClick: true,
    noKeyboard: true,
  });

  /* eslint-disable jsx-a11y/control-has-associated-label */
  return (
    // @TODO: error handling
    <div className={error ? "invalid" : ""}>
      <div className="upload__image-wrapper bg-light border p-3 pb-0 rounded">
        {(images.length === 0 || multipleImages) && (
          <>
            <Button
              variant="success"
              onClick={open}
              className="me-3"
            >
              {!multipleImages && t("image-uploader.pick-image")}
              {multipleImages && t("image-uploader.pick-more-images")}
            </Button>
            {showLibraryButton && (
              <Button
                variant="success"
                onClick={() => setShowMediaModal(true)}
              >
                {t("image-uploader.media-library")}
              </Button>
            )}
            <div {...getRootProps()}>
              <input {...getInputProps()} />
              <button
                type="button"
                className={
                  isDragActive
                    ? "drag-drop-area drag-drop-area-active"
                    : "drag-drop-area"
                }
                onClick={open}
              >
                <FontAwesomeIcon icon={faImage} />
              </button>
            </div>

            <small
              id="aria-label-for-drag-and-drop"
              className="form-text mb-3"
            >
              {t("image-uploader.help-text")}
            </small>
          </>
        )}
        {images.map((image, index) => {
          const key = image?.file ? image.file.name : image["@id"];
          return (
            <Image
              inputImage={image}
              handleChange={handleChange}
              onImageRemove={() => onImageRemove(index)}
              index={index}
              key={`image-${key}`}
            />
          );
        })}
      </div>
      {error && (
        <div className="invalid-feedback-image-uploader">
          {invalidInputText}
        </div>
      )}
      {showMediaModal && (
        <MediaModal
          images={images}
          show={showMediaModal}
          onClose={onCloseMediaModal}
          handleAccept={onAcceptMediaModal}
          multiple={multipleImages}
        />
      )}
    </div>
  );
  /* eslint-enable jsx-a11y/control-has-associated-label */
}

export default ImageUploader;
