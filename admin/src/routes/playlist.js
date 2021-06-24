import React from "react";
import { Route } from "react-router-dom";
import { List, Create, Update, Show } from "../components/playlist";

export default [
  <Route path="/v1/playlists/create" component={Create} exact key="create" />,
  <Route path="/v1/playlists/edit/:id" component={Update} exact key="update" />,
  <Route path="/v1/playlists/show/:id" component={Show} exact key="show" />,
  <Route path="/v1/playlists/" component={List} exact strict key="list" />,
  <Route path="/v1/playlists/:page" component={List} exact strict key="page" />,
];
