import { useState, useEffect } from "react";
import { useDispatch } from "react-redux";

function useFetchDataHook(apiCall, ids, params = {}, key = "id") {
  const [data, setData] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const dispatch = useDispatch();

  useEffect(() => {
    if (!ids || ids.length === 0) return;

    // Filter out null/undefined/empty IDs
    const validIds = ids.filter((id) => id != null && id !== "");
    if (validIds.length === 0) return;

    // Check if params contain invalid values
    const hasInvalidParams = Object.values(params).some(
      (value) => value === "" || value == null,
    );
    if (hasInvalidParams) return;

    async function fetchItems() {
      setLoading(true);

      try {
        let allItems = [];
        let fetchedItems = [];

        for (const id of ids) {
          let page = 1;
          let totalItems = 1; // Will be overridden when we know the total amount.

          while (fetchedItems.length < totalItems) {
            params[key] = id;
            const {
              data: {
                "hydra:member": items = [],
                "hydra:totalItems": hydraTotalItems = 0,
              },
              originalArgs,
            } = await dispatch(
              apiCall({
                ...params,
                page,
                // The max items per page is 30: https://github.com/os2display/display-api-service/blob/develop/config/packages/api_platform.yaml#L11
                itemsPerPage: 30,
              }),
            );

            // We don't like those darn infinite loops.
            if (items.length === 0) {
              break;
            }

            // Sometimes we use the arguments from the api call
            const itemsWithOriginalArgs = items.map((item) => ({
              ...item,
              originalArgs,
            }));

            totalItems = hydraTotalItems;
            fetchedItems = fetchedItems.concat(itemsWithOriginalArgs);
            page++;
          }
          allItems = allItems.concat(fetchedItems);
          fetchedItems = [];
        }
        setData(allItems);
      } catch (err) {
        setError(err.message);
      } finally {
        setLoading(false);
      }
    }

    fetchItems();
  }, [apiCall]); // Should params beadded  here to rerun on change?

  return { data, loading, error };
}

export default useFetchDataHook;
