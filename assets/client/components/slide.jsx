import {React} from "react";
import "./slide.scss";
import ErrorBoundary from "./error-boundary.jsx";
import logger from "../logger/logger";
import renderSlide from "../../shared/template/slide.jsx";

/**
 * Slide component.
 *
 * @param {object} props - Props.
 * @param {object} props.slide - The slide data.
 * @param {string} props.id - The unique slide id.
 * @param {string} props.run - Timestamp for when to run the slide.
 * @param {Function} props.slideDone - The function to call when the slide is done running.
 * @param {Function} props.slideError - Callback when slide encountered an error.
 * @returns {object} - The component.
 */
function Slide(
    {
        slide,
        id,
        run,
        slideDone,
        slideError,
    }
) {
    /**
     * Handle errors in ErrorBoundary.
     *
     * Call slideDone after a timeout to ensure progression.
     */
    const handleError = () => {
        logger.warn("Slide error boundary triggered.");

        setTimeout(() => {
            slideError(slide);
        }, 5000);
    };

    return (
        <div
            id={id}
            className="slide"
            data-run={run}
            data-execution-id={slide.executionId}
        >
            <ErrorBoundary errorHandler={handleError}>
                {renderSlide(slide, run, slideDone)}
            </ErrorBoundary>
        </div>
    );
}

export default Slide;
