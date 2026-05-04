import { createRoot } from "react-dom/client";
import App from "./app.jsx";

const url = new URL(window.location.href);
const preview = url.searchParams.get("preview");
const previewId = url.searchParams.get("preview-id");

const container = document.getElementById("root");
const root = createRoot(container);

root.render(<App preview={preview} previewId={previewId} />);

if (
  "serviceWorker" in navigator &&
  import.meta.env.MODE === "production"
) {
  const register = () =>
    navigator.serviceWorker
      .register("/client/sw.js", { scope: "/" })
      .then((registration) =>
        console.log("Service worker registered:", registration.scope),
      )
      .catch((error) =>
        console.error("Service worker registration failed:", error),
      );

  if (document.readyState === "complete") {
    register();
  } else {
    window.addEventListener("load", register);
  }
}
