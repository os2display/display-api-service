import appStorage from "../util/app-storage";
import logger from "../logger/logger";
import { clientStore } from "../redux/store.js";
import { clientApi } from "../redux/generated-api.ts";

class TenantService {
  loadTenantConfig = () => {
    const token = appStorage.getToken();
    const tenantKey = appStorage.getTenantKey();
    const tenantId = appStorage.getTenantId();

    if (token && tenantKey && tenantId) {
      clientStore
        .dispatch(
          clientApi.endpoints.getV2TenantsById.initiate({ id: tenantId }),
        )
        .unwrap()
        .then((tenantData) => {
          if (tenantData?.fallbackImageUrl) {
            appStorage.setFallbackImageUrl(tenantData.fallbackImageUrl);
          }
        })
        .catch((err) => {
          logger.error(`Failed to load tenant config: ${err.message}`);
        });
    }
  };
}

// Singleton.
const tenantService = new TenantService();

export default tenantService;
