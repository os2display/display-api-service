import React from "react";
import { Route } from "react-router-dom";
import { List, Create, Update, Show } from "../components/media";

export default [
  <Route path="/v1/media/create" component={Create} exact key="create" />,
  <Route path="/v1/media/edit/:id" component={Update} exact key="update" />,
  <Route path="/v1/media/show/:id" component={Show} exact key="show" />,
  <Route path="/v1/media/" component={List} exact strict key="list" />,
  <Route path="/v1/media/:page" component={List} exact strict key="page" />,
];
