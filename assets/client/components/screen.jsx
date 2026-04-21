import { useEffect, useRef } from "react";
import SunCalc from "suncalc";
import { createGrid } from "../../shared/grid-generator/grid-generator";
import Region from "./region.jsx";
import logger from "../core/logger.js";
import TouchRegion from "./touch-region.jsx";
import ClientConfigLoader from "../core/client-config-loader.js";
import constants from "../util/constants";
import "./screen.scss";

/**
 * Screen component.
 *
 * @param {object} props
 *   Props.
 * @param {object} props.screen
 *   The screen data.
 * @returns {object}
 *   The component.
 */
function Screen({ screen }) {
  const configColumns = screen?.layoutData?.grid?.columns || 1;
  const configRows = screen?.layoutData?.grid?.rows || 1;
  const gridTemplateColumns = "1fr ".repeat(configColumns);
  const gridTemplateRows = "1fr ".repeat(configRows);
  const colorSchemeIntervalRef = useRef(null);

  const rootStyle = {
    gridTemplateAreas: createGrid(configColumns, configRows),
    gridTemplateColumns,
    gridTemplateRows,
  };

  const refreshColorScheme = () => {
    logger.info("Refreshing color scheme.");

    ClientConfigLoader.loadConfig().then((config) => {
      const now = new Date();
      let colorScheme;

      if (config.colorScheme?.type === "library") {
        // Default to somewhere in Denmark.
        const times = SunCalc.getTimes(
          now,
          config.colorScheme?.lat ?? 56.0,
          config.colorScheme?.lng ?? 10.0,
        );

        if (now > times.sunrise && now < times.sunset) {
          logger.info("Light color scheme activated.");
          colorScheme = "color-scheme-light";
        } else {
          logger.info("Dark color scheme activated.");
          colorScheme = "color-scheme-dark";
        }
      } else {
        // Browser based.
        colorScheme = window?.matchMedia("(prefers-color-scheme: dark)").matches
          ? "color-scheme-dark"
          : "color-scheme-light";
      }

      // Set class name on html root.
      document.documentElement.classList.remove(
        "color-scheme-light",
        "color-scheme-dark",
      );
      document.documentElement.classList.add(colorScheme);
    });
  };

  useEffect(() => {
    if (screen?.enableColorSchemeChange) {
      logger.info("Enabling color scheme change.");
      refreshColorScheme();
      colorSchemeIntervalRef.current = setInterval(
        refreshColorScheme,
        constants.COLOR_SCHEME_REFRESH_INTERVAL,
      );
    }

    return () => {
      if (colorSchemeIntervalRef.current !== null) {
        clearInterval(colorSchemeIntervalRef.current);
        colorSchemeIntervalRef.current = null;
      }

      // Cleanup html root classes.
      document.documentElement.classList.remove(
        "color-scheme-light",
        "color-scheme-dark",
      );
    };
  }, [screen?.enableColorSchemeChange]);

  return (
    <div className="screen" style={rootStyle} id={screen["@id"]}>
      {screen?.layoutData?.regions?.map((region) => {
        if (region?.type === "touch-buttons") {
          return <TouchRegion key={region["@id"]} region={region} />;
        }
        return <Region key={region["@id"]} region={region} />;
      })}
    </div>
  );
}

export default Screen;
