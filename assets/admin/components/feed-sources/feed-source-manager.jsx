import { useEffect, useState } from "react";
import { useTranslation } from "react-i18next";
import { useNavigate } from "react-router-dom";
import FeedSourceForm from "./feed-source-form";
import {
  usePostV2FeedSourcesMutation,
  usePutV2FeedSourcesByIdMutation,
} from "../../../shared/redux/enhanced-api.ts";
import {
  displayError,
  displaySuccess,
} from "../util/list/toast-component/display-toast";
import idFromUrl from "../util/helpers/id-from-url";

/**
 * The theme manager component.
 *
 * @param {object} props The props.
 * @param {object} props.initialState Initial theme state.
 * @param {string} props.saveMethod POST or PUT.
 * @param {string | null} props.id Theme id.
 * @param {boolean} props.isLoading Is the theme state loading
 * @param {object} props.loadingError Loading error.
 * @returns {object} The theme form.
 */
function FeedSourceManager({
  saveMethod,
  id = null,
  isLoading = false,
  loadingError = null,
  initialState = null,
}) {
  // Hooks
  const { t } = useTranslation("common", {
    keyPrefix: "feed-source-manager",
  });
  const navigate = useNavigate();

  // State
  const [headerText] = useState(
    saveMethod === "PUT" ? t("edit-feed-source") : t("create-new-feed-source"),
  );

  const [loadingMessage, setLoadingMessage] = useState(
    t("loading-messages.loading-feed-source"),
  );

  const [submitting, setSubmitting] = useState(false);
  const [formStateObject, setFormStateObject] = useState({});
  const [saveWithoutClose, setSaveWithoutClose] = useState(false);

  const [
    postV2FeedSources,
    { error: saveErrorPost, isSuccess: isSaveSuccessPost, data },
  ] = usePostV2FeedSourcesMutation();

  const [
    PutV2FeedSourcesById,
    { error: saveErrorPut, isSuccess: isSaveSuccessPut },
  ] = usePutV2FeedSourcesByIdMutation();

  const feedSourceTypeOptions = [
    {
      value: "App\\Feed\\CalendarApiFeedType",
      title: t("dynamic-fields.calendar-api-feed-type.title"),
      key: "CalendarApiFeedType",
      secretsDefault: {
        locations: [],
      },
    },
    {
      value: "App\\Feed\\ColiboFeedType",
      title: t("colibo-feed-type.title"),
      key: "ColiboFeedType",
      secretsDefault: {
        api_base_uri: "",
        client_id: "",
        client_secret: "",
        recipients: [],
      },
    },
    {
      value: "App\\Feed\\EventDatabaseApiFeedType",
      title: t("dynamic-fields.event-database-api-feed-type.title"),
      key: "EventDatabaseApiFeedType",
      secretsDefault: {
        host: "",
      },
    },
    {
      value: "App\\Feed\\EventDatabaseApiV2FeedType",
      title: t("event-database-api-v2-feed-type.title"),
      key: "EventDatabaseApiV2FeedType",
      secretsDefault: {
        host: "",
        apikey: "",
      },
    },
    {
      value: "App\\Feed\\NotifiedFeedType",
      title: t("dynamic-fields.notified-feed-type.title"),
      key: "NotifiedFeedType",
      secretsDefault: {
        token: "",
      },
    },
    {
      value: "App\\Feed\\RssFeedType",
      title: t("dynamic-fields.rss-feed-type.title"),
      key: "RssFeedType",
      secretsDefault: {},
    },
    {
      value: "App\\Feed\\BrndFeedType",
      title: t("brnd-feed-type.title"),
      key: "BrndFeedType",
      secretsDefault: {
        api_base_uri: "",
        company_id: "",
        api_auth_key: "",
        api_version: "1.0",
      },
    },
  ];

  /**
   * Set state on change in input field
   *
   * @param {object} props - The props.
   * @param {object} props.target - Event target.
   */
  const handleInput = ({ target }) => {
    const localFormStateObject = { ...formStateObject };
    localFormStateObject[target.id] = target.value;
    setFormStateObject(localFormStateObject);
  };

  /** Set loaded data into form state. */
  useEffect(() => {
    const newState = { ...initialState };

    if (newState.secrets instanceof Array) {
      newState.secrets = {};
    }

    setFormStateObject(newState);
  }, [initialState]);

  const handleSecretInput = ({ target }) => {
    const secrets = { ...formStateObject.secrets };
    secrets[target.id] = target.value;
    setFormStateObject({ ...formStateObject, secrets });
  };

  const onFeedTypeChange = ({ target }) => {
    const { value } = target;
    const option = feedSourceTypeOptions.find((opt) => opt.value === value);
    const newFormStateObject = { ...formStateObject };
    newFormStateObject.feedType = value;
    newFormStateObject.secrets = { ...option.secretsDefault };
    setFormStateObject(newFormStateObject);
  };

  /** Save feed source. */
  function saveFeedSource() {
    setLoadingMessage(t("loading-messages.saving-feed-source"));

    if (saveMethod === "POST") {
      postV2FeedSources({
        feedSourceFeedSourceInputJsonld: JSON.stringify(formStateObject),
      });
    } else if (saveMethod === "PUT") {
      PutV2FeedSourcesById({
        feedSourceFeedSourceInputJsonld: JSON.stringify(formStateObject),
        id,
      });
    }
  }

  /** If the feed source is not loaded, display the error message */
  useEffect(() => {
    if (loadingError) {
      displayError(
        t("error-messages.load-feed-source-error", { id }),
        loadingError,
      );
    }
  }, [loadingError]);

  /** When the media is saved, the theme will be saved. */
  useEffect(() => {
    if (isSaveSuccessPost || isSaveSuccessPut) {
      setSubmitting(false);
      displaySuccess(t("success-messages.saved-feed-source"));

      if (saveWithoutClose) {
        setSaveWithoutClose(false);

        if (isSaveSuccessPost) {
          navigate(`/feed-sources/edit/${idFromUrl(data["@id"])}`);
        }
      } else {
        navigate(`/feed-sources/list`);
      }
    }
  }, [isSaveSuccessPut, isSaveSuccessPost]);

  /** Handles submit. */
  const handleSubmit = () => {
    setSubmitting(true);
    saveFeedSource();
  };

  const handleSaveNoClose = () => {
    setSaveWithoutClose(true);
    handleSubmit();
  };

  /** If the theme is saved with error, display the error message */
  useEffect(() => {
    if (saveErrorPut || saveErrorPost) {
      const saveError = saveErrorPut || saveErrorPost;
      setSubmitting(false);
      displayError(t("error-messages.save-feed-source-error"), saveError);
    }
  }, [saveErrorPut, saveErrorPost]);

  return (
    <>
      {formStateObject && (
        <FeedSourceForm
          feedSource={formStateObject}
          headerText={`${headerText}: ${formStateObject?.title}`}
          handleInput={handleInput}
          handleSubmit={handleSubmit}
          handleSaveNoClose={handleSaveNoClose}
          isLoading={isLoading || submitting}
          loadingMessage={loadingMessage}
          onFeedTypeChange={onFeedTypeChange}
          handleSecretInput={handleSecretInput}
          feedSourceTypeOptions={feedSourceTypeOptions}
          mode={saveMethod}
        />
      )}
    </>
  );
}

export default FeedSourceManager;
