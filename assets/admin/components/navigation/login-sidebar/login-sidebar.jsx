import { useEffect, useState } from "react";
import DOMPurify from "dompurify";
import AdminConfigLoader from "../../util/admin-config-loader";
import Logo from "../logo";

const SANITIZE_OPTIONS = {
  ALLOWED_TAGS: ["strong", "em", "b", "i", "br", "p", "a", "span"],
  ALLOWED_ATTR: ["href", "title", "target", "rel", "class"],
};

const LoginSidebar = () => {
  const [customHtml, setCustomHtml] = useState("");

  useEffect(() => {
    AdminConfigLoader.loadConfig().then((cfg) => {
      const raw = cfg?.loginScreenText?.trim();
      if (raw) setCustomHtml(DOMPurify.sanitize(raw, SANITIZE_OPTIONS));
    });
  }, []);

  return (
    <div className="background-image-screens p-4 col-md-4">
      <Logo />
      {customHtml && (
        <div className="card text-white bg-dark mb-3 border border-color-white">
          <div className="card-body">
            <div
              className="card-text"
              // eslint-disable-next-line react/no-danger
              dangerouslySetInnerHTML={{ __html: customHtml }}
            />
          </div>
        </div>
      )}
    </div>
  );
};

export default LoginSidebar;
