import './App.css';
import {Link} from "react-router-dom";

function App() {
  return (
    <div className="App">
        <ul>
            <li><Link to="/slide">Slide</Link></li>
            <li><Link to="/media">Media</Link></li>
            <li><Link to="/template">Template</Link></li>
            <li><Link to="/screen">Screen</Link></li>
            <li><Link to="/playlist">Playlist</Link></li>
        </ul>
    </div>
  );
}

export default App;
