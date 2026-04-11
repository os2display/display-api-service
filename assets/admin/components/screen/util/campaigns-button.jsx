import { useTranslation } from "react-i18next";
import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import calculateIsPublished from "../../util/helpers/calculate-is-published.jsx";
import idFromUrl from "../../util/helpers/id-from-url.jsx";
import getAllPages from "../../util/helpers/get-all-pages.js";
import { useDispatch } from "react-redux";
import useModal from "../../../context/modal-context/modal-context-hook.jsx";
import { enhancedApi } from "../../../../shared/redux/enhanced-api.ts";
import { Button } from "react-bootstrap";

function getAllScreenGroupCampaigns(dispatch, screenGroupIds = []) {
  return screenGroupIds.reduce(
    (promise, groupId) =>
      promise.then((results) =>
        getAllPages(
          dispatch,
          enhancedApi.endpoints.getV2ScreenGroupsByIdCampaigns,
          { id: groupId },
        ).then((campaigns) => [...results, ...campaigns]),
      ),
    Promise.resolve([]),
  );
}

function getAllCampaigns(dispatch, campaignIds = [], results = []) {
  return new Promise((resolve) => {
    if (campaignIds.length === 0) {
      resolve(results);
    } else {
      const campaignId = campaignIds[0];

      dispatch(
        enhancedApi.endpoints.getV2PlaylistsById.initiate({
          id: campaignId,
        }),
      ).then(({ data }) => {
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
    getAllPages(dispatch, enhancedApi.endpoints.getV2ScreensByIdScreenGroups, {
      id: screen.id,
    })
      .then((screenGroups) => {
        const screenGroupIds = screenGroups
          .filter(({ campaignsLength }) => campaignsLength > 0)
          .map((group) => idFromUrl(group["@id"]));

        getAllScreenGroupCampaigns(dispatch, screenGroupIds).then(
          (screenGroupCampaigns) => {
            getAllPages(
              dispatch,
              enhancedApi.endpoints.getV2ScreensByIdCampaigns,
              { id: screen.id },
            ).then((screenCampaigns) => {
              const campaignRelations = [
                ...screenGroupCampaigns,
                ...screenCampaigns,
              ];
              const campaigns = campaignRelations.map(
                (campaignRelation) => campaignRelation.campaign,
              );
              const ids = new Set();
              const uniqueCampaigns = campaigns.filter(
                (campaign) =>
                  !ids.has(campaign["@id"]) && ids.add(campaign["@id"]),
              );

              getAllCampaigns(
                dispatch,
                uniqueCampaigns.map((campaign) => idFromUrl(campaign["@id"])),
              ).then((allCampaigns) => {
                setCampaigns(allCampaigns);
                setLoading(false);
              });
            });
          },
        );
      })
      .catch(() => setLoading(false));
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
              {calculateIsPublished(campaign.published) && (
                <span className="badge bg-success ms-2">Aktiv</span>
              )}
            </li>
          ))}
        </ul>
      );

      setModal({
        info: true,
        modalTitle: t("campaigns-modal-title"),
        content,
      });
    }
  }, [campaigns]);

  return (
    <Button
      variant={screen.activeCampaignsLength > 0 ? "primary" : "secondary"}
      type="button"
      onClick={onClick}
      disabled={loading || screen.campaignsLength === 0}
    >
      {loading && (
        <span
          className="spinner-border spinner-border-sm"
          role="status"
          aria-hidden="true"
        ></span>
      )}
      {!loading && (
        <>
          {screen.activeCampaignsLength <= 0 && screen.campaignsLength}
          {screen.activeCampaignsLength > 0 &&
            screen.activeCampaignsLength +
              "/" +
              screen.campaignsLength +
              " " +
              t("active")}
        </>
      )}
    </Button>
  );
}

export default CampaignsButton;
