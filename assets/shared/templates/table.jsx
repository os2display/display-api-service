import { useEffect } from "react";
import styled from "styled-components";
import BaseSlideExecution from "../slide-utils/base-slide-execution.js";
import {
  getFirstMediaUrlFromField,
  ThemeStyles,
} from "../slide-utils/slide-util.jsx";
import GlobalStyles from "../slide-utils/GlobalStyles.js";
import "./table/table.scss";
import templateConfig from "./table.json";

function id() {
  return templateConfig.id;
}

function config() {
  return templateConfig;
}

function renderSlide(slide, run, slideDone) {
  return (
    <Table
      slide={slide}
      run={run}
      slideDone={slideDone}
      content={slide.content}
      executionId={slide.executionId}
    />
  );
}

/**
 * Table component.
 *
 * @param {object} props Props.
 * @param {object} props.slide The slide.
 * @param {object} props.content The slide content.
 * @param {boolean} props.run Whether or not the slide should start running.
 * @param {Function} props.slideDone Function to invoke when the slide is done playing.
 * @param {string} props.executionId Unique id for the instance.
 * @returns {JSX.Element} The component.
 */
function Table({ slide, content, run, slideDone, executionId }) {
  // Content
  const {
    table,
    title,
    text,
    fontSize,
    fontPlacement,
    separator = true,
    duration = 15000,
    mediaContain,
  } = content;
  let header;

  if (Array.isArray(table) && table.length > 0 && table[0].type === "header") {
    [header] = table;
  }

  // Image
  const rootStyle = {};
  const backgroundImageUrl = getFirstMediaUrlFromField(
    slide.mediaData,
    content.image,
  );
  if (backgroundImageUrl) {
    rootStyle.backgroundImage = `url("${backgroundImageUrl}")`;
  }

  /** Setup slide run function. */
  useEffect(() => {
    const slideExecution = new BaseSlideExecution(slide, slideDone);
    if (run) {
      slideExecution.start(duration);
    }

    return function cleanup() {
      slideExecution.stop();
    };
  }, [run]);

  let gridStyle;
  if (header) {
    gridStyle = {
      gridTemplateColumns: `${"auto ".repeat(header.columns.length)}`,
      display: "grid",
    };
  }

  return (
    <>
      <Wrapper
        className={`template-table ${fontSize} ${
          mediaContain ? "media-contain" : ""
        }`}
        style={rootStyle}
      >
        <Header className="template-table-header">
          <Title className="title">
            {title}
            {separator && <HeaderUnderline className="separator" />}
          </Title>
        </Header>
        <ContentWrapper>
          {fontPlacement === "top" && (
            <Description className="top-text">{text}</Description>
          )}
          {header && (
            <GridTable style={gridStyle}>
              {header.columns.map((headerObject) => (
                <TableHeader
                  key={headerObject.Header}
                  className="column-header"
                >
                  {headerObject.Header}
                </TableHeader>
              ))}

              {Array.isArray(table) &&
                table.map((column) =>
                  header.columns.map(
                    ({ accessor }) =>
                      column[accessor] && (
                        <Column key={column[accessor]} className="column">
                          {column[accessor]}
                        </Column>
                      ),
                  ),
                )}
            </GridTable>
          )}
          {fontPlacement === "bottom" && (
            <Description className="bottom-text">{text}</Description>
          )}
        </ContentWrapper>
      </Wrapper>

      <ThemeStyles id={executionId} css={slide?.theme?.cssStyles} />
      <GlobalStyles />
    </>
  );
}

const Wrapper = styled.div`
  /* Wrapper styling */
  font-family: var(--font-family-base);
  font-size: var(--font-size-base);
  height: 100%;
  background-repeat: no-repeat;
  background-size: cover;
  background-color: var(--background-color);
  color: var(--text-color);
  overflow: hidden;

  /* Position background from inline style */
  background-size: cover;
  background-position: center;
`;

const Header = styled.header`
  /* Header styling */
  background-color: var(--background-color-secondary);
  padding: var(--padding-size-base);
`;

const Title = styled.h1`
  /* H1 title styling */
  font-size: var(--h1-font-size);
  position: relative;
  display: inline-block;
  margin-bottom: var(--margin-size-base);
`;

const TableHeader = styled.h2`
  /* H2 tableheader styling */
  font-size: var(--h2-font-size);
  color: var(--color-primary);
`;

const HeaderUnderline = styled.div`
  /* HeaderUnderline styling */
  /*
  * TODO: Consider moving HeaderUnderline to at seperate reusable component. Maybe in combination with title.
  */
  opacity: 0;
  position: absolute;
  height: 0.2em;
  width: 100%;
  transition: width 0.3s ease-out;
  animation: 0.7s normal 0.5s forwards 1 h1-underline ease-out;
  background-color: var(--color-primary);
`;

const ContentWrapper = styled.main`
  /* Content wrapper styling */
  padding: var(--padding-size-base);
`;

const GridTable = styled.div`
  /* Grid styling */
  margin: var(--margin-size-base) 0;

  &:nth-child(even) {
    background-color: var(--background-color-secondary);
  }

  &.s {
    font-size: var(--font-size-sm);
  }
  &.m {
    font-size: var(--font-size-base);
  }
  &.l {
    font-size: var(--font-size-lg);
  }
  &.xl {
    font-size: var(--font-size-xl);
  }
`;

const Column = styled.div`
  /* Column styling */
  padding: calc(var(--padding-size-base) * 0.5) 0;
`;

const Description = styled.div`
  /* Description text styling */
  margin: var(--margin-size-base) 0;
`;

export default { id, config, renderSlide };
