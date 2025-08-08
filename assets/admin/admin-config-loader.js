let configData = null;
let activePromise = null;

const AdminConfigLoader = {
  async loadConfig() {
    if (activePromise) {
      return activePromise;
    }

    activePromise = new Promise((resolve) => {
      if (configData !== null) {
        resolve(configData);
      } else {
        fetch("/config/admin")
          .then((response) => response.json())
          .then((data) => {
            configData = data;
            resolve(configData);
          })
          .catch(() => {
            if (configData !== null) {
              resolve(configData);
            } else {
              // eslint-disable-next-line no-console
              console.error("Could not load config. Will use default config.");

              // Default config.
              resolve({
                rejseplanenApiKey: null,
                touchButtonRegions: false,
                showScreenStatus: false,
                enhancedPreview: false,
                loginMethods: [
                  {
                    type: "username-password",
                    enabled: true,
                    provider: "username-password",
                    label: null,
                    icon: null,
                  },
                ],
              });
            }
          })
          .finally(() => {
            activePromise = null;
          });
      }
    });

    return activePromise;
  },
};

Object.freeze(AdminConfigLoader);

export default AdminConfigLoader;
