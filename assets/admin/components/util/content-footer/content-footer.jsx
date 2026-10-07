/**
 * @param {object} props The props.
 * @param {Array} props.children The children being passed from parent
 * @returns {object} The Content header.
 */
function ContentFooter({ children }) {
  return (
    <section className="content-footer d-grid gap-2 d-lg-block align-items-end mb-5">
      {children}
    </section>
  );
}

export default ContentFooter;
