// DashboardLayout.jsx and css inside src/layouts/
import React from "react";
import { Outlet } from "react-router-dom";
import Sidebar from "../components/Sidebar";
import "./DashboardLayout.css";

export default function DashboardLayout() {
  return (
    <div className="dashboard-layout">
      <Sidebar />
      <div className="dashboard-container">
        <Outlet />
      </div>
    </div>
  );
}
