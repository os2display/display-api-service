import { Spinner } from "react-bootstrap";

/**
 * The loading component for forms.
 *
 * @param {object} props The props.
 * @param {boolean} props.isLoading Indicator of whether the form is loading
 * @param {string} props.loadingMessage The loading message for the spinner
 * @returns {object} The loading component for forms.
 */
function LoadingComponent({ isLoading = false, loadingMessage = "" }) {
  return (
    <>
      {isLoading && (
        <div className="spinner-overlay">
          <div className="spinner-container">
            <Spinner animation="border" className="loading-spinner" />
            {loadingMessage && <h2>{loadingMessage}</h2>}
          </div>
        </div>
      )}
    </>
  );
}

export default LoadingComponent;
