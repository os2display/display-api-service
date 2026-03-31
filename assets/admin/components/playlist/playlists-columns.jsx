import { useTranslation } from "react-i18next";
import ColumnHoc from "../util/column-hoc";
import SelectColumnHoc from "../util/select-column-hoc";
import DateValue from "../util/date-value";
import PublishingStatus from "../util/publishingStatus";
import useModal from "../../context/modal-context/modal-context-hook.jsx";
import { useDispatch } from "react-redux";
import { enhancedApi } from "../../../shared/redux/enhanced-api.ts";
import idFromUrl from "../util/helpers/id-from-url.jsx";
import { Link } from "react-router-dom";
import { Button } from "react-bootstrap";

function SlidesButton({ playlist }) {
  const { t } = useTranslation("common", { keyPrefix: "playlists-columns" });
  const { setModal } = useModal();
  const dispatch = useDispatch();

  const onClick = () => {
    dispatch(
      enhancedApi.endpoints.getV2PlaylistsByIdSlides.initiate({
        id: idFromUrl(playlist.id),
      }),
    ).then(({ data }) => {
      const content = (
        <ul>
          {data["hydra:member"].map((playlistSlide) => (
            <li key={playlistSlide?.slide["@id"]}>
              <Link
                to={`slide/edit/${idFromUrl(playlistSlide?.slide["@id"])}`}
                target="_blank"
              >
                {playlistSlide?.slide.title}
              </Link>
            </li>
          ))}
        </ul>
      );

      setModal({
        info: true,
        modalTitle: t("playlist-slide-modal-title"),
        content,
      });
    });
  };

  return (
    <Button variant="secondary" type="button" onClick={onClick} disabled={playlist.slidesLength === 0}>
      {playlist.slidesLength}
    </Button>
  );
}

/**
 * Columns for playlists lists.
 *
 * @param {object} props - The props.
 * @param {Function} props.apiCall - The api to call
 * @param {string} props.infoModalRedirect - The url for redirecting in the info modal.
 * @param {string} props.infoModalTitle - The info modal title.
 * @param {string} props.dataKey The data key for mapping the data.
 * @returns {object} The columns for the playlists lists.
 */
function getPlaylistColumns() {
  const { t } = useTranslation("common", {
    keyPrefix: "playlists-columns",
  });

  const columns = [
    {
      key: "playlist",
      label: t("number-of-slides"),
      content: (playlist) => <SlidesButton playlist={playlist} />,
    },
    {
      key: "publishing-from",
      content: ({ published }) => <DateValue date={published.from} />,
      label: t("publishing-from"),
    },
    {
      key: "publishing-to",
      content: ({ published }) => <DateValue date={published.to} />,
      label: t("publishing-to"),
    },
    {
      key: "status",
      content: ({ published }) => <PublishingStatus published={published} />,
      label: t("status"),
    },
  ];

  return columns;
}

const PlaylistColumns = ColumnHoc(getPlaylistColumns);
const SelectPlaylistColumns = SelectColumnHoc(getPlaylistColumns);

export { SelectPlaylistColumns, PlaylistColumns };
