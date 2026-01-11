import { useEditorState } from "@tiptap/react";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import {
  faBold,
  faItalic,
  faLevelDownAlt,
  faListOl,
  faListUl,
  faRedo,
  faRemoveFormat,
  faStrikethrough,
  faUnderline,
  faUndo,
} from "@fortawesome/free-solid-svg-icons";
import { useTranslation } from "react-i18next";
import Dropdown from "react-bootstrap/Dropdown";
import { useState } from "react";

function RichTextMenu({ editor }) {
  const { t } = useTranslation("common", { keyPrefix: "rich-text-editor" });

  const [headingDropdownOpen, setHeadingDropdownOpen] = useState(false);

  const editorState = useEditorState({
    editor,
    selector: (ctx) => {
      return {
        isBold: ctx.editor.isActive("bold") ?? false,
        canBold: ctx.editor.can().chain().toggleBold().run() ?? false,
        isItalic: ctx.editor.isActive("italic") ?? false,
        canItalic: ctx.editor.can().chain().toggleItalic().run() ?? false,
        isStrike: ctx.editor.isActive("strike") ?? false,
        canStrike: ctx.editor.can().chain().toggleStrike().run() ?? false,
        isUnderline: ctx.editor.isActive("underline") ?? false,
        canUnderline: ctx.editor.can().chain().toggleUnderline().run() ?? false,
        isHeading1: ctx.editor.isActive("heading", { level: 1 }) ?? false,
        isHeading2: ctx.editor.isActive("heading", { level: 2 }) ?? false,
        isHeading3: ctx.editor.isActive("heading", { level: 3 }) ?? false,
        isHeading4: ctx.editor.isActive("heading", { level: 4 }) ?? false,
        isNormal:
          !ctx.editor.isActive("heading", { level: 1 }) &&
          !ctx.editor.isActive("heading", { level: 2 }) &&
          !ctx.editor.isActive("heading", { level: 3 }) &&
          !ctx.editor.isActive("heading", { level: 4 }),
        isBulletList: ctx.editor.isActive("bulletList") ?? false,
        isOrderedList: ctx.editor.isActive("orderedList") ?? false,
        isParagraph: ctx.editor.isActive("paragraph") ?? false,
        canUndo: ctx.editor.can().chain().undo().run() ?? false,
        canRedo: ctx.editor.can().chain().redo().run() ?? false,
      };
    },
  });

  const toggleDropdown = () => {
    setHeadingDropdownOpen(!headingDropdownOpen);
  };

  return (
    <div className="control-group">
      <div className="button-group">
        <Dropdown
          style={{ display: "inline-block" }}
          className={!editorState.isNormal ? "is-active" : ""}
          show={headingDropdownOpen}
          onToggle={toggleDropdown}
        >
          <Dropdown.Toggle variant="" id="dropdown-basic">
            <span
              className={
                "toggle-text " + !editorState.isNormal ? "is-active" : ""
              }
            >
              {editorState.isHeading1 && t("toggle-heading-1")}
              {editorState.isHeading2 && t("toggle-heading-2")}
              {editorState.isHeading3 && t("toggle-heading-3")}
              {editorState.isHeading4 && t("toggle-heading-4")}
            </span>
            {editorState.isNormal && t("normal")}
          </Dropdown.Toggle>
          <Dropdown.Menu>
            <button
              type="button"
              onClick={() => {
                editor.chain().focus().toggleHeading({ level: 1 }).run();
                toggleDropdown();
              }}
              className={editorState.isHeading1 ? "is-active" : ""}
              aria-label={t("toggle-heading-1")}
              title={t("toggle-heading-1")}
            >
              {t("toggle-heading-1")}
            </button>
            <button
              type="button"
              onClick={() => {
                editor.chain().focus().toggleHeading({ level: 2 }).run();
                toggleDropdown();
              }}
              className={editorState.isHeading2 ? "is-active" : ""}
              aria-label={t("toggle-heading-2")}
              title={t("toggle-heading-2")}
            >
              {t("toggle-heading-2")}
            </button>
            <button
              type="button"
              onClick={() => {
                editor.chain().focus().toggleHeading({ level: 3 }).run();
                toggleDropdown();
              }}
              className={editorState.isHeading3 ? "is-active" : ""}
              aria-label={t("toggle-heading-3")}
              title={t("toggle-heading-3")}
            >
              {t("toggle-heading-3")}
            </button>
            <button
              type="button"
              onClick={() => {
                editor.chain().focus().toggleHeading({ level: 4 }).run();
                toggleDropdown();
              }}
              className={editorState.isHeading4 ? "is-active" : ""}
              aria-label={t("toggle-heading-4")}
              title={t("toggle-heading-4")}
            >
              {t("toggle-heading-4")}
            </button>
          </Dropdown.Menu>
        </Dropdown>
        <button
          type="button"
          onClick={() => editor.chain().focus().toggleBold().run()}
          disabled={!editorState.canBold}
          className={editorState.isBold ? "is-active" : ""}
          aria-label={t("toggle-bold")}
          title={t("toggle-bold")}
        >
          <FontAwesomeIcon icon={faBold} />
        </button>
        <button
          type="button"
          onClick={() => editor.chain().focus().toggleItalic().run()}
          disabled={!editorState.canItalic}
          className={editorState.isItalic ? "is-active" : ""}
          aria-label={t("toggle-italic")}
          title={t("toggle-italic")}
        >
          <FontAwesomeIcon icon={faItalic} />
        </button>
        <button
          type="button"
          onClick={() => editor.chain().focus().toggleUnderline().run()}
          disabled={!editorState.canUnderline}
          className={editorState.isUnderline ? "is-active" : ""}
          aria-label={t("toggle-underline")}
          title={t("toggle-underline")}
        >
          <FontAwesomeIcon icon={faUnderline} />
        </button>
        <button
          type="button"
          onClick={() => editor.chain().focus().toggleStrike().run()}
          disabled={!editorState.canStrike}
          className={editorState.isStrike ? "is-active" : ""}
          aria-label={t("toggle-strike-through")}
          title={t("toggle-strike-through")}
        >
          <FontAwesomeIcon icon={faStrikethrough} />
        </button>
        <button
          type="button"
          onClick={() => editor.chain().focus().toggleBulletList().run()}
          className={editorState.isBulletList ? "is-active" : ""}
          aria-label={t("toggle-bullet-list")}
          title={t("toggle-bullet-list")}
        >
          <FontAwesomeIcon icon={faListUl} />
        </button>
        <button
          type="button"
          onClick={() => editor.chain().focus().toggleOrderedList().run()}
          className={editorState.isOrderedList ? "is-active" : ""}
          aria-label={t("toggle-ordered-list")}
          title={t("toggle-ordered-list")}
        >
          <FontAwesomeIcon icon={faListOl} />
        </button>
        <button
          type="button"
          onClick={() => editor.chain().focus().setHardBreak().run()}
          aria-label={t("insert-hard-break")}
          title={t("insert-hard-break")}
        >
          <FontAwesomeIcon icon={faLevelDownAlt} />
        </button>

        <button
          type="button"
          onClick={() => editor.chain().focus().undo().run()}
          disabled={!editorState.canUndo}
          className="ms-3"
          aria-label={t("undo")}
          title={t("undo")}
        >
          <FontAwesomeIcon icon={faUndo} />
        </button>
        <button
          type="button"
          onClick={() => editor.chain().focus().redo().run()}
          disabled={!editorState.canRedo}
          aria-label={t("redo")}
          title={t("redo")}
        >
          <FontAwesomeIcon icon={faRedo} />
        </button>

        <button
          type="button"
          className="ms-3"
          onClick={() => {
            editor.commands.unsetAllMarks();
            editor.commands.clearNodes();
          }}
          aria-label={t("reset-formatting")}
          title={t("reset-formatting")}
        >
          <FontAwesomeIcon icon={faRemoveFormat} />
        </button>
      </div>
    </div>
  );
}

export default RichTextMenu;
