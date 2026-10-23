import { useTranslation } from "react-i18next";
import ColumnHoc from "../util/column-hoc";
import SelectColumnHoc from "../util/select-column-hoc";
import useModal from "../../context/modal-context/modal-context-hook.jsx";
import { useDispatch } from "react-redux";
import { enhancedApi } from "../../../shared/redux/enhanced-api.ts";
import idFromUrl from "../util/helpers/id-from-url.jsx";
import getAllPages from "../util/helpers/get-all-pages.js";
import { Link } from "react-router-dom";
import { Button } from "react-bootstrap";

function ScreensButton({ group }) {
  const { t } = useTranslation("common", { keyPrefix: "groups-columns" });
  const { setModal } = useModal();
  const dispatch = useDispatch();

  const onClick = () => {
    getAllPages(dispatch, enhancedApi.endpoints.getV2ScreenGroupsByIdScreens, {
      id: idFromUrl(group.id),
    }).then((screens) => {
      const content = (
        <ul>
          {screens.map((screen) => (
            <li key={screen["@id"]}>
              <Link
                to={`screen/edit/${idFromUrl(screen["@id"])}`}
                target="_blank"
              >
                {screen.title}
              </Link>
            </li>
          ))}
        </ul>
      );

      setModal({
        info: true,
        modalTitle: t("screens-modal-title"),
        content,
      });
    });
  };

  return (
    <Button
      variant="secondary"
      type="button"
      onClick={onClick}
      disabled={group.screensLength === 0}
    >
      {group.screensLength}
    </Button>
  );
}

/**
 * Columns for group lists.
 *
 * @param {object} props - The props.
 * @param {Function} props.apiCall - The api to call
 * @param {string} props.infoModalRedirect - The url for redirecting in the info modal.
 * @param {string} props.infoModalTitle - The info modal title.
 * @returns {object} The columns for the group lists.
 */
function getGroupColumns({ apiCall, infoModalRedirect, infoModalTitle }) {
  const { t } = useTranslation("common", { keyPrefix: "groups-columns" });

  const columns = [
    {
      // eslint-disable-next-line react/prop-types
      content: (group) => <ScreensButton group={group} />,
      key: "screens",
      label: t("screens"),
    },
  ];

  return columns;
}

const GroupColumns = ColumnHoc(getGroupColumns);
const SelectGroupColumns = SelectColumnHoc(getGroupColumns);

export { SelectGroupColumns, GroupColumns };
