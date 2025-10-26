import React, { useState, useEffect } from "react";
import { Link, useNavigate, useLocation } from "react-router-dom";
import "../styles/Header.css";

const Header = () => {
  const [menuOpen, setMenuOpen] = useState(false);
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  const navigate = useNavigate();
  const location = useLocation();

  // Check login state whenever localStorage changes or the page reloads
  useEffect(() => {
    const checkLogin = () => {
      setIsLoggedIn(!!localStorage.getItem("userToken"));
    };

    // initial load
    checkLogin();
    window.addEventListener("storage", checkLogin);

    return () => window.removeEventListener("storage", checkLogin);
  }, []);

  const toggleMenu = () => setMenuOpen(!menuOpen);

  const scrollToSection = (id) => {
    if (location.pathname !== "/") {
      navigate("/");
      setTimeout(() => {
        const el = document.getElementById(id);
        if (el) el.scrollIntoView({ behavior: "smooth" });
      }, 300);
    } else {
      const el = document.getElementById(id);
      if (el) el.scrollIntoView({ behavior: "smooth" });
    }
    setMenuOpen(false);
  };

  const handleGenerateWorkout = () => {
    if (!isLoggedIn) {
      navigate("/login");
    } else {
      navigate("/generate"); // Open the modal
    }
    setMenuOpen(false);
  };

  const handleLogout = () => {
    localStorage.removeItem("userToken");
    setIsLoggedIn(false);
    navigate("/login");
  };

  return (
    <header className="header">
      <div className="header-container">
        {/* Logo */}
        <div className="logo" onClick={() => navigate("/")}>
          FitSync
        </div>

        {/* Hamburger icon for mobile */}
        <div className="menu-icon" onClick={toggleMenu}>
          ☰
        </div>

        {/* Navigation Links */}
        <nav className={`nav ${menuOpen ? "active" : ""}`}>
          <span onClick={() => scrollToSection("hero")}>Home</span>
          <span onClick={() => scrollToSection("features")}>Features</span>
          <span onClick={() => scrollToSection("about")}>About</span>
          <span onClick={() => scrollToSection("contact")}>Contact</span>
          <span onClick={handleGenerateWorkout}>Generate Workout</span>

          <div className="auth-links">
            {!isLoggedIn ? (
              <>
                <Link to="/login" onClick={toggleMenu}>
                  Login
                </Link>
                <Link
                  to="/register"
                  className="register-btn"
                  onClick={toggleMenu}
                >
                  Register
                </Link>
              </>
            ) : (
              <div className="profile-dropdown">
                <span className="profile-link" onClick={() => navigate("/profile")}>
                  Profile
                </span>
                <button className="logout-btn" onClick={handleLogout}>
                  Logout
                </button>
              </div>
            )}
          </div>
        </nav>
      </div>
    </header>
  );
};

export default Header;
