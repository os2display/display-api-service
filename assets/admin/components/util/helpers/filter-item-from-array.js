function filterItemFromArray(array, toRemove) {
    return array.filter(
      (item) => item["@id"] !== toRemove
    );
}

export default filterItemFromArray;
