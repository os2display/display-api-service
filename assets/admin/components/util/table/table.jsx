import React from "react";
import TableHeader from "./table-header";
import TableBody from "./table-body";
import PaginationButton from "../forms/multiselect-dropdown/pagination-button";

/**
 * @param {object} props The props.
 * @param {Array} props.columns The columns for the table.
 * @param {Array} props.data The data to display in the table.
 * @param {Function} props.callback - The callback.
 * @param {string | null} props.label - The label.
 * @param {number | null} props.totalItems - Total data items.
 * @param {boolean} props.isFetching - Fetching items.
 * @returns {object} The table.
 */
function Table({
  columns,
  data,
  label = null,
  callback = null,
  totalItems = null,
  isFetching = false,
}) {
  const showButton = Number.isInteger(totalItems) && totalItems > data.length;

  return (
    <div className="table-responsive">
      <table className="table table-hover">
        <TableHeader columns={columns} />
        {!isFetching && <TableBody columns={columns} data={data} />}
      </table>
      <PaginationButton
        showButton={showButton}
        label={label}
        callback={callback}
      />
    </div>
  );
}

export default Table;
