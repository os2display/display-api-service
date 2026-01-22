import { useTranslation } from "react-i18next";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { faAngleLeft, faAngleRight } from "@fortawesome/free-solid-svg-icons";

/**
 * @param {object} props The props.
 * @param {number} props.itemsCount The amount of data to be spread out in pages.
 * @param {number} props.pageSize The page size
 * @param {Function} props.onPageChange The callback for page change.
 * @param {number} props.currentPage The current page.
 * @returns {object} The pagination.
 */
function Pagination({ itemsCount, pageSize, onPageChange, currentPage }) {
  const { t } = useTranslation("common", { keyPrefix: "pagination" });
  const pageCount = Math.ceil(itemsCount / pageSize);
  // No need for pagination
  if (pageCount <= 1) return null;

  const nextPage = () => {
    onPageChange(currentPage + 1);
  };

  const prevPage = () => {
    onPageChange(Math.max(1, currentPage - 1));
  };

  return (<div className="d-flex justify-content-center">
      {currentPage > 1 && <a type="button" className="me-3" onClick={() => prevPage()}>{t('prev')}</a>}
      <span className="me-3">{t('page', {currentPage: currentPage})}</span>
      {currentPage * pageSize < itemsCount && <a type="button" className="me-3" onClick={() => nextPage()}>{t('next')}</a>}
    </div>
  );
}

export default Pagination;
