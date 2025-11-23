import React from "react";
import { NavLink, useNavigate } from "react-router-dom";
import {
  FaTachometerAlt,
  FaUsers,
  FaDumbbell,
  FaServer,
  FaChartLine,
  FaCog,
  FaSignOutAlt
} from "react-icons/fa";

import "../../styles/admin/AdminSidebar.css";
import axios from 'axios';
import LogoutConfirmModal from '../LogoutConfirmModal';
import AppLogo from '../AppLogo';

const API_BASE = import.meta.env.VITE_API_BASE || "http://localhost/personalized-fitness-workout/backend/public";

export default function AdminSidebar({ isOpen, toggleSidebar }) {
  const navigate = useNavigate();
  const [showLogoutModal, setShowLogoutModal] = React.useState(false);

  const handleLogout = () => {
    setShowLogoutModal(true);
  };

  const confirmLogout = async () => {
    try {
      await axios.get(`${API_BASE}/index.php?route=user&action=logout`, { withCredentials: true });
    } catch (e) {
      console.error('Logout failed', e);
    }
    localStorage.removeItem("user");
    setShowLogoutModal(false);
    navigate("/login");
  };

  const cancelLogout = () => setShowLogoutModal(false);

  return (
    <>
      {/* Mobile overlay */}
      {isOpen && <div className="admin-sidebar-overlay" onClick={toggleSidebar}></div>}
      
      <div className={`admin-sidebar ${isOpen ? 'open' : ''}`}>
        {/* Close button inside sidebar */}
        <button className="admin-sidebar-close-btn" onClick={toggleSidebar}>
          <span></span>
          <span></span>
        </button>

        <div className="admin-logo"><AppLogo appName={undefined} /> <span style={{fontWeight:600, marginLeft:8}}>Admin</span></div>

      <nav className="admin-nav">
        <NavLink to="/admin/dashboard" onClick={toggleSidebar}>
          <FaTachometerAlt className="icon" /> Dashboard
        </NavLink>

        <NavLink to="/admin/users" onClick={toggleSidebar}>
          <FaUsers className="icon" /> User Management
        </NavLink>

        <NavLink to="/admin/generated" onClick={toggleSidebar}>
          <FaDumbbell className="icon" /> Generated Workouts
        </NavLink>

        <NavLink to="/admin/api-logs" onClick={toggleSidebar}>
          <FaServer className="icon" /> API Logs
        </NavLink>

        <NavLink to="/admin/system-reports" onClick={toggleSidebar}>
          <FaChartLine className="icon" /> System Reports
        </NavLink>

        <NavLink to="/admin/settings" onClick={toggleSidebar}>
          <FaCog className="icon" /> Settings
        </NavLink>
      </nav>

      <button className="admin-logout" onClick={handleLogout}>
        <FaSignOutAlt className="icon" /> Logout
      </button>

      <LogoutConfirmModal isOpen={showLogoutModal} onConfirm={confirmLogout} onCancel={cancelLogout} />
      </div>
    </>
  );
}
