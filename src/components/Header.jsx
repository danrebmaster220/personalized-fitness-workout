import React, { useState, useEffect } from "react";
import { Link, useNavigate, useLocation } from "react-router-dom";
import "../styles/Header.css";
import { useSettings } from '../contexts/SettingsContext';
import axios from 'axios';
import LogoutConfirmModal from './LogoutConfirmModal';
import AppLogo from './AppLogo';

const API_BASE = import.meta.env.VITE_API_BASE || "http://localhost/personalized-fitness-workout/backend/public";

const Header = () => {
  const [menuOpen, setMenuOpen] = useState(false);
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  const navigate = useNavigate();
  const location = useLocation();
  const { settings } = useSettings();

  // Lock body scroll when menu is open
  useEffect(() => {
    if (menuOpen) {
      document.body.style.overflow = "hidden"; // prevent scroll
    } else {
      document.body.style.overflow = "auto";   // restore scroll
    }
  }, [menuOpen]);

  // Check login status
  useEffect(() => {
    const checkLogin = () => setIsLoggedIn(!!localStorage.getItem("userToken"));
    checkLogin();
    window.addEventListener("storage", checkLogin);
    return () => window.removeEventListener("storage", checkLogin);
  }, []);

  const toggleMenu = () => setMenuOpen(!menuOpen);

  const scrollToSection = (id) => {
    if (location.pathname !== "/") {
      navigate("/");
      setTimeout(() => {
        document.getElementById(id)?.scrollIntoView({ behavior: "smooth" });
      }, 300);
    } else {
      document.getElementById(id)?.scrollIntoView({ behavior: "smooth" });
    }
    setMenuOpen(false);
  };

  const handleGenerateWorkout = () => {
    if (!isLoggedIn) navigate("/login");
    else navigate("/generate");
    setMenuOpen(false);
  };

  const [showLogoutModal, setShowLogoutModal] = React.useState(false);

  const handleLogout = () => {
    // open confirmation modal
    setShowLogoutModal(true);
  };

  const confirmLogout = async () => {
    try {
      await axios.get(`${API_BASE}/index.php?route=user&action=logout`, { withCredentials: true });
    } catch (e) {
      // ignore errors but proceed to clear local state
      console.error('Logout request failed', e);
    }
    localStorage.removeItem("userToken");
    setIsLoggedIn(false);
    setMenuOpen(false);
    setShowLogoutModal(false);
    navigate("/login");
  };

  const cancelLogout = () => setShowLogoutModal(false);

  return (
    // Add a Home-specific class so styling for the landing header can be scoped
    <header className={`header ${location.pathname === '/' ? 'home-header' : ''}`}>
      <div className="header-container">
        <div className="logo" onClick={() => navigate("/")}>
          {settings?.logo_url ? (
            <img src={settings.logo_url} alt={settings.app_name || 'App'} style={{height:32}} />
          ) : (
            <AppLogo appName={settings?.app_name || 'FitSync'} className="header-logo" />
          )}
        </div>

        <nav className="nav-center">
          <span onClick={() => scrollToSection("hero")}>Home</span>
          <span onClick={() => scrollToSection("features")}>Features</span>
          <span onClick={() => scrollToSection("about")}>About</span>
          <span onClick={() => scrollToSection("contact")}>Contact</span>
          <span onClick={handleGenerateWorkout}>Generate Workout</span>
        </nav>

  <div className="auth-right">
          {!isLoggedIn ? (
            <>
              <Link to="/login" className="login-btn">Login</Link>
              <Link to="/register" className="register-btn">Register</Link>
            </>
          ) : (
              <div className="profile-dropdown">
              <span onClick={() => navigate("/profile")}>Profile</span>
              <button className="logout-btn" onClick={handleLogout}>Logout</button>
            </div>
          )}
        </div>

        {/* Hamburger menu icon */}
        <div className={`menu-icon ${menuOpen ? "open" : ""}`} onClick={toggleMenu}>
          ☰
        </div>
      </div>

      {/* Sliding mobile menu */}
      <div className={`mobile-menu ${menuOpen ? "open" : ""}`}>
        <span onClick={() => scrollToSection("hero")}>Home</span>
        <span onClick={() => scrollToSection("features")}>Features</span>
        <span onClick={() => scrollToSection("about")}>About</span>
        <span onClick={() => scrollToSection("contact")}>Contact</span>
        <span onClick={() => scrollToSection("CTA")}>Generate Workout</span>

        <div className="mobile-auth">
          {!isLoggedIn ? (
            <>
              <Link to="/login" className="login-btn" onClick={toggleMenu}>Login</Link>
              <Link to="/register" className="register-btn" onClick={toggleMenu}>
                Register
              </Link>
            </>
          ) : (
            <>
              <Link to="/profile" onClick={toggleMenu}>Profile</Link>
              <button className="logout-btn" onClick={handleLogout}>Logout</button>
            </>
          )}
        </div>
      </div>

      <LogoutConfirmModal isOpen={showLogoutModal} onConfirm={confirmLogout} onCancel={cancelLogout} />

      {/* Overlay */}
      <div
        className={`mobile-overlay ${menuOpen ? "open" : ""}`}
        onClick={toggleMenu}
      />
    </header>
  );
};

export default Header;
