import React from "react";
import { Route } from "react-router-dom";
import { List, Create, Update, Show } from "../components/slide";

export default [
  <Route path="/v1/slides/create" component={Create} exact key="create" />,
  <Route path="/v1/slides/edit/:id" component={Update} exact key="update" />,
  <Route path="/v1/slides/show/:id" component={Show} exact key="show" />,
  <Route path="/v1/slides/" component={List} exact strict key="list" />,
  <Route path="/v1/slides/:page" component={List} exact strict key="page" />,
];
