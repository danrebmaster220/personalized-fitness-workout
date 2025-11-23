import React, { useState } from "react";
import AdminSidebar from "../components/admin/AdminSidebar";
import { Outlet } from "react-router-dom";
import "../styles/admin/AdminLayout.css";

export default function AdminLayout() {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const toggleSidebar = () => setSidebarOpen(!sidebarOpen);

  return (
    <div className="admin-layout">
      <AdminSidebar isOpen={sidebarOpen} toggleSidebar={toggleSidebar} />
      <main className="admin-main">
        {/* Hamburger button only visible on mobile */}
        <button className={`admin-hamburger-btn ${sidebarOpen ? 'open' : ''}`} onClick={toggleSidebar}>
          <span></span>
          <span></span>
          <span></span>
        </button>
        <Outlet />
      </main>
    </div>
  );
}
