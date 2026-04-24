import { describe, it, expect } from "vitest";
import constants from "../../client/util/constants";

describe("constants", () => {
  it("has all login status constants", () => {
    expect(constants.LOGIN_STATUS_READY).toBe("ready");
    expect(constants.LOGIN_STATUS_AWAITING_BIND_KEY).toBe("awaitingBindKey");
    expect(constants.LOGIN_STATUS_UNKNOWN).toBe("unknown");
  });

  it("has all token state constants", () => {
    expect(constants.TOKEN_EXPIRED).toBe("Expired");
    expect(constants.TOKEN_VALID_SHOULD_HAVE_BEEN_REFRESHED).toBe(
      "ValidShouldHaveBeenRefreshed"
    );
    expect(constants.TOKEN_VALID).toBe("Valid");
  });

  it("has all app status constants", () => {
    expect(constants.STATUS_INIT).toBe("init");
    expect(constants.STATUS_LOGIN).toBe("login");
    expect(constants.STATUS_RUNNING).toBe("running");
  });

  it("has all error code constants following ER1xx pattern", () => {
    expect(constants.ERROR_TOKEN_REFRESH_FAILED).toBe("ER101");
    expect(constants.ERROR_TOKEN_REFRESH_LOOP_FAILED).toBe("ER102");
    expect(constants.ERROR_TOKEN_EXP_IAT_NOT_SET).toBe("ER103");
    expect(constants.ERROR_RELEASE_FILE_NOT_LOADED).toBe("ER104");
    expect(constants.ERROR_TOKEN_EXPIRED).toBe("ER105");
    expect(constants.ERROR_TOKEN_VALID_SHOULD_HAVE_BEEN_REFRESHED).toBe(
      "ER106"
    );
  });

  it("has all sentinel constants", () => {
    expect(constants.NO_TOKEN).toBe("NO_TOKEN");
    expect(constants.NO_EXPIRE).toBe("NO_EXPIRE");
    expect(constants.NO_ISSUED_AT).toBe("NO_ISSUED_AT");
  });

  it("has campaign region ID", () => {
    expect(constants.CAMPAIGN_REGION_ID).toBe("01G112XBWFPY029RYFB8X2H4KD");
  });

  it("has timing constants", () => {
    expect(constants.SLIDE_ERROR_RECOVERY_TIMEOUT).toBe(5000);
    expect(constants.SLIDE_TRANSITION_TIMEOUT).toBe(1000);
    expect(constants.COLOR_SCHEME_REFRESH_INTERVAL).toBe(300000);
  });
});
