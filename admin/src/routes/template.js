import React from "react";
import { Route } from "react-router-dom";
import { List, Create, Update, Show } from "../components/template";

export default [
  <Route path="/v1/templates/create" component={Create} exact key="create" />,
  <Route path="/v1/templates/edit/:id" component={Update} exact key="update" />,
  <Route path="/v1/templates/show/:id" component={Show} exact key="show" />,
  <Route path="/v1/templates/" component={List} exact strict key="list" />,
  <Route path="/v1/templates/:page" component={List} exact strict key="page" />,
];
