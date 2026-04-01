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
import { useEffect, useState } from "react";
import calculateIsPublished from "../../util/helpers/calculate-is-published.jsx";

function ScreenGroupsButton({ screen }) {
  const { t } = useTranslation("common", { keyPrefix: "screen-columns" });
  const { setModal } = useModal();
  const dispatch = useDispatch();

  const onClick = () => {
    dispatch(
      enhancedApi.endpoints.getV2ScreensByIdScreenGroups.initiate({
        id: idFromUrl(screen.id)
      })
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
        content
      });
    });
  };

  return (
    <Button variant="secondary" type="button" onClick={onClick} disabled={screen.inScreenGroupsLength === 0}>
      {screen.inScreenGroupsLength}
    </Button>
  );
}

function getAllScreenGroups(dispatch, screenId = null, results = [], page = 1) {
  return new Promise((resolve, reject) => {
    if (screenId === null) {
      resolve(results);
    } else {
      dispatch(enhancedApi.endpoints.getV2ScreensByIdScreenGroups.initiate({
        id: screenId,
        page: page
      })).then(({ data }) => {
        const newResults = [...results, ...data["hydra:member"]];
        const hydraView = data["hydra:view"] ?? null;

        if (hydraView !== null && (hydraView["hydra:next"] ?? false)) {
          resolve(getAllScreenGroups(dispatch, screenId, newResults, page + 1));
        } else {
          resolve(newResults);
        }
      });
    }
  });
}

function getAllScreenGroupCampaigns(dispatch, screenGroupId = null, screenGroupIds = [], results = [], page = 1) {
  return new Promise((resolve, reject) => {
    if (screenGroupId === null) {
      resolve(results);
    } else {
      dispatch(enhancedApi.endpoints.getV2ScreenGroupsByIdCampaigns.initiate({
        id: screenGroupId,
        page: page
      })).then(({ data }) => {
        const newResults = [...results, ...data["hydra:member"]];
        const hydraView = data["hydra:view"] ?? null;

        if (hydraView !== null && (hydraView["hydra:next"] ?? false)) {
          resolve(getAllScreenGroupCampaigns(dispatch, screenGroupId, screenGroupIds, newResults, page + 1));
        } else {
          const newScreenGroupIds = screenGroupIds.filter((id) => id !== screenGroupId);
          if (newScreenGroupIds.length === 0) {
            resolve(newResults);
          } else {
            resolve(getAllScreenGroupCampaigns(dispatch, newScreenGroupIds[0], newScreenGroupIds, newResults, 1));
          }
        }
      });
    }
  });
}

function getAllScreenCampaigns(dispatch, screenId = null, results = [], page = 1) {
  return new Promise((resolve, reject) => {
    if (screenId === null) {
      resolve(results);
    } else {
      dispatch(enhancedApi.endpoints.getV2ScreensByIdCampaigns.initiate({
        id: screenId,
        page: page
      })).then(({ data }) => {
        const newResults = [...results, ...data["hydra:member"]];
        const hydraView = data["hydra:view"] ?? null;

        if (hydraView !== null && (hydraView["hydra:next"] ?? false)) {
          resolve(getAllScreenCampaigns(dispatch, screenId, newResults, page + 1));
        } else {
          resolve(newResults);
        }
      });
    }
  });
}

function getAllCampaigns(dispatch, campaignIds = [], results = []) {
  return new Promise((resolve, reject) => {
    if (campaignIds.length === 0) {
      resolve(results);
    } else {
      const campaignId = campaignIds[0];

      dispatch(enhancedApi.endpoints.getV2PlaylistsById.initiate({
        id: campaignId
      })).then(({ data }) => {
        const newResults = [...results, data];

        const newCampaignIds = campaignIds.filter((id) => id !== campaignId);

        if (newCampaignIds.length === 0) {
          resolve(newResults);
        } else {
          resolve(getAllCampaigns(dispatch, newCampaignIds, newResults));
        }
      });
    }
  });
}

function CampaignsButton({ screen }) {
  const { t } = useTranslation("common", { keyPrefix: "screen-columns" });
  const { setModal } = useModal();
  const dispatch = useDispatch();
  const [campaigns, setCampaigns] = useState([]);
  const [loading, setLoading] = useState(false);

  const onClick = () => {
    setLoading(true);
    // Fetch screen groups.
    // Fetch screen group campaigns.
    // Fetch screen campaigns.
    // Merge campaign arrays.
    // Set campaigns to trigger useEffect.
    getAllScreenGroups(dispatch, screen.id).then((screenGroups) => {
      const screenGroupIds = screenGroups.filter(({ campaignsLength }) => campaignsLength > 0).map((group) => idFromUrl(group["@id"]));

      getAllScreenGroupCampaigns(dispatch, screenGroupIds[0] ?? null, screenGroupIds).then((screenGroupCampaigns) => {
        getAllScreenCampaigns(dispatch, screen.id).then((screenCampaigns) => {
          const campaignRelations = [...screenGroupCampaigns, ...screenCampaigns];
          const campaigns = campaignRelations.map((campaignRelation) => campaignRelation.campaign);
          const ids = new Set();
          const uniqueCampaigns = campaigns.filter((campaign) => !ids.has(campaign["@id"]) && ids.add(campaign["@id"]));

          getAllCampaigns(dispatch, uniqueCampaigns.map((campaign) => idFromUrl(campaign["@id"]))).then((campaigns) => {
            setCampaigns(campaigns);
            setLoading(false);
          });
        });
      });
    }).catch(() => setLoading(false));
  };

  useEffect(() => {
    if (campaigns?.length > 0) {
      const content = (
        <ul>
          {campaigns.map((campaign) => (
            <li key={campaign["@id"]}>
              <Link
                to={`campaign/edit/${idFromUrl(campaign["@id"])}`}
                target="_blank"
              >
                {campaign.title}
              </Link>
              {
                calculateIsPublished(campaign.published) && <span className="badge bg-success ms-2">Aktiv</span>
              }
            </li>
          ))}
        </ul>
      );

      setModal({
        info: true,
        modalTitle: t("campaigns-modal-title"),
        content
      });
    }
  }, [campaigns]);

  return (
    <Button variant={screen.activeCampaignsLength > 0 ? "primary" : "secondary"} type="button" onClick={onClick}
            disabled={loading || screen.campaignsLength === 0}>
      {loading && <span className="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>}
      {!loading && (<>
          {screen.activeCampaignsLength <= 0 && screen.campaignsLength}
          {screen.activeCampaignsLength > 0 && screen.activeCampaignsLength + "/" + screen.campaignsLength + " " + t("active")}
        </>
      )}
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
      label: t("columns.on-groups")
    },
    {
      path: "location",
      label: t("columns.location")
    },
    {
      key: "campaign",
      label: t("columns.campaign"),
      content: (screen) => <CampaignsButton screen={screen} />
    }
  ];

  if (displayStatus) {
    columns.push({
      path: "status",
      label: t("columns.status"),
      content: (screen) => {
        return <ScreenStatus screen={screen} mode="minimal" />;
      }
    });
  }

  return columns;
}

const ScreenColumns = ColumnHoc(getScreenColumns);
const SelectScreenColumns = SelectColumnHoc(getScreenColumns);

export { SelectScreenColumns, ScreenColumns };
