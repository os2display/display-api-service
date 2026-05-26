import { useContext, useEffect, useState } from "react";
import { useTranslation } from "react-i18next";
import { useDispatch } from "react-redux";
import { Alert } from "react-bootstrap";
import UserContext from "../../context/user-context";
import Schedule from "../util/schedule/schedule";
import { enhancedApi } from "../../../shared/redux/enhanced-api.ts";
import getAllPages from "../util/helpers/get-all-pages.js";
import ContentBody from "../util/content-body/content-body";
import TenantsDropdown from "../util/forms/multiselect-dropdown/tenants/tenants-dropdown";

/**
 * The playlist form component.
 *
 * @param {object} props - The props.
 * @param {object} props.playlist The playlist object to modify in the form.
 * @param {Function} props.handleInput Handles form input.
 * @param {boolean} props.highlightSharedSection - Highlight section concerning
 *   shared info
 * @returns {object} The playlist form.
 */
function PlaylistForm({
  handleInput,
  highlightSharedSection = false,
  playlist = null,
}) {
  const { t } = useTranslation("common", { keyPrefix: "playlist-form" });
  const context = useContext(UserContext);
  const dispatch = useDispatch();
  const [tenants, setTenants] = useState(null);

  useEffect(() => {
    let cancelled = false;
    getAllPages(dispatch, enhancedApi.endpoints.getV2Tenants, {
      itemsPerPage: 30,
    }).then((all) => {
      if (!cancelled) setTenants(all);
    });
    return () => {
      cancelled = true;
    };
  }, [dispatch]);

  return (
    <>
      {playlist && tenants && (
        <>
          <ContentBody>
            <h2 className="h4">{t("schedule-header")}</h2>
            <Schedule
              schedules={playlist.schedules}
              onChange={(schedules) =>
                handleInput({ target: { id: "schedules", value: schedules } })
              }
            />
          </ContentBody>
          <ContentBody
            id="shared-section"
            highlightSection={highlightSharedSection}
          >
            <h2 className="h4">{t("share-playlist")}</h2>
            <TenantsDropdown
              name="tenants"
              handleTenantSelection={handleInput}
              selected={playlist.tenants}
              data={tenants.filter(({ tenantKey }) => {
                return context.selectedTenant.get.tenantKey !== tenantKey;
              })}
            />
            <Alert className="mt-3 text-dark" variant="warning">
              {t("warning")}
            </Alert>
          </ContentBody>
        </>
      )}
    </>
  );
}

export default PlaylistForm;
