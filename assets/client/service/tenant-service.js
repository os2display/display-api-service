import ConfigLoader from '../../shared/config-loader';
import appStorage from '../util/app-storage';

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
          'Authorization-Tenant-Key': tenantKey,
        },
      })
        .then((response) => response.json())
        .then((tenantData) => {
          if (tenantData?.fallbackImageUrl) {
            appStorage.setFallbackImageUrl(tenantData.fallbackImageUrl);
          }
        });
    }
  };
}

// Singleton.
const tenantService = new TenantService();

export default tenantService;
