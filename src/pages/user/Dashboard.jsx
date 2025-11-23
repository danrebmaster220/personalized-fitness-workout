import React, { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import axios from "axios";
import "../../styles/admin/admin-common.css";
import "../../styles/admin/AdminDashboard.css";

const API_BASE = import.meta.env.VITE_API_BASE || "http://localhost/personalized-fitness-workout/backend/public";

export default function Dashboard() {
  const navigate = useNavigate();
  const stored = JSON.parse(localStorage.getItem("user")) || null;
  const userId = stored?.User_ID || stored?.UserId || null;

  const [profile, setProfile] = useState(stored);
  const [workouts, setWorkouts] = useState([]);
  const [stats, setStats] = useState({ totalWorkouts: 0, lastGenerated: null, avgDuration: 0 });

  

  useEffect(() => {
    // attempt to refresh profile from localStorage
    const u = JSON.parse(localStorage.getItem("user"));
    if (u) setProfile(u);

    // inline loader to satisfy hook dependencies
    (async function fetchWorkouts() {
      if (!userId) return;
      try {
        const res = await axios.get(`${API_BASE}/index.php?route=workout&action=history&userId=${userId}&limit=8`);
        if (res.data?.success) {
          const rows = res.data.data;
          setWorkouts(rows);

          const total = res.data.pagination?.total ?? rows.length;
          const last = rows.length ? rows[0].Created_At : null;

          const durations = rows.map(r => Number(r.Duration_Min ?? r.Session_Minutes ?? r.Duration ?? 0)).filter(d => d > 0);
          const avg = durations.length ? Math.round(durations.reduce((a,b)=>a+b,0)/durations.length) : 0;

          setStats({ totalWorkouts: total, lastGenerated: last, avgDuration: avg });
        }
      } catch (err) {
        console.error("Failed to load workouts", err);
      }
    })();
  }, [userId]);

  // Helper function to format dates nicely
  const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins} min ago`;
    if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
    if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
    
    // Otherwise show date
    const month = date.toLocaleDateString('en-US', { month: 'short' });
    const day = date.getDate();
    const year = date.getFullYear();
    return `${month} ${day}, ${year}`;
  };

  const downloadPDF = (workoutId) => {
    window.open(`${API_BASE}/index.php?route=user&action=downloadWorkout&id=${workoutId}`, '_blank');
  };

  return (
    <div className="admin-dashboard">
      <h2>Dashboard</h2>
      <p className="subtitle">Overview of your fitness journey</p>

      <div className="stats-grid">
        <div className="stat-card users">
          <h3>{profile?.FirstName ? `${profile.FirstName} ${profile.LastName ?? ''}` : 'User'}</h3>
          <p>Account</p>
        </div>

        <div className="stat-card workouts">
          <h3>{stats.totalWorkouts}</h3>
          <p>Your Generated Workouts</p>
        </div>

        <div className="stat-card verified">
          <h3>{stats.lastGenerated ? formatDate(stats.lastGenerated) : '—'}</h3>
          <p>Last Generated</p>
        </div>

        <div className="stat-card logs">
          <h3>{stats.avgDuration ? `${stats.avgDuration} min` : '—'}</h3>
          <p>Avg Session Duration</p>
        </div>
      </div>

      <div className="recent-section">
        <h4>Recent Workouts</h4>
        <div className="recent-list">
          {workouts.length === 0 ? (
            <p className="muted">You haven't generated any workouts yet. Use Generate Workout to create your plan.</p>
          ) : (
            workouts.map(w => (
              <div className="recent-item" key={w.Generate_ID}>
                <div className="ri-left">
                  <strong>{w.Goal ?? (w.Target_Muscle ? `${w.Target_Muscle} focus` : 'Workout')}</strong>
                  <div className="small" style={{marginTop: '4px'}}>
                    <span style={{marginRight: '12px'}}>🎯 {w.Target_Muscle || 'N/A'}</span>
                    <span style={{marginRight: '12px'}}>📍 {w.Workout_Place || 'N/A'}</span>
                    <span>⏱️ {w.Duration_Min ? `${w.Duration_Min} min` : 'N/A'}</span>
                  </div>
                </div>
                <div className="ri-right">
                  <div className="small" style={{marginBottom: '8px', color: '#6b7280'}}>{formatDate(w.Created_At)}</div>
                  <div style={{display: 'flex', gap: '6px'}}>
                    <button 
                      className="btn-small btn-primary" 
                      onClick={() => navigate('/workout-history')}
                      style={{padding: '4px 10px', fontSize: '12px'}}
                    >
                      View
                    </button>
                    <button 
                      className="btn-small btn-secondary" 
                      onClick={() => downloadPDF(w.Generate_ID)}
                      style={{padding: '4px 10px', fontSize: '12px'}}
                    >
                      Download
                    </button>
                  </div>
                </div>
              </div>
            ))
          )}
        </div>
      </div>
    </div>
  );
}
