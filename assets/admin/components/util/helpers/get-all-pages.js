const MAX_PAGES = 100;

async function getAllPages(dispatch, endpoint, params) {
  const results = [];
  let page = 1;

  while (page <= MAX_PAGES) {
    const { data } = await dispatch(endpoint.initiate({ ...params, page }));
    const members = data["hydra:member"];

    if (members.length === 0) {
      break;
    }

    results.push(...members);

    const hydraView = data["hydra:view"] ?? null;
    if (hydraView === null || !(hydraView["hydra:next"] ?? false)) {
      break;
    }
    page += 1;
  }

  return results;
}

export default getAllPages;
