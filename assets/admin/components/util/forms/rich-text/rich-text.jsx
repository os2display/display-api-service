import { FormGroup, FormLabel } from "react-bootstrap";
import DOMPurify from "dompurify";
import { EditorContent, useEditor } from "@tiptap/react";
import RichTextMenu from "./rich-text-menu.jsx";
import "./rich-text.scss";
import { BulletList, ListItem, OrderedList } from "@tiptap/extension-list";
import Bold from "@tiptap/extension-bold";
import Italic from "@tiptap/extension-italic";
import Strike from "@tiptap/extension-strike";
import Underline from "@tiptap/extension-underline";
import Document from "@tiptap/extension-document";
import HardBreak from "@tiptap/extension-hard-break";
import Paragraph from "@tiptap/extension-paragraph";
import Text from "@tiptap/extension-text";
import { UndoRedo } from "@tiptap/extensions";
import Heading from "@tiptap/extension-heading";

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
    // @see https://tiptap.dev/docs/editor/extensions/overview
    extensions: [
      Document,
      Text,
      Bold,
      Italic,
      Strike,
      Underline,
      Heading.configure({
        levels: [1, 2, 3, 4],
      }),
      Underline,
      BulletList,
      OrderedList,
      ListItem,
      Paragraph,
      UndoRedo,
      HardBreak,
    ],
    enableInputRules: false,
    content: value,
    onUpdate({ editor }) {
      onRichTextChange(editor.getHTML());
    },
  });

  return (
    <div className="text-editor">
      <FormGroup className={formGroupClasses}>
        <FormLabel>
          {label}
          {required && " *"}
        </FormLabel>

        <div className="rich-text-editor">
          <RichTextMenu editor={editor} />
          <EditorContent editor={editor} />
        </div>
      </FormGroup>
      {helpText && <small>{helpText}</small>}
    </div>
  );
}

export default RichText;
