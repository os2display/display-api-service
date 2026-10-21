import { useEffect, useLayoutEffect, useRef, useState } from "react";
import { FormLabel } from "react-bootstrap";
import { useTranslation } from "react-i18next";
import {
  lintThemeCss,
  themeCssTokens,
} from "../../../../shared/theme/theme-css-lint";
import "./css-editor.scss";

const LINT_DELAY = 150;
// Long enough that a screen reader hears the count once typing pauses, not
// every intermediate problem.
const ANNOUNCE_DELAY = 1000;

// The Custom Highlight API colours the mirror behind the textarea. Without it
// the field is a plain textarea and only the problem list is shown.
const canHighlight = () =>
  typeof CSS !== "undefined" && "highlights" in CSS && "Highlight" in window;

// The ::highlight() names are fixed in the stylesheet, so every editor on the
// page shares one Highlight per name and adds and removes only its own ranges.
const sharedHighlight = (key) => {
  if (!CSS.highlights.has(key)) CSS.highlights.set(key, new Highlight());
  return CSS.highlights.get(key);
};

/**
 * A css text area that highlights syntax and lists problems while typing.
 *
 * Problems never block saving: the API decides what is accepted.
 *
 * @param {object} props The props.
 * @param {string} props.name The name of the text area
 * @param {string} props.label The label for the text area
 * @param {string} props.placeholder The placeholder for the text area
 * @param {string} props.value The value of the text area
 * @param {Function} props.onChange The callback for changes in the text area
 * @param {string} props.helpText Help text shown below the label
 * @param {boolean} props.hideLabel Hide the label visually, when a heading
 *   above already names the field. Screen readers still announce it.
 * @returns {object} A css text area.
 */
function CssEditor({
  name,
  label,
  onChange,
  helpText = "",
  hideLabel = false,
  value = "",
  placeholder = "",
  required = false,
}) {
  const { t } = useTranslation("common", { keyPrefix: "css-editor" });
  const [highlight] = useState(canHighlight);
  const [diagnostics, setDiagnostics] = useState([]);
  const [announcement, setAnnouncement] = useState("");
  const textareaRef = useRef(null);
  const mirrorRef = useRef(null);
  const gutterRef = useRef(null);
  const css = value ?? "";

  // Only the lint waits for typing to pause; the colours below do not.
  useEffect(() => {
    const timeout = setTimeout(
      () => setDiagnostics(lintThemeCss(css)),
      LINT_DELAY,
    );
    return () => clearTimeout(timeout);
  }, [css]);

  useEffect(() => {
    const timeout = setTimeout(
      () =>
        setAnnouncement(
          diagnostics.length > 0
            ? t("problem-count", { count: diagnostics.length })
            : "",
        ),
      ANNOUNCE_DELAY,
    );
    return () => clearTimeout(timeout);
  }, [diagnostics, t]);

  // React replaces the mirror's text node on every change, which collapses
  // the old ranges, so they are rebuilt before the browser paints. Problem
  // underlines use the last lint until the next one is done.
  useLayoutEffect(() => {
    const text = mirrorRef.current?.firstChild?.firstChild;
    if (!highlight || !text) return undefined;

    const range = ({ from, to }) => {
      const r = new Range();
      r.setStart(text, Math.min(from, css.length));
      r.setEnd(text, Math.min(to, css.length));
      return r;
    };
    // This editor's ranges, as [highlight name, range].
    const own = [
      ...themeCssTokens(css).map((token) => [
        `css-editor-${token.type}`,
        range(token),
      ]),
      ...diagnostics.map((d) => [`css-editor-${d.severity}`, range(d)]),
    ];
    own.forEach(([key, r]) => sharedHighlight(key).add(r));

    return () => own.forEach(([key, r]) => CSS.highlights.get(key)?.delete(r));
  }, [css, diagnostics, highlight]);

  const syncScroll = () => {
    gutterRef.current.scrollTop = textareaRef.current.scrollTop;
    if (!mirrorRef.current) return;
    mirrorRef.current.scrollTop = textareaRef.current.scrollTop;
    mirrorRef.current.scrollLeft = textareaRef.current.scrollLeft;
  };

  // Lines never wrap, so each line of css is one row in the gutter.
  const lineCount = css.split("\n").length;
  const lineSeverity = {};
  diagnostics.forEach((d) => {
    if (lineSeverity[d.line] !== "error") lineSeverity[d.line] = d.severity;
  });

  // Select the problem and scroll its line a third down the field, clear of
  // the problem panel that may cover the bottom.
  const select = ({ from, to, line }) => {
    const textarea = textareaRef.current;
    textarea.focus({ preventScroll: true });
    textarea.setSelectionRange(from, to);
    const lineHeight = parseFloat(getComputedStyle(textarea).lineHeight);
    textarea.scrollTop = Math.max(
      0,
      (line - 1) * lineHeight - textarea.clientHeight / 3,
    );
    syncScroll();
    textarea.scrollIntoView({ block: "nearest" });
  };

  const hasErrors = diagnostics.some((d) => d.severity === "error");

  return (
    <div
      className={`form-group css-editor${
        diagnostics.length > 0 ? " css-editor--has-problems" : ""
      }`}
    >
      <FormLabel
        htmlFor={name}
        className={hideLabel ? "visually-hidden" : undefined}
      >
        {label}
        {required && " *"}
      </FormLabel>
      {helpText && (
        <p id={`${name}-help`} className="form-text mt-0">
          {helpText}
        </p>
      )}
      <div className="css-editor__stack">
        <div className="css-editor__gutter" aria-hidden="true" ref={gutterRef}>
          {Array.from({ length: lineCount }, (_, i) => (
            <span
              key={i}
              className={
                lineSeverity[i + 1]
                  ? `css-editor__line--${lineSeverity[i + 1]}`
                  : undefined
              }
            >
              {i + 1}
            </span>
          ))}
        </div>
        {highlight && (
          <pre
            className="form-control css-editor__mirror"
            aria-hidden="true"
            ref={mirrorRef}
          >
            {/* The trailing newline keeps the height of a last empty line. */}
            <code>{`${css}\n`}</code>
          </pre>
        )}
        <textarea
          name={name}
          id={name}
          ref={textareaRef}
          className={`form-control css-editor__input${
            highlight ? " css-editor__input--highlighted" : ""
          }`}
          rows={15}
          placeholder={placeholder}
          value={css}
          onChange={onChange}
          onScroll={syncScroll}
          spellCheck={false}
          aria-invalid={hasErrors}
          aria-describedby={`${helpText ? `${name}-help ` : ""}${name}-diagnostics`}
        />
      </div>
      <p role="status" className="visually-hidden">
        {announcement}
      </p>
      <ul id={`${name}-diagnostics`} className="css-editor__diagnostics">
        {diagnostics.map((d) => (
          <li
            key={`${d.code}-${d.from}`}
            className={`css-editor__diagnostic css-editor__diagnostic--${d.severity}`}
          >
            <span
              className={`badge ${
                d.severity === "error" ? "text-bg-danger" : "text-bg-warning"
              }`}
            >
              {t(d.severity)}
            </span>{" "}
            <button
              type="button"
              className="btn btn-link p-0 align-baseline"
              onClick={() => select(d)}
            >
              {t("line", { line: d.line })}
            </button>
            : {t(d.code, d.params)}
          </li>
        ))}
      </ul>
    </div>
  );
}

export default CssEditor;
