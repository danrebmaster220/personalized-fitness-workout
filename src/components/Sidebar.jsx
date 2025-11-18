// inside src/components/Sidebar.jsx
import React, { useEffect, useState } from "react";
import { NavLink, useNavigate } from "react-router-dom";
import "../styles/Sidebar.css";

export default function Sidebar() {
  const navigate = useNavigate();

  // Load user into state so UI updates instantly
  const [user, setUser] = useState(() => JSON.parse(localStorage.getItem("user")));

  useEffect(() => {
    const syncUser = () => {
      const updated = JSON.parse(localStorage.getItem("user"));
      setUser(updated);
    };

    // Listen for user updates
    window.addEventListener("user-updated", syncUser);

    return () => window.removeEventListener("user-updated", syncUser);
  }, []);

  const logout = () => {
    localStorage.clear();
    navigate("/login");
  };

  return (
    <aside className="sidebar">
      <div className="sidebar-top">
        <NavLink to="/dashboard" className="logo">
          Fit<span>Sync</span>
        </NavLink>

        <div className="profile">
          <img
            src={
              user?.Profile_Image 
                ? `${import.meta.env.VITE_API_URL}${user.Profile_Image}`
                : "https://i.pravatar.cc/150?u=" + user?.Email
            }
            alt="User"
          />
          <p className="user-name">{user?.FirstName} {user?.LastName}</p>
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
