import { createRoot } from "react-dom/client";
import { Provider } from "react-redux";
import { clientStore } from "./redux/store.js";
import { ClientStateProvider } from "./context/client-state-context.jsx";
import App from "./app.jsx";

const url = new URL(window.location.href);
const preview = url.searchParams.get("preview");
const previewId = url.searchParams.get("preview-id");

const container = document.getElementById("root");
const root = createRoot(container);

root.render(
  <Provider store={clientStore}>
    <ClientStateProvider>
      <App preview={preview} previewId={previewId} />
    </ClientStateProvider>
  </Provider>,
);
