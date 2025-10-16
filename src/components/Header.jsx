import React, { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import "../styles/Header.css";

const Header = () => {
  const [menuOpen, setMenuOpen] = useState(false);
  const navigate = useNavigate();

  const toggleMenu = () => setMenuOpen(!menuOpen);

  return (
    <header className="header">
      <div className="header-container">
        {/* Logo */}
        <div className="logo" onClick={() => navigate("/")}>
          FitNes<span className="logo-accent">+</span>
        </div>

        {/* Hamburger icon for mobile */}
        <div className="menu-icon" onClick={toggleMenu}>
          ☰
        </div>

        {/* Navigation Links */}
        <nav className={`nav ${menuOpen ? "active" : ""}`}>
          <Link to="/" onClick={toggleMenu}>Home</Link>
          <Link to="/features" onClick={toggleMenu}>Features</Link>
          <Link to="/about" onClick={toggleMenu}>About</Link>
          <Link to="/contact" onClick={toggleMenu}>Contact</Link>

          <div className="auth-links">
            <Link to="/login" onClick={toggleMenu}>Login</Link>
            <Link to="/register" className="register-btn" onClick={toggleMenu}>Register</Link>
          </div>
        </nav>
      </div>
    </header>
  );
};

export default Header;
