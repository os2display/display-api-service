import TableHeader from "./table-header";
import TableBody from "./table-body";

/**
 * @param {object} props The props.
 * @param {Array} props.columns The columns for the table.
 * @param {Array} props.data The data to display in the table.
 * @param {boolean} props.isFetching - Fetching items.
 * @returns {object} The table.
 */
function Table({ columns, data, isFetching = false }) {
  return (
    <div className="table-responsive">
      <table className="table table-hover">
        <TableHeader columns={columns} />
        {!isFetching && <TableBody columns={columns} data={data} />}
      </table>
    </div>
  );
}

export default Table;
