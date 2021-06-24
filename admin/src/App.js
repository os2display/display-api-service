import './App.css';
import {Link} from "react-router-dom";

function App() {
  return (
    <div className="App">
        <ul>
            <li><Link to="/v1/slides/">Slide</Link></li>
            <li><Link to="/v1/media/">Media</Link></li>
            <li><Link to="/v1/templates/">Template</Link></li>
            <li><Link to="/v1/screens/">Screen</Link></li>
            <li><Link to="/v1/playlists/">Playlist</Link></li>
        </ul>
    </div>
  );
}

export default App;
