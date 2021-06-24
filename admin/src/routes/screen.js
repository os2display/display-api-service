import React from "react";
import { Route } from "react-router-dom";
import { List, Create, Update, Show } from "../components/screen";

export default [
  <Route path="/v1/screens/create" component={Create} exact key="create" />,
  <Route path="/v1/screens/edit/:id" component={Update} exact key="update" />,
  <Route path="/v1/screens/show/:id" component={Show} exact key="show" />,
  <Route path="/v1/screens/" component={List} exact strict key="list" />,
  <Route path="/v1/screens/:page" component={List} exact strict key="page" />,
];
