import { React } from "react";
import DisplayLogo from "./logo.svg";

const Logo = () => {
  return (
    <div className="mx-3 mt-md-0 mb-3 mb-md-0">
      <DisplayLogo style={{width: "70px"}} alt="logo" />
    </div>
  );
};

export default Logo;
