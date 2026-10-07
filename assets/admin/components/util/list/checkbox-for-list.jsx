import { Form } from "react-bootstrap";
import { useTranslation } from "react-i18next";
import "./checkbox-for-list.scss";

/**
 * A checkbox for the list.
 *
 * @param {object} props Props.
 * @param {Function} props.onSelected The callback for on selected.
 * @param {boolean} props.selected Whether the checkbox should display selected.
 * @param {boolean} props.disabled Whether the checkbox is disabled
 * @param {string} props.title The title for the aria label
 * @returns {object} A checkbox for the list.
 */
function CheckboxForList({
  selected = false,
  onSelected,
  disabled = false,
  title,
}) {
  const { t } = useTranslation("common", { keyPrefix: "checkbox-for-list" });

  return (
    <Form>
      <Form.Group>
        <Form.Check
          checked={selected}
          onClick={disabled ? () => {} : onSelected}
          disabled={disabled}
          type="checkbox"
          readOnly
          aria-label={t("checkbox-form-aria-label", { this: title })}
        />
      </Form.Group>
    </Form>
  );
}

export default CheckboxForList;
