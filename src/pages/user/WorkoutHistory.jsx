// src/pages/user/WorkoutHistory.jsx
import React, { useEffect, useState } from "react";
import axios from "axios";
import GeneratedWorkoutView from "../../components/GeneratedWorkoutView"; // reuse admin detail modal (works fine)
import "../../styles/admin/admin-common.css";
import "../../styles/admin/AdminDashboard.css";

const API_BASE = import.meta.env.VITE_API_BASE || "http://localhost/personalized-fitness-workout/backend/public";

export default function WorkoutHistory() {
  const user = JSON.parse(localStorage.getItem("user")) || null;
  const userId = user?.User_ID;

  const [workouts, setWorkouts] = useState([]);
  const [loading, setLoading] = useState(false);

  // filters / pagination
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [limit] = useState(10);
  const [totalPages, setTotalPages] = useState(1);

  // view modal
  const [viewOpen, setViewOpen] = useState(false);
  const [selected, setSelected] = useState(null);

  const loadHistory = async (opts = {}) => {
    if (!userId) return;
    setLoading(true);
    try {
      const params = new URLSearchParams();
      params.append("userId", userId);
      params.append("page", opts.page ?? page);
      params.append("limit", limit);
      if (opts.search !== undefined ? opts.search : search) {
        params.append("search", opts.search ?? search);
      }

      const res = await axios.get(`${API_BASE}/index.php?route=workout&action=history&${params.toString()}`);
      if (res.data?.success) {
        // Normalize in either case: data can be in .data or .workouts
        const data = res.data.data ?? res.data.workouts ?? [];
        setWorkouts(data);
        if (res.data.pagination) {
          setTotalPages(res.data.pagination.totalPages || 1);
        } else {
          // fallback: estimate pages from returned length
          setTotalPages(Math.max(1, Math.ceil((res.data.total ?? data.length) / limit)));
        }
      } else {
        setWorkouts([]);
      }
    } catch (err) {
      console.error("Failed to load workout history:", err);
      setWorkouts([]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadHistory({ page: 1, search });
    // reset to page 1 when search changes
    setPage(1);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [search]);

  useEffect(() => {
    loadHistory({ page });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [page]);

  const openView = async (id) => {
    // call endpoint to fetch detail (backend getOne)
    try {
      const res = await axios.get(`${API_BASE}/index.php?route=workout&action=getOne&id=${id}`);
      if (res.data?.success) {
        // backend returns { success:true, data: row }
        setSelected(res.data.data);
        setViewOpen(true);
      } else {
        alert(res.data?.message || "Workout not found");
      }
    } catch (err) {
      console.error(err);
      alert("Failed to fetch workout.");
    }
  };

  const downloadJSON = async (w) => {
    const workoutId = w.Generate_ID || w.id;
    // Open download URL directly in new window - simpler and more reliable
    window.open(`${API_BASE}/index.php?route=user&action=downloadWorkout&id=${workoutId}`, '_blank');
  };

  return (
    <div className="admin-dashboard">
      <h2>Your Generated Workouts</h2>
      <p className="subtitle">View, download, and manage all your workout plans</p>

      <div style={{marginBottom: '24px'}}>
        <input
          type="text"
          placeholder="Search by goal, muscle, or note..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          style={{
            width: '100%',
            maxWidth: '500px',
            padding: '12px 16px',
            fontSize: '14px',
            border: '1px solid #e5e7eb',
            borderRadius: '8px',
            outline: 'none',
            boxShadow: '0 1px 3px rgba(0,0,0,0.1)'
          }}
        />
      </div>

      <div className="data-table-container" style={{
        background: 'white',
        borderRadius: '8px',
        overflow: 'hidden',
        boxShadow: '0 1px 3px rgba(0,0,0,0.1)'
      }}>
        <table className="data-table" style={{width: '100%', borderCollapse: 'collapse'}}>
          <thead>
            <tr style={{background: '#f9fafb', borderBottom: '1px solid #e5e7eb'}}>
              <th style={{padding: '16px', textAlign: 'left', fontWeight: '600', fontSize: '13px', color: '#374151'}}>ID</th>
              <th style={{padding: '16px', textAlign: 'left', fontWeight: '600', fontSize: '13px', color: '#374151'}}>Goal</th>
              <th style={{padding: '16px', textAlign: 'left', fontWeight: '600', fontSize: '13px', color: '#374151'}}>Target</th>
              <th style={{padding: '16px', textAlign: 'left', fontWeight: '600', fontSize: '13px', color: '#374151'}}>Days</th>
              <th style={{padding: '16px', textAlign: 'left', fontWeight: '600', fontSize: '13px', color: '#374151'}}>Duration</th>
              <th style={{padding: '16px', textAlign: 'left', fontWeight: '600', fontSize: '13px', color: '#374151'}}>Created</th>
              <th style={{padding: '16px', textAlign: 'center', fontWeight: '600', fontSize: '13px', color: '#374151'}}>Actions</th>
            </tr>
          </thead>

          <tbody>
            {loading ? (
              <tr><td colSpan="7" style={{textAlign: 'center', padding: '40px', color: '#6b7280', fontSize: '14px'}}>Loading…</td></tr>
            ) : workouts.length === 0 ? (
              <tr><td colSpan="7" style={{textAlign: 'center', padding: '40px', color: '#6b7280', fontSize: '14px'}}>No generated workouts found.</td></tr>
            ) : (
              workouts.map((w) => (
                <tr key={w.Generate_ID ?? w.id} style={{borderBottom: '1px solid #f3f4f6'}}>
                  <td style={{padding: '16px', fontSize: '14px', color: '#374151'}}>{w.Generate_ID ?? w.id}</td>
                  <td style={{padding: '16px', fontSize: '14px', color: '#374151', fontWeight: '500'}}>{w.Goal ?? "-"}</td>
                  <td style={{padding: '16px', fontSize: '14px', color: '#6b7280'}}>{w.Target_Muscle ?? "-"}</td>
                  <td style={{padding: '16px', fontSize: '14px', color: '#6b7280'}}>{w.Workout_Days ?? "-"}</td>
                  <td style={{padding: '16px', fontSize: '14px', color: '#6b7280'}}>{w.Duration_Min ? `${w.Duration_Min} min` : (w.duration ? `${w.duration} min` : "-")}</td>
                  <td style={{padding: '16px', fontSize: '13px', color: '#6b7280'}}>{w.Created_At ?? w.createdAt ?? "-"}</td>
                  <td style={{padding: '16px'}}>
                    <div style={{display: 'flex', gap: '8px', justifyContent: 'center'}}>
                      <button 
                        className="btn-small btn-primary" 
                        onClick={() => openView(w.Generate_ID ?? w.id)}
                        style={{
                          padding: '8px 16px',
                          fontSize: '13px',
                          fontWeight: '500',
                          borderRadius: '6px'
                        }}
                      >
                        View
                      </button>
                      <button 
                        className="btn-small btn-secondary" 
                        onClick={() => downloadJSON(w)}
                        style={{
                          padding: '8px 16px',
                          fontSize: '13px',
                          fontWeight: '500',
                          borderRadius: '6px',
                          background: 'white',
                          border: '1px solid #e5e7eb',
                          color: '#374151'
                        }}
                      >
                        Download
                      </button>
                    </div>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      <div className="pagination" style={{
        display: 'flex',
        justifyContent: 'center',
        alignItems: 'center',
        gap: '8px',
        marginTop: '24px'
      }}>
        <button 
          className="pg-btn" 
          onClick={() => setPage(1)} 
          disabled={page === 1}
          style={{
            padding: '8px 12px',
            fontSize: '13px',
            border: '1px solid #e5e7eb',
            borderRadius: '6px',
            background: 'white',
            cursor: page === 1 ? 'not-allowed' : 'pointer',
            opacity: page === 1 ? 0.5 : 1
          }}
        >
          « First
        </button>
        <button 
          className="pg-btn" 
          onClick={() => setPage(Math.max(1, page - 1))} 
          disabled={page === 1}
          style={{
            padding: '8px 12px',
            fontSize: '13px',
            border: '1px solid #e5e7eb',
            borderRadius: '6px',
            background: 'white',
            cursor: page === 1 ? 'not-allowed' : 'pointer',
            opacity: page === 1 ? 0.5 : 1
          }}
        >
          ‹ Prev
        </button>
        <span className="pg-info" style={{padding: '0 16px', fontSize: '14px', color: '#374151'}}>
          Page {page} of {totalPages}
        </span>
        <button 
          className="pg-btn" 
          onClick={() => setPage(Math.min(totalPages, page + 1))} 
          disabled={page === totalPages}
          style={{
            padding: '8px 12px',
            fontSize: '13px',
            border: '1px solid #e5e7eb',
            borderRadius: '6px',
            background: 'white',
            cursor: page === totalPages ? 'not-allowed' : 'pointer',
            opacity: page === totalPages ? 0.5 : 1
          }}
        >
          Next ›
        </button>
        <button 
          className="pg-btn" 
          onClick={() => setPage(totalPages)} 
          disabled={page === totalPages}
          style={{
            padding: '8px 12px',
            fontSize: '13px',
            border: '1px solid #e5e7eb',
            borderRadius: '6px',
            background: 'white',
            cursor: page === totalPages ? 'not-allowed' : 'pointer',
            opacity: page === totalPages ? 0.5 : 1
          }}
        >
          Last »
        </button>
      </div>

      {viewOpen && selected && (
        <GeneratedWorkoutView workout={selected} onClose={() => { setViewOpen(false); setSelected(null); }} />
      )}
    </div>
  );
}
