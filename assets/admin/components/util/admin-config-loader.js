const DEFAULT_CONFIG = {
  rejseplanenApiKey: null,
  touchButtonRegions: false,
  showScreenStatus: false,
  enhancedPreview: false,
  loginScreenText: "",
  mediaMaxUploadSizeMb: 200,
  loginMethods: [
    {
      type: "username-password",
      enabled: true,
      provider: "username-password",
      label: null,
      icon: null,
    },
  ],
};

let configData = null;
let activePromise = null;

const AdminConfigLoader = {
  async loadConfig() {
    if (activePromise) {
      return activePromise;
    }
    if (configData !== null) {
      return configData;
    }

    activePromise = fetch("/config/admin")
      .then((response) => response.json())
      .then((data) => {
        configData = data;
        return configData;
      })
      .catch(() => {
        // eslint-disable-next-line no-console
        console.error("Could not load config. Will use default config.");
        configData = DEFAULT_CONFIG;
        return configData;
      })
      .finally(() => {
        activePromise = null;
      });

    return activePromise;
  },
};

Object.freeze(AdminConfigLoader);

export default AdminConfigLoader;
