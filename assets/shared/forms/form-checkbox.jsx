import { FormCheck, FormGroup } from "react-bootstrap";

/**
 * A checkbox for forms.
 *
 * @param {string} props The props.
 * @param {string} props.name The name of the checkbox
 * @param {string} props.label The label for the checkbox
 * @param {string} props.helpText The help text for the checkbox, if it is needed.
 * @param {string} props.disabled If the checkbox is disabled.
 * @param {string} props.value The value of the checkbox
 * @param {Function} props.onChange The callback for changes in the checkbox
 * @param {string} props.formGroupClasses Classes for the form group
 * @returns {object} A checkbox.
 */
function FormCheckbox({
  name,
  label,
  onChange,
  helpText = "",
  disabled = false,
  formGroupClasses = "",
  value = false,
}) {
  /**
   * Transforms the target to something the form-components understand.
   *
   * @param {object} props The props
   * @param {object} props.target The object containing the values return via callback.
   */
  const onChecked = ({ target }) => {
    const returnTarget = { value: target.checked, id: target.name };
    onChange({ target: returnTarget });
  };

  return (
    <FormGroup className={formGroupClasses} controlId={`checkbox-${name}`}>
      <FormCheck
        id={`checkbox-${name}`}
        onChange={onChecked}
        type="checkbox"
        name={name}
        checked={value}
        disabled={disabled}
        label={label}
      />
      {helpText && <small>{helpText}</small>}
    </FormGroup>
  );
}

export default FormCheckbox;
