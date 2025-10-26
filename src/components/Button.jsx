import React from "react";
import "../styles/Button.css";

const Button = ({ text, onClick, variant = "primary", loading = false }) => {
  return (
    <button
      className={`btn ${variant}`}
      onClick={onClick}
      disabled={loading}
    >
      {loading ? (
        <div className="btn-loading">
          <div className="spinner"></div>
          <span>Loading...</span>
        </div>
      ) : (
        text
      )}
    </button>
  );
};

export default Button;
