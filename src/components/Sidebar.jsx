// inside src/components/Sidebar.jsx
import React, { useEffect, useState } from "react";
import { NavLink, useNavigate } from "react-router-dom";
import "../styles/Sidebar.css";
import axios from 'axios';
import LogoutConfirmModal from './LogoutConfirmModal';
import AppLogo from './AppLogo';

const API_BASE = import.meta.env.VITE_API_BASE || "http://localhost/personalized-fitness-workout/backend/public";

export default function Sidebar({ isOpen, toggleSidebar }) {
  const navigate = useNavigate();

  // Load user into state so UI updates instantly
  const [user, setUser] = useState(() => JSON.parse(localStorage.getItem("user")));

  useEffect(() => {
    const syncUser = () => {
      const updated = JSON.parse(localStorage.getItem("user"));
      setUser(updated);
    };

    // Listen for user updates from same-tab (custom event) and cross-tab (storage)
    window.addEventListener("user-updated", syncUser);
    window.addEventListener("storage", syncUser);

    return () => {
      window.removeEventListener("user-updated", syncUser);
      window.removeEventListener("storage", syncUser);
    };
  }, []);

  const [showLogoutModal, setShowLogoutModal] = React.useState(false);

  const logout = () => {
    setShowLogoutModal(true);
  };

  const confirmLogout = async () => {
    try {
      await axios.get(`${API_BASE}/index.php?route=user&action=logout`, { withCredentials: true });
    } catch (e) {
      console.error('Logout failed', e);
    }
    localStorage.clear();
    setShowLogoutModal(false);
    navigate("/login");
  };

  const cancelLogout = () => setShowLogoutModal(false);

  return (
    <>
      {/* Mobile overlay */}
      {isOpen && <div className="sidebar-overlay" onClick={toggleSidebar}></div>}
      
      <aside className={`sidebar ${isOpen ? 'open' : ''}`}>
        {/* Close button inside sidebar */}
        <button className="sidebar-close-btn" onClick={toggleSidebar}>
          <span></span>
          <span></span>
        </button>

        <div className="sidebar-top">
        <NavLink to="/dashboard" className="logo">
          {/* use a scoped class name so the logo span doesn't accidentally match the
              global `.sidebar` selector (that would apply fixed/full-height styles
              to the logo element). */}
          <AppLogo appName={undefined} className="sidebar-logo" />
        </NavLink>

        <div className="profile">
          <img
            src={
              user?.Profile_Image
                ? (/^https?:\/\//i.test(user.Profile_Image) ? user.Profile_Image : (import.meta.env.VITE_API_URL ? `${import.meta.env.VITE_API_URL}${user.Profile_Image}` : user.Profile_Image))
                : "data:image/svg+xml;utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Crect fill='%230b1220' width='24' height='24'/%3E%3Ccircle cx='12' cy='8' r='3.2' fill='%23cbd5e1'/%3E%3Cpath d='M4 20c0-4 4-6 8-6s8 2 8 6' fill='%23cbd5e1'/%3E%3C/svg%3E"
            }
            alt="User"
          />
          <p className="user-name">{user?.FirstName} {user?.LastName}</p>
        </div>

        <nav className="nav-links">
          <NavLink to="/dashboard" onClick={toggleSidebar}>Dashboard</NavLink>
          <NavLink to="/generate-workout" onClick={toggleSidebar}>Generate Workout</NavLink>
          <NavLink to="/workout-history" onClick={toggleSidebar}>Workout History</NavLink>
          <NavLink to="/profile" onClick={toggleSidebar}>Profile</NavLink>
        </nav>
      </div>

      <div className="logout-section">
        <button className="logout-btn" onClick={logout}>Logout</button>
      </div>

      <LogoutConfirmModal isOpen={showLogoutModal} onConfirm={confirmLogout} onCancel={cancelLogout} />
      </aside>
    </>
  );
}
