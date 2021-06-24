import { combineReducers } from "redux";

export function error(state = null, action) {
  switch (action.type) {
    case "PLAYLIST_SHOW_ERROR":
      return action.error;

    case "PLAYLIST_SHOW_MERCURE_DELETED":
      return `${action.retrieved["@id"]} has been deleted by another user.`;

    case "PLAYLIST_SHOW_RESET":
      return null;

    default:
      return state;
  }
}

export function loading(state = false, action) {
  switch (action.type) {
    case "PLAYLIST_SHOW_LOADING":
      return action.loading;

    case "PLAYLIST_SHOW_RESET":
      return false;

    default:
      return state;
  }
}

export function retrieved(state = null, action) {
  switch (action.type) {
    case "PLAYLIST_SHOW_SUCCESS":
    case "PLAYLIST_SHOW_MERCURE_MESSAGE":
      return action.retrieved;

    case "PLAYLIST_SHOW_RESET":
      return null;

    default:
      return state;
  }
}

export function eventSource(state = null, action) {
  switch (action.type) {
    case "PLAYLIST_SHOW_MERCURE_OPEN":
      return action.eventSource;

    case "PLAYLIST_SHOW_RESET":
      return null;

    default:
      return state;
  }
}

export default combineReducers({ error, loading, retrieved, eventSource });
