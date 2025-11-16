import React from "react";
import AdminSidebar from "../components/admin/AdminSidebar";
import { Outlet } from "react-router-dom";
import "../styles/admin/AdminLayout.css";

export default function AdminLayout() {
  return (
    <div className="admin-layout">
      <AdminSidebar />
      <main className="admin-main">
        <Outlet />
      </main>
    </div>
  );
}
