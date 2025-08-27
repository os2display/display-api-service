import { React } from "react";

/**
 * @param {object} props The props.
 * @param {object} props.formData The form data.
 * @param {object} props.data The data.
 * @param {Function} props.onChange On change callback.
 * @returns {React.JSX} The component.
 */
function CheckboxOptions({ formData, data, onChange }) {
  const values = data[formData.name] ?? [];

  const containsValue = (value) => {
    return values.includes(value);
  };

  const onOptionChange = ({ target }) => {
    const { value } = target;

    const newValues = [...values];

    if (newValues.includes(value)) {
      newValues.splice(newValues.indexOf(value), 1);
    } else {
      newValues.push(value);
    }

    onChange({
      target: { id: formData.name, value: newValues },
    });
  };

  return (
    <div className={formData.formGroupClasses}>
      {formData.options.map((option) => (
        <div className="ms-2 mb-2" key={option.value}>
          <input
            type="checkbox"
            className="form-check-input me-1"
            value={option.value}
            onChange={onOptionChange}
            checked={containsValue(option.value)}
          />
          <span className="ms-2">{option.title}</span>
        </div>
      ))}
    </div>
  );
}

export default CheckboxOptions;
