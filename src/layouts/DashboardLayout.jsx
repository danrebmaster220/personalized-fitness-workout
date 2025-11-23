// DashboardLayout.jsx
import React, { useEffect, useState } from "react";
import Sidebar from "../components/Sidebar";
import { Outlet, useNavigate } from "react-router-dom";
import axios from "axios";
import "./DashboardLayout.css";

const API_BASE = import.meta.env.VITE_API_BASE || "http://localhost/personalized-fitness-workout/backend/public";

export default function DashboardLayout() {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(true);
  const [sidebarOpen, setSidebarOpen] = useState(false);

  const toggleSidebar = () => setSidebarOpen(!sidebarOpen);

  useEffect(() => {
    // On mount, check if user session exists and sync with localStorage
    const checkSession = async () => {
      try {
        const res = await axios.get(`${API_BASE}/index.php?route=user&action=me`, { 
          withCredentials: true 
        });
        
        if (res.data?.success && res.data.user) {
          // Session exists - save to localStorage
          localStorage.setItem('user', JSON.stringify(res.data.user));
          window.dispatchEvent(new Event('storage')); // Notify other components
        } else {
          // No valid session - redirect to login
          localStorage.removeItem('user');
          navigate('/login');
        }
      } catch (error) {
        console.error('Session check failed:', error);
        // If API call fails, check if localStorage has user
        const stored = localStorage.getItem('user');
        if (!stored) {
          navigate('/login');
        }
      } finally {
        setLoading(false);
      }
    };

    checkSession();
  }, [navigate]);

  if (loading) {
    return (
      <div style={{ 
        display: 'flex', 
        alignItems: 'center', 
        justifyContent: 'center', 
        height: '100vh',
        background: '#0b1220',
        color: '#fff'
      }}>
        Loading...
      </div>
    );
  }

  return (
    <div className="dashboard-app">
      <Sidebar isOpen={sidebarOpen} toggleSidebar={toggleSidebar} />
      <div className="main-content">
        {/* Hamburger button only visible on mobile */}
        <button className={`hamburger-btn ${sidebarOpen ? 'open' : ''}`} onClick={toggleSidebar}>
          <span></span>
          <span></span>
          <span></span>
        </button>
        <Outlet />
      </div>
    </div>
  );
}
