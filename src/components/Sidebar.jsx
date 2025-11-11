// inside src/components/
import React from "react";
import { NavLink, useNavigate } from "react-router-dom";
import "../styles/Sidebar.css";

export default function Sidebar() {
  const navigate = useNavigate();
  const user = JSON.parse(localStorage.getItem("user"));

  const logout = () => {
    localStorage.clear();
    navigate("/login");
  };

  return (
    <aside className="sidebar">
      <div>
        <NavLink to="/dashboard" className="logo">
          Fit<span>Sync</span>
        </NavLink>

        <div className="profile">
          <img src="https://i.pravatar.cc/150?img=12" alt="User" />
          <p className="user-name">{user?.First_Name || "User"}</p>
        </div>

        <nav className="nav-links">
          <NavLink to="/dashboard">Dashboard</NavLink>
          <NavLink to="/generate-workout">Generate Workout</NavLink>
          <NavLink to="/workout-history">Workout History</NavLink>
          <NavLink to="/profile">Profile</NavLink>
        </nav>
      </div>

      <div className="logout-section">
        <button className="logout-btn" onClick={logout}>Logout</button>
      </div>
    </aside>
  );
}
