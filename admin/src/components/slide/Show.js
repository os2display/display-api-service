import React, { Component } from "react";
import { connect } from "react-redux";
import { Link, Redirect } from "react-router-dom";
import PropTypes from "prop-types";
import { retrieve, reset } from "../../actions/slide/show";
import { del } from "../../actions/slide/delete";

class Show extends Component {
  static propTypes = {
    retrieved: PropTypes.object,
    loading: PropTypes.bool.isRequired,
    error: PropTypes.string,
    eventSource: PropTypes.instanceOf(EventSource),
    retrieve: PropTypes.func.isRequired,
    reset: PropTypes.func.isRequired,
    deleteError: PropTypes.string,
    deleteLoading: PropTypes.bool.isRequired,
    deleted: PropTypes.object,
    del: PropTypes.func.isRequired,
  };

  componentDidMount() {
    this.props.retrieve(decodeURIComponent(this.props.match.params.id));
  }

  componentWillUnmount() {
    this.props.reset(this.props.eventSource);
  }

  del = () => {
    if (window.confirm("Are you sure you want to delete this item?"))
      this.props.del(this.props.retrieved);
  };

  render() {
    if (this.props.deleted) return <Redirect to=".." />;

    const item = this.props.retrieved;

    return (
      <div>
        <h1>Show {item && item["@id"]}</h1>

        {this.props.loading && (
          <div className="alert alert-info" role="status">
            Loading...
          </div>
        )}
        {this.props.error && (
          <div className="alert alert-danger" role="alert">
            <span className="fa fa-exclamation-triangle" aria-hidden="true" />{" "}
            {this.props.error}
          </div>
        )}
        {this.props.deleteError && (
          <div className="alert alert-danger" role="alert">
            <span className="fa fa-exclamation-triangle" aria-hidden="true" />{" "}
            {this.props.deleteError}
          </div>
        )}

        {item && (
          <table className="table table-responsive table-striped table-hover">
            <thead>
              <tr>
                <th>Field</th>
                <th>Value</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <th scope="row">id</th>
                <td>{item["id"]}</td>
              </tr>
              <tr>
                <th scope="row">template</th>
                <td>{item["template"]}</td>
              </tr>
              <tr>
                <th scope="row">duration</th>
                <td>{item["duration"]}</td>
              </tr>
              <tr>
                <th scope="row">content</th>
                <td>{item["content"]}</td>
              </tr>
              <tr>
                <th scope="row">published</th>
                <td>{item["published"]}</td>
              </tr>
              <tr>
                <th scope="row">title</th>
                <td>{item["title"]}</td>
              </tr>
              <tr>
                <th scope="row">description</th>
                <td>{item["description"]}</td>
              </tr>
              <tr>
                <th scope="row">tags</th>
                <td>{item["tags"]}</td>
              </tr>
              <tr>
                <th scope="row">modified</th>
                <td>{item["modified"]}</td>
              </tr>
              <tr>
                <th scope="row">created</th>
                <td>{item["created"]}</td>
              </tr>
              <tr>
                <th scope="row">modifiedBy</th>
                <td>{item["modifiedBy"]}</td>
              </tr>
              <tr>
                <th scope="row">createdBy</th>
                <td>{item["createdBy"]}</td>
              </tr>
            </tbody>
          </table>
        )}
        <Link to=".." className="btn btn-primary">
          Back to list
        </Link>
        {item && (
          <Link to={`/v1/slides/edit/${encodeURIComponent(item["@id"])}`}>
            <button className="btn btn-warning">Edit</button>
          </Link>
        )}
        <button onClick={this.del} className="btn btn-danger">
          Delete
        </button>
      </div>
    );
  }

  renderLinks = (type, items) => {
    if (Array.isArray(items)) {
      return items.map((item, i) => (
        <div key={i}>{this.renderLinks(type, item)}</div>
      ));
    }

    return (
      <Link to={`../../${type}/show/${encodeURIComponent(items)}`}>
        {items}
      </Link>
    );
  };
}

const mapStateToProps = (state) => ({
  retrieved: state.slide.show.retrieved,
  error: state.slide.show.error,
  loading: state.slide.show.loading,
  eventSource: state.slide.show.eventSource,
  deleteError: state.slide.del.error,
  deleteLoading: state.slide.del.loading,
  deleted: state.slide.del.deleted,
});

const mapDispatchToProps = (dispatch) => ({
  retrieve: (id) => dispatch(retrieve(id)),
  del: (item) => dispatch(del(item)),
  reset: (eventSource) => dispatch(reset(eventSource)),
});

export default connect(mapStateToProps, mapDispatchToProps)(Show);
