import React, { Component } from "react";
import { Field, reduxForm } from "redux-form";
import PropTypes from "prop-types";

class Form extends Component {
  static propTypes = {
    handleSubmit: PropTypes.func.isRequired,
    error: PropTypes.string,
  };

  renderField = (data) => {
    data.input.className = "form-control";

    const isInvalid = data.meta.touched && !!data.meta.error;
    if (isInvalid) {
      data.input.className += " is-invalid";
      data.input["aria-invalid"] = true;
    }

    if (this.props.error && data.meta.touched && !data.meta.error) {
      data.input.className += " is-valid";
    }

    return (
      <div className={`form-group`}>
        <label
          htmlFor={`template_${data.input.name}`}
          className="form-control-label"
        >
          {data.input.name}
        </label>
        <input
          {...data.input}
          type={data.type}
          step={data.step}
          required={data.required}
          placeholder={data.placeholder}
          id={`template_${data.input.name}`}
        />
        {isInvalid && <div className="invalid-feedback">{data.meta.error}</div>}
      </div>
    );
  };

  render() {
    return (
      <form onSubmit={this.props.handleSubmit}>
        <Field
          component={this.renderField}
          name="id"
          type="text"
          placeholder=""
        />
        <Field
          component={this.renderField}
          name="icon"
          type="text"
          placeholder=""
        />
        <Field
          component={this.renderField}
          name="resources"
          type="text"
          placeholder=""
        />
        <Field
          component={this.renderField}
          name="title"
          type="text"
          placeholder=""
        />
        <Field
          component={this.renderField}
          name="description"
          type="text"
          placeholder=""
        />
        <Field
          component={this.renderField}
          name="tags"
          type="text"
          placeholder=""
        />
        <Field
          component={this.renderField}
          name="modified"
          type="number"
          placeholder=""
          normalize={(v) => parseFloat(v)}
        />
        <Field
          component={this.renderField}
          name="created"
          type="number"
          placeholder=""
          normalize={(v) => parseFloat(v)}
        />
        <Field
          component={this.renderField}
          name="modifiedBy"
          type="text"
          placeholder=""
        />
        <Field
          component={this.renderField}
          name="createdBy"
          type="text"
          placeholder=""
        />

        <button type="submit" className="btn btn-success">
          Submit
        </button>
      </form>
    );
  }
}

export default reduxForm({
  form: "template",
  enableReinitialize: true,
  keepDirtyOnReinitialize: true,
})(Form);
