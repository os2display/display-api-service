import { createContext, useState, useRef, useContext, useCallback } from "react";

const ClientStateContext = createContext();

/**
 * Provider that holds client state previously communicated via document events.
 * Services receive the callbacks ref at construction and call them directly.
 */
function ClientStateProvider({ children }) {
  const [screen, setScreen] = useState(null);
  const [isContentEmpty, setIsContentEmpty] = useState(false);
  const [regionSlides, setRegionSlides] = useState({});

  const updateRegionSlides = useCallback((regionId, slides) => {
    setRegionSlides((prev) => ({ ...prev, [regionId]: slides }));
  }, []);

  // Stable callbacks ref — services hold a reference to this object
  // and call its methods instead of dispatching document events.
  const callbacks = useRef({
    setScreen,
    setIsContentEmpty,
    updateRegionSlides,
    onRegionReady: () => {},
    onRegionRemoved: () => {},
    onReauthenticate: () => {},
  });

  const value = {
    screen,
    isContentEmpty,
    regionSlides,
    callbacks,
  };

  return (
    <ClientStateContext.Provider value={value}>
      {children}
    </ClientStateContext.Provider>
  );
}

function useClientState() {
  return useContext(ClientStateContext);
}

export { ClientStateProvider, useClientState };
