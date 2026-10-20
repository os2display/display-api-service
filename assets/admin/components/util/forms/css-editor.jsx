import { useEffect, useRef, useState } from "react";
import { FormLabel } from "react-bootstrap";
import { useTranslation } from "react-i18next";
import {
  lintThemeCss,
  themeCssTokens,
} from "../../../../shared/theme/theme-css-lint";
import "./css-editor.scss";

const LINT_DELAY = 150;

// The Custom Highlight API colours the mirror behind the textarea. Without it
// the field is a plain textarea and only the problem list is shown.
const canHighlight = () =>
  typeof CSS !== "undefined" && "highlights" in CSS && "Highlight" in window;

const clearHighlights = () =>
  CSS.highlights.forEach((h, key) => {
    if (key.startsWith("css-editor-")) CSS.highlights.delete(key);
  });

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
 * @returns {object} A css text area.
 */
function CssEditor({
  name,
  label,
  onChange,
  helpText = "",
  value = "",
  placeholder = "",
  required = false,
}) {
  const { t } = useTranslation("common", { keyPrefix: "css-editor" });
  const [highlight] = useState(canHighlight);
  const [diagnostics, setDiagnostics] = useState([]);
  const textareaRef = useRef(null);
  const mirrorRef = useRef(null);
  const css = value ?? "";

  useEffect(() => {
    const timeout = setTimeout(() => {
      const found = lintThemeCss(css);
      setDiagnostics(found);

      const text = mirrorRef.current?.firstChild?.firstChild;
      if (!highlight || !text) return;

      const range = ({ from, to }) => {
        const r = new Range();
        r.setStart(text, Math.min(from, css.length));
        r.setEnd(text, Math.min(to, css.length));
        return r;
      };
      const groups = {};
      themeCssTokens(css).forEach((token) => {
        (groups[`css-editor-${token.type}`] ??= []).push(range(token));
      });
      groups["css-editor-error"] = found
        .filter((d) => d.severity === "error")
        .map(range);
      groups["css-editor-warning"] = found
        .filter((d) => d.severity === "warning")
        .map(range);

      clearHighlights();
      Object.entries(groups).forEach(([key, ranges]) => {
        if (ranges.length > 0)
          CSS.highlights.set(key, new Highlight(...ranges));
      });
    }, LINT_DELAY);

    return () => clearTimeout(timeout);
  }, [css, highlight]);

  useEffect(() => (highlight ? clearHighlights : undefined), [highlight]);

  const syncScroll = () => {
    if (!mirrorRef.current) return;
    mirrorRef.current.scrollTop = textareaRef.current.scrollTop;
    mirrorRef.current.scrollLeft = textareaRef.current.scrollLeft;
  };

  const select = ({ from, to }) => {
    textareaRef.current.focus();
    textareaRef.current.setSelectionRange(from, to);
  };

  const hasErrors = diagnostics.some((d) => d.severity === "error");

  return (
    <div className="form-group css-editor">
      <FormLabel htmlFor={name}>
        {label}
        {required && " *"}
      </FormLabel>
      {helpText && (
        <p id={`${name}-help`} className="form-text mt-0">
          {helpText}
        </p>
      )}
      <div className="css-editor__stack">
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
      <ul
        id={`${name}-diagnostics`}
        className="css-editor__diagnostics"
        aria-live="polite"
      >
        {diagnostics.map((d) => (
          <li
            key={`${d.code}-${d.from}`}
            className={`css-editor__diagnostic css-editor__diagnostic--${d.severity}`}
          >
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
