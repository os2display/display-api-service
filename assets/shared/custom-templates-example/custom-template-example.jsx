import templateConfig from "./custom-template-example.json";
import useBaseSlideExecution from "../slide-utils/useBaseSlideExecution.js";
import { ThemeStyles } from "../slide-utils/slide-util.jsx";

/**
 * Get the ULID of the template.
 * @return {string} The ULID of the template.
 */
function id() {
  return templateConfig.id;
}

/**
 * Get the config object of the template.
 * @return {{title: string, id: string, options: {}, adminForm: {}}}
 */
function config() {
  return templateConfig;
}

/**
 * Render the slide.
 * @param {object} slide The slide data.
 * @param {string} run A date string set when the slide should start running.
 * @param slideDone A function to invoke when the slide is done playing.
 * @return {JSX.Element} The component.
 */
function renderSlide(slide, run, slideDone) {
  return (
    <CustomTemplateExample
      slide={slide}
      run={run}
      slideDone={slideDone}
      content={slide.content}
      executionId={slide.executionId}
    />
  );
}

/**
 * @param {object} props Props.
 * @param {object} props.slide The slide.
 * @param {object} props.content The slide content.
 * @param {string} props.run A date string set when the slide should start running.
 * @param {Function} props.slideDone A function to invoke when the slide is done playing.
 * @param {string} props.executionId A unique id for the instance.
 * @returns {JSX.Element} The component.
 */
function CustomTemplateExample({
  slide,
  content,
  run,
  slideDone,
  executionId,
}) {
  const { duration = 15000 } = content;
  const { title = "Default title" } = content;

  useBaseSlideExecution({ slide, run, slideDone, duration });

  return (
    <>
      <div className="custom-template-example">
        <h1 className="title">{title}</h1>
      </div>

      {slide?.theme?.cssStyles && (
        <ThemeStyles id={executionId} css={slide.theme.cssStyles} />
      )}
    </>
  );
}

export default { id, config, renderSlide };
