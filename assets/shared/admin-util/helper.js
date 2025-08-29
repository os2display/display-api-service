const getInputFiles = (field, mediaData) => {
  const inputFiles = [];
  if (Array.isArray(field)) {
    field.forEach((mediaId) => {
      if (Object.prototype.hasOwnProperty.call(mediaData, mediaId)) {
        inputFiles.push(mediaData[mediaId]);
      }
    });
  }

  return inputFiles;
};

export default getInputFiles;
