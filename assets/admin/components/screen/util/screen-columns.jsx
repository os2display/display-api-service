import { useTranslation } from "react-i18next";
import SelectColumnHoc from "../../util/select-column-hoc";
import ColumnHoc from "../../util/column-hoc";
import ScreenStatus from "../screen-status";
import CampaignsButton from "./campaigns-button.jsx";
import ScreenGroupsButton from "./screen-groups-button.jsx";

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
      content: (screen) => <CampaignsButton screen={screen} />,
    },
  ];

  if (displayStatus) {
    columns.push({
      path: "status",
      label: t("columns.status"),
      content: (screen) => <ScreenStatus screen={screen} mode="minimal" />,
    });
  }

  return columns;
}

const ScreenColumns = ColumnHoc(getScreenColumns);
const SelectScreenColumns = SelectColumnHoc(getScreenColumns);

export { SelectScreenColumns, ScreenColumns };
