import { useTranslation } from "react-i18next";
import idFromUrl from "../../util/helpers/id-from-url";
import getAllPages from "../../util/helpers/get-all-pages.js";
import { Button } from "react-bootstrap";
import useModal from "../../../context/modal-context/modal-context-hook.jsx";
import { enhancedApi } from "../../../../shared/redux/enhanced-api.ts";
import { useDispatch } from "react-redux";
import { Link } from "react-router-dom";

function ScreenGroupsButton({ screen }) {
  const { t } = useTranslation("common", { keyPrefix: "screen-columns" });
  const { setModal } = useModal();
  const dispatch = useDispatch();

  const onClick = () => {
    getAllPages(dispatch, enhancedApi.endpoints.getV2ScreensByIdScreenGroups, {
      id: idFromUrl(screen.id),
    }).then((groups) => {
      const content = (
        <ul>
          {groups.map((group) => (
            <li key={group["@id"]}>
              <Link
                to={`group/edit/${idFromUrl(group["@id"])}`}
                target="_blank"
              >
                {group.title}
              </Link>
            </li>
          ))}
        </ul>
      );

      setModal({
        info: true,
        modalTitle: t("screen-groups-modal-title"),
        content,
      });
    });
  };

  return (
    <Button
      variant="secondary"
      type="button"
      onClick={onClick}
      disabled={screen.inScreenGroupsLength === 0}
    >
      {screen.inScreenGroupsLength}
    </Button>
  );
}

export default ScreenGroupsButton;
