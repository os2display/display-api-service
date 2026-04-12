import appStorage from "../util/app-storage";
import logger from "../logger/logger";

class TenantService {
  loadTenantConfig = () => {
    const token = appStorage.getToken();
    const tenantKey = appStorage.getTenantKey();
    const tenantId = appStorage.getTenantId();

    if (token && tenantKey && tenantId) {
      // Get fallback image.
      fetch(`/v2/tenants/${tenantId}`, {
        headers: {
          authorization: `Bearer ${token}`,
          "Authorization-Tenant-Key": tenantKey,
        },
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error(
              `Failed to fetch tenant (status: ${response.status})`,
            );
          }
          return response.json();
        })
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
