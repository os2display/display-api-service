function getAllPages(dispatch, endpoint, params, results = [], page = 1) {
  return new Promise((resolve) => {
    dispatch(endpoint.initiate({ ...params, page })).then(({ data }) => {
      const newResults = [...results, ...data["hydra:member"]];
      const hydraView = data["hydra:view"] ?? null;

      if (hydraView !== null && (hydraView["hydra:next"] ?? false)) {
        resolve(getAllPages(dispatch, endpoint, params, newResults, page + 1));
      } else {
        resolve(newResults);
      }
    });
  });
}

export default getAllPages;
