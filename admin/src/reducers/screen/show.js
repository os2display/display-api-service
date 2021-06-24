import { combineReducers } from "redux";

export function error(state = null, action) {
  switch (action.type) {
    case "SCREEN_SHOW_ERROR":
      return action.error;

    case "SCREEN_SHOW_MERCURE_DELETED":
      return `${action.retrieved["@id"]} has been deleted by another user.`;

    case "SCREEN_SHOW_RESET":
      return null;

    default:
      return state;
  }
}

export function loading(state = false, action) {
  switch (action.type) {
    case "SCREEN_SHOW_LOADING":
      return action.loading;

    case "SCREEN_SHOW_RESET":
      return false;

    default:
      return state;
  }
}

export function retrieved(state = null, action) {
  switch (action.type) {
    case "SCREEN_SHOW_SUCCESS":
    case "SCREEN_SHOW_MERCURE_MESSAGE":
      return action.retrieved;

    case "SCREEN_SHOW_RESET":
      return null;

    default:
      return state;
  }
}

export function eventSource(state = null, action) {
  switch (action.type) {
    case "SCREEN_SHOW_MERCURE_OPEN":
      return action.eventSource;

    case "SCREEN_SHOW_RESET":
      return null;

    default:
      return state;
  }
}

export default combineReducers({ error, loading, retrieved, eventSource });
