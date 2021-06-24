import React from 'react';
import ReactDOM from 'react-dom';
import { createStore, combineReducers, applyMiddleware } from 'redux';
import { Provider } from 'react-redux';
import thunk from 'redux-thunk';
import { reducer as form } from 'redux-form';
import { Route, Switch } from 'react-router-dom';
import { createBrowserHistory } from 'history';
import {
    ConnectedRouter,
    connectRouter,
    routerMiddleware
} from 'connected-react-router';
import 'bootstrap/dist/css/bootstrap.css';
import 'font-awesome/css/font-awesome.css';
import MediaList from './components/media/List';
import PlaylistList from './components/playlist/List';
import ScreenList from './components/screen/List';
import SlideList from './components/slide/List';
import SlideEdit from './components/slide/Update';
import TemplateList from './components/template/List';
import media from './reducers/media/';
import playlist from './reducers/playlist/';
import screen from './reducers/screen/';
import slide from './reducers/slide/';
import template from './reducers/template/';
import App from "./App";
import SlideRoutes from './routes/slide';
import ScreenRoutes from './routes/screen';
import TemplateRoutes from './routes/template';
import MediaRoutes from './routes/media';
import PlaylistRoutes from './routes/playlist';

const history = createBrowserHistory();
const store = createStore(
    combineReducers({
        router: connectRouter(history),
        form,
        /* Add your reducers here */
        media,
        playlist,
        screen,
        slide,
        template,
    }),
    applyMiddleware(routerMiddleware(history), thunk)
);

ReactDOM.render(
    <Provider store={store}>
        <ConnectedRouter history={history}>
            <Switch>
                <Route path="/" component={App} strict={true} exact={true}/>
                {SlideRoutes.map(route => route)}
                {ScreenRoutes.map(route => route)}
                {TemplateRoutes.map(route => route)}
                {MediaRoutes.map(route => route)}
                {PlaylistRoutes.map(route => route)}
                <Route render={() => <h1>Not Found</h1>} />
            </Switch>
        </ConnectedRouter>
    </Provider>,
    document.getElementById('root')
);
