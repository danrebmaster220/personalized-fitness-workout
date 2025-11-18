import { BrowserRouter, Routes, Route } from "react-router-dom";

import MainLayout from "./layouts/MainLayout";
import DashboardLayout from "./layouts/DashboardLayout";
import AdminLayout from "./layouts/AdminLayout";
import AdminRoute from "./utils/AdminRoute";
import AuthLayout from "./layouts/AuthLayout";

// Landing Page
import Home from "./pages/Home";

// Auth Page
import Login from "./pages/Login";
import Register from "./pages/Register";

// User Pages
import Dashboard from "./pages/user/Dashboard";
import GenerateWorkout from "./pages/user/GenerateWorkout"
import WorkoutHistory from "./pages/user/WorkoutHistory"
import Profile from "./pages/user/Profile";

// Admin Pages
import AdminDashboard from "./pages/admin/AdminDashboard";
import UserManagement from "./pages/admin/UserManagement";
import GeneratedWorkouts from "./pages/admin/GeneratedWorkouts";
import ApiLogs from "./pages/admin/ApiLogs"; 
import SystemReports from "./pages/admin/SystemReports";  
import Settings from "./pages/admin/Settings";

export default function App() {
  return (
    <BrowserRouter>
      <Routes>

        {/* Landing Page */}
        <Route element={<MainLayout />}>
          <Route path="/" element={<Home />} />
        </Route>

        {/* AUTH PAGES WITHOUT HEADER */}
        <Route element={<AuthLayout />}>
          <Route path="/login" element={<Login />} />
          <Route path="/register" element={<Register />} />
        </Route>

        {/* User Interface */}
        <Route element={<DashboardLayout />}>
          <Route path="/dashboard" element={<Dashboard />} />
          <Route path="/generate-workout" element={<GenerateWorkout />} />
          <Route path="/workout-history" element={<WorkoutHistory />} />
          <Route path="/profile" element={<Profile />} />
        </Route>

        {/* Admin Interface */}
        <Route
          path="/admin"
          element={
            <AdminRoute>
              <AdminLayout />
            </AdminRoute>
          }
        >
          <Route path="dashboard" element={<AdminDashboard />} />
          <Route path="users" element={<UserManagement />} />
          <Route path="generated" element={<GeneratedWorkouts />} />
          <Route path="api-logs" element={<ApiLogs />} />
          <Route path="system-reports" element={<SystemReports />} />
          <Route path="settings" element={<Settings />} />
        </Route>

      </Routes>
    </BrowserRouter>
  );
}
