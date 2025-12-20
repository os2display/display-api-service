import { FormGroup, FormLabel } from "react-bootstrap";
import DOMPurify from "dompurify";
//import "./rich-text.scss";
import { EditorContent, useEditor, useEditorState } from "@tiptap/react";
import StarterKit from "@tiptap/starter-kit";
import { FloatingMenu } from "@tiptap/react/menus";

const extensions = [StarterKit]

function MenuBar({ editor }) {
  const editorState = useEditorState({
    editor,
    selector: ctx => {
      return {
        isBold: ctx.editor.isActive('bold') ?? false,
        canBold: ctx.editor.can().chain().toggleBold().run() ?? false,
        isItalic: ctx.editor.isActive('italic') ?? false,
        canItalic: ctx.editor.can().chain().toggleItalic().run() ?? false,
        isStrike: ctx.editor.isActive('strike') ?? false,
        canStrike: ctx.editor.can().chain().toggleStrike().run() ?? false,
        isParagraph: ctx.editor.isActive('paragraph') ?? false,
        isHeading1: ctx.editor.isActive('heading', { level: 1 }) ?? false,
        isHeading2: ctx.editor.isActive('heading', { level: 2 }) ?? false,
        isHeading3: ctx.editor.isActive('heading', { level: 3 }) ?? false,
        isHeading4: ctx.editor.isActive('heading', { level: 4 }) ?? false,
        isHeading5: ctx.editor.isActive('heading', { level: 5 }) ?? false,
        isHeading6: ctx.editor.isActive('heading', { level: 6 }) ?? false,
        isBulletList: ctx.editor.isActive('bulletList') ?? false,
        isOrderedList: ctx.editor.isActive('orderedList') ?? false,
        isBlockquote: ctx.editor.isActive('blockquote') ?? false,
        canUndo: ctx.editor.can().chain().undo().run() ?? false,
        canRedo: ctx.editor.can().chain().redo().run() ?? false,
      }
    },
  })

  return (
    <div className="control-group">
      <div className="button-group">
        <button
          type="button"
          onClick={() => editor.chain().focus().toggleBold().run()}
          disabled={!editorState.canBold}
          className={editorState.isBold ? 'is-active' : ''}
        >
          Bold
        </button>
        <button
          type="button"
          onClick={() => editor.chain().focus().toggleItalic().run()}
          disabled={!editorState.canItalic}
          className={editorState.isItalic ? 'is-active' : ''}
        >
          Italic
        </button>
        <button
          type="button"
          onClick={() => editor.chain().focus().toggleStrike().run()}
          disabled={!editorState.canStrike}
          className={editorState.isStrike ? 'is-active' : ''}
        >
          Strike
        </button>
        <button
          type="button"
          onClick={() => editor.chain().focus().setParagraph().run()}
          className={editorState.isParagraph ? 'is-active' : ''}
        >
          Paragraph
        </button>
        <button
          type="button"
          onClick={() => editor.chain().focus().toggleHeading({ level: 1 }).run()}
          className={editorState.isHeading1 ? 'is-active' : ''}
        >
          H1
        </button>
        <button
          type="button"
          onClick={() => editor.chain().focus().toggleHeading({ level: 2 }).run()}
          className={editorState.isHeading2 ? 'is-active' : ''}
        >
          H2
        </button>
        <button
          type="button"
          onClick={() => editor.chain().focus().toggleHeading({ level: 3 }).run()}
          className={editorState.isHeading3 ? 'is-active' : ''}
        >
          H3
        </button>
        <button
          type="button"
          onClick={() => editor.chain().focus().toggleHeading({ level: 4 }).run()}
          className={editorState.isHeading4 ? 'is-active' : ''}
        >
          H4
        </button>
        <button
          type="button"
          onClick={() => editor.chain().focus().toggleHeading({ level: 5 }).run()}
          className={editorState.isHeading5 ? 'is-active' : ''}
        >
          H5
        </button>
        <button
          type="button"
          onClick={() => editor.chain().focus().toggleHeading({ level: 6 }).run()}
          className={editorState.isHeading6 ? 'is-active' : ''}
        >
          H6
        </button>
        <button
          type="button"
          onClick={() => editor.chain().focus().toggleBulletList().run()}
          className={editorState.isBulletList ? 'is-active' : ''}
        >
          Bullet list
        </button>
        <button
          type="button"
          onClick={() => editor.chain().focus().toggleOrderedList().run()}
          className={editorState.isOrderedList ? 'is-active' : ''}
        >
          Ordered list
        </button>
        <button
          type="button"
          onClick={() => editor.chain().focus().toggleBlockquote().run()}
          className={editorState.isBlockquote ? 'is-active' : ''}
        >
          Blockquote
        </button>
        <button
          type="button"
          onClick={() => editor.chain().focus().setHorizontalRule().run()}
        >
          Horizontal rule
        </button>
        <button
          type="button"
          onClick={() => editor.chain().focus().setHardBreak().run()}
        >
          Hard break
        </button>
        <button
          type="button"
          onClick={() => editor.chain().focus().undo().run()} disabled={!editorState.canUndo}
        >
          Undo
        </button>
        <button
          type="button"
          onClick={() => editor.chain().focus().redo().run()} disabled={!editorState.canRedo}
        >
          Redo
        </button>
      </div>
    </div>
  )
}

/**
 * A rich text field for forms.
 *
 * @param {string} props The props.
 * @param {string} props.name The name of the rich text field
 * @param {string} props.label The label for the rich text field
 * @param {string} props.helpText The help text for the rich text field, if it is needed.
 * @param {string} props.value The value of the rich text field
 * @param {Function} props.onChange The callback for changes in the rich text field
 * @param {string} props.formGroupClasses Classes for the form group
 * @param {boolean} props.required Whether the rich text field is required.
 * @returns {object} A rich text field.
 */
function RichText({
  name,
  onChange,
  label = "",
  helpText = "",
  value = "",
  formGroupClasses = "",
  required = false,
}) {
  /**
   * Transforms the target to something the form-components understand.
   *
   * @param {string} richText The rich text returned from reactquill
   */
  const onRichTextChange = (richText) => {
    let sanitizedHtml = DOMPurify.sanitize(richText);
    const returnTarget = { value: sanitizedHtml, id: name };
    onChange({ target: returnTarget });
  };

  const editor = useEditor({
    extensions: extensions,
    content: value,
    onUpdate({editor}) {
      onRichTextChange(editor.getHTML())
    },
  })

  return (
    <div className="text-editor">
      <FormGroup className={formGroupClasses}>
        <FormLabel htmlFor={name}>
          {label}
          {required && " *"}
        </FormLabel>

        <div style={{border: "1px solid #ccc", padding: "10px"}}>
          <MenuBar editor={editor} />
          <EditorContent editor={editor} />
        </div>
      </FormGroup>
      {helpText && <small>{helpText}</small>}
    </div>
  );
}

export default RichText;
