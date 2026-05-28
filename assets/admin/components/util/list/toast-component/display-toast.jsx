/* eslint-disable react/prop-types */
import { toast } from "react-toastify";
import dayjs from "dayjs";
import "./display-toast.scss";

/** @param {string} text The toast display text */
export function displaySuccess(text) {
  const displayText = `${text} ${dayjs().format("HH:mm:ss")}`;

  toast.success(displayText);
}

/** @param {string} text The toast display text */
export function displayWarning(text) {
  const displayText = `${text} ${dayjs().format("HH:mm:ss")}`;

  toast.warning(displayText);
}

/**
 * @param {string} errorString - The toast display text
 * @param {object} error - The error
 */
export function displayError(errorString, error) {
  let errorText = "";

  if (error && error["hydra:description"]) {
    errorText = error["hydra:description"];
  }
  if (error?.data && typeof error.data === "object") {
    errorText = error.data["hydra:description"] || error.data.message || "";
  }
  // RTK Query couldn't JSON-parse the response — typically an HTML error page
  // from nginx/php-fpm rejecting the request before Symfony (e.g. 413 when the
  // upload exceeds the proxy body-size limit). Show the HTTP status instead
  // of leaking the "Unexpected token '<'" SyntaxError to the user.
  if (!errorText && error?.status === "PARSING_ERROR") {
    errorText = error.originalStatus
      ? `HTTP ${error.originalStatus}`
      : "Server returned an unexpected response";
  }
  if (!errorText && error?.error) {
    errorText = error.error;
  }

  const displayText = `${errorString} ${errorText} ${dayjs().format(
    "HH:mm:ss",
  )}`;

  toast.error(displayText, {
    autoClose: false,
  });
}
