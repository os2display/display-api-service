import idFromUrl from "./id-from-url";

function mapToIds(array) {
  return array.map((item) => idFromUrl(item["@id"]));
}

export default mapToIds;
