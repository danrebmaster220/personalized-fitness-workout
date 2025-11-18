import React from "react";
import Sidebar from "../components/Sidebar";
import { Outlet } from "react-router-dom";
import "../styles/Sidebar.css";  // ensure sidebar styles load

export default function DashboardLayout() {
  return (
    <div className="dashboard-wrapper">
      <Sidebar />

      <div className="main-content">
        <Outlet />
      </div>
    </div>
  );
}
