import appStorage from "../core/app-storage";
import logger from "../logger";
import { clientStore } from "../redux/store.js";
import { clientApi } from "../redux/generated-api.ts";

class TenantService {
  loadTenantConfig = () => {
    const token = appStorage.getToken();
    const tenantKey = appStorage.getTenantKey();
    const tenantId = appStorage.getTenantId();

    if (token && tenantKey && tenantId) {
      const request = clientStore.dispatch(
        clientApi.endpoints.getV2TenantsById.initiate({ id: tenantId }),
      );
      request
        .unwrap()
        .then((tenantData) => {
          if (tenantData?.fallbackImageUrl) {
            appStorage.setFallbackImageUrl(tenantData.fallbackImageUrl);
          }
        })
        .catch((err) => {
          logger.error(`Failed to load tenant config: ${err.message}`);
        })
        .finally(() => {
          request.unsubscribe();
        });
    }
  };
}

// Singleton.
const tenantService = new TenantService();

export default tenantService;
