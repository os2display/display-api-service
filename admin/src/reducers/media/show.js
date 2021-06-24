import { combineReducers } from "redux";

export function error(state = null, action) {
  switch (action.type) {
    case "MEDIA_SHOW_ERROR":
      return action.error;

    case "MEDIA_SHOW_MERCURE_DELETED":
      return `${action.retrieved["@id"]} has been deleted by another user.`;

    case "MEDIA_SHOW_RESET":
      return null;

    default:
      return state;
  }
}

export function loading(state = false, action) {
  switch (action.type) {
    case "MEDIA_SHOW_LOADING":
      return action.loading;

    case "MEDIA_SHOW_RESET":
      return false;

    default:
      return state;
  }
}

export function retrieved(state = null, action) {
  switch (action.type) {
    case "MEDIA_SHOW_SUCCESS":
    case "MEDIA_SHOW_MERCURE_MESSAGE":
      return action.retrieved;

    case "MEDIA_SHOW_RESET":
      return null;

    default:
      return state;
  }
}

export function eventSource(state = null, action) {
  switch (action.type) {
    case "MEDIA_SHOW_MERCURE_OPEN":
      return action.eventSource;

    case "MEDIA_SHOW_RESET":
      return null;

    default:
      return state;
  }
}

export default combineReducers({ error, loading, retrieved, eventSource });
