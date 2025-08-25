import { React } from "react";

/**
 * @param {object} props - The props.
 * @param {Array} props.children The children being passed from parent
 * @param {string} props.id Id of the section
 * @param {boolean} props.highlightSection Highlight this section.
 * @returns {object} The Content header.
 */
function ContentBody({ children, id = "", highlightSection = false }) {
  const classes = `shadow-sm p-3 mb-3 bg-body rounded ${
    highlightSection ? "border border-warning" : ""
  }`;

  return (
    <section id={id} className={classes}>
      {children}
    </section>
  );
}

export default ContentBody;
