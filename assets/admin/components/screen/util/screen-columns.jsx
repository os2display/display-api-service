import { useTranslation } from "react-i18next";
import SelectColumnHoc from "../../util/select-column-hoc";
import ColumnHoc from "../../util/column-hoc";
import idFromUrl from "../../util/helpers/id-from-url";
import ScreenStatus from "../screen-status";
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
    dispatch(
      enhancedApi.endpoints.getV2ScreensByIdScreenGroups.initiate({
        id: idFromUrl(screen.id),
      }),
    ).then(({ data }) => {
      const content = (
        <ul>
          {data["hydra:member"].map((group) => (
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
    <Button variant="secondary" type="button" onClick={onClick} disabled={screen.inScreenGroupsLength === 0}>
      {screen.inScreenGroupsLength}
    </Button>
  );
}

/**
 * Columns for screens lists.
 *
 * @param {object} props - The props.
 * @param {boolean} props.displayStatus Should status be displayed?
 * @returns {object} The columns for the screens lists.
 */
function getScreenColumns({ displayStatus }) {
  const { t } = useTranslation("common", { keyPrefix: "screen-list" });

  const columns = [
    {
      content: (screen) => <ScreenGroupsButton screen={screen} />,
      key: "groups",
      label: t("columns.on-groups"),
    },
    {
      path: "location",
      label: t("columns.location"),
    },
    {
      key: "campaign",
      label: t("columns.campaign"),
      content: (screen) => {
        if (screen.activeCampaignsLength > 0)
          return t("overridden-by-campaign");
        return t("not-overridden-by-campaign");
      },
    },
  ];

  if (displayStatus) {
    columns.push({
      path: "status",
      label: t("columns.status"),
      content: (screen) => {
        return <ScreenStatus screen={screen} mode="minimal" />;
      },
    });
  }

  return columns;
}

const ScreenColumns = ColumnHoc(getScreenColumns);
const SelectScreenColumns = SelectColumnHoc(getScreenColumns);

export { SelectScreenColumns, ScreenColumns };
