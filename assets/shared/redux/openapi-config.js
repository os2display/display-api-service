const config = {
  schemaFile: "../../../public/api-spec-v2.json",
  apiFile: "./empty-api.ts",
  apiImport: "emptySplitApi",
  outputFile: "./generated-api.ts",
  exportName: "api",
  hooks: true,
  tag: true,
  endpointOverrides: [
    {
      pattern: /.*/,
      parameterFilter: (_name, parameter) => {
        // Filter out parameters from OpenAPI specification that results in
        // invalid javascript with duplicate query parameters.
        return !(
          ["createdBy", "modifiedBy", "supportedFeedOutputType"].includes(
            _name,
          ) && parameter.style === "deepObject"
        );
      },
    },
  ],
};

export default config;
