import { Component } from "react";
import logger from "../logger/logger";
import fallback from "../assets/fallback.png";
import "./error-boundary.scss";

class ErrorBoundary extends Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false, errorMessage: null, errorStackTrace: null };
  }

  // Update state so the next render will show the fallback UI.
  static getDerivedStateFromError() {
    return { hasError: true };
  }

  componentDidUpdate(prevProps) {
    if (this.props.resetKey !== prevProps.resetKey && this.state.hasError) {
      this.setState({ hasError: false, errorMessage: null, errorStackTrace: null });
    }
  }

  componentDidCatch(error, errorInfo) {
    logger.error(`ErrorBoundary caught error: ${error}`, errorInfo);

    const { errorHandler = () => {} } = this.props;
    errorHandler(error, errorInfo);

    this.setState({
      errorMessage: error.message.toString(),
      errorStackTrace: errorInfo.componentStack,
    });
  }

  render() {
    const { hasError, errorMessage, errorStackTrace } = this.state;

    if (hasError) {
      return (
        <div
          className="error-boundary"
          style={{ backgroundImage: `url(${fallback})` }}
        >
          <div className="error-boundary-box">
            <div className="error-boundary-header">Seneste log hændelser</div>
            <pre className="error-boundary-stacktrace">
              {errorMessage}
              {errorStackTrace}
            </pre>
          </div>
        </div>
      );
    }

    const { children } = this.props;
    return <>{children}</>;
  }
}

export default ErrorBoundary;
