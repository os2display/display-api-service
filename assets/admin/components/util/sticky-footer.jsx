import { React, JSX } from "react";

/**
 * @param {object} props The props.
 * @param {Array} props.children The children being passed from parent
 * @returns {JSX.Element} The Content header.
 */
function StickyFooter({ children }) {
  return (
    <section className="sticky-footer border-top shadow-sm navbar navbar-expand-lg navbar-light bg-white position-sticky bottom-0 p-3">
      {children}
    </section>
  );
}

export default StickyFooter;
