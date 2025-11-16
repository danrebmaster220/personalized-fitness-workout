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

export default function AdminSidebar() {
  const navigate = useNavigate();

  const handleLogout = () => {
    localStorage.removeItem("user");
    navigate("/login");
  };

  return (
    <div className="admin-sidebar">
      <h2 className="admin-logo">FitSync Admin</h2>

      <nav className="admin-nav">
        <NavLink to="/admin/dashboard">
          <FaTachometerAlt className="icon" /> Dashboard
        </NavLink>

        <NavLink to="/admin/users">
          <FaUsers className="icon" /> User Management
        </NavLink>

        <NavLink to="/admin/generated">
          <FaDumbbell className="icon" /> Generated Workouts
        </NavLink>

        <NavLink to="/admin/api-logs">
          <FaServer className="icon" /> API Logs
        </NavLink>

        <NavLink to="/admin/reports">
          <FaChartLine className="icon" /> System Reports
        </NavLink>

        <NavLink to="/admin/settings">
          <FaCog className="icon" /> Settings
        </NavLink>
      </nav>

      <button className="admin-logout" onClick={handleLogout}>
        <FaSignOutAlt className="icon" /> Logout
      </button>
    </div>
  );
}
