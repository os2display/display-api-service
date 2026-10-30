import Spinner from "react-bootstrap/Spinner";
import { useGetV2TemplatesByIdQuery } from "../../../shared/redux/enhanced-api.ts";
import idFromUrl from "./helpers/id-from-url";
/**
 * @param {object} props The props.
 * @param {object} props.templateInfo Object containing template id.
 * @returns {object} The template title.
 */
function TemplateLabelInList({ templateInfo }) {
  // template id created below.
  const id = idFromUrl(templateInfo["@id"]);

  const { data } = useGetV2TemplatesByIdQuery({
    id,
  });

  return (
    <>
      {data && <div>{data.title}</div>}
      {!data && (
        <Spinner
          as="span"
          animation="border"
          size="sm"
          role="status"
          aria-hidden="true"
          className="m-1"
        />
      )}
    </>
  );
}

export default TemplateLabelInList;
