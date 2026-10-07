import {
  createGridArea,
  createGrid,
} from "../../../../shared/grid-generator/grid-generator";

// Rows and columns in grid defaults to 1.
const Grid = ({
  vertical,
  regions,
  selected,
  grid: { columns = 1, rows = 1 },
}) => {
  const gridClasses = `grid ${vertical ? "vertical" : "horizontal"}`;
  const gridTemplateAreas = {
    gridTemplateAreas: createGrid(columns, rows),
  };

  return (
    <div className={gridClasses} style={gridTemplateAreas}>
      {regions.map((region) => (
        <div
          key={region["@id"]}
          className={
            selected === region["@id"] ? "grid-item selected" : "grid-item "
          }
          style={{ gridArea: createGridArea(region.gridArea) }}
        >
          {region.title}
        </div>
      ))}
    </div>
  );
};

export default Grid;
