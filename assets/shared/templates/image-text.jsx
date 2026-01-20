import imageTextConfig from "./image-text.json";
import ImageTextAdminForm from "./image-text/admin-form";
import ImageText from "./image-text/template";
import "../slide-utils/global-styles.css";
import "./image-text/image-text.scss";

function id() {
  return imageTextConfig.id;
}

function config() {
  return imageTextConfig;
}

function renderSlide(slide, run, slideDone) {
  return (
    <ImageText
      slide={slide}
      run={run}
      slideDone={slideDone}
      content={slide.content}
      executionId={slide.executionId}
    />
  );
}

function renderAdminForm(formStateObject, onChange, handleMedia, mediaData) {
  return (
    <ImageTextAdminForm
      slideContent={formStateObject}
      onSlideContentChange={onChange}
      handleMedia={handleMedia}
      mediaData={mediaData}
    />
  );
}

export default { id, config, renderSlide, renderAdminForm };
