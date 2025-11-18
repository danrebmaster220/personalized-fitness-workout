// src/pages/user/WorkoutHistory.jsx
import React, { useEffect, useState } from "react";
import axios from "axios";
import GeneratedWorkoutView from "../../components/GeneratedWorkoutView"; // reuse admin detail modal (works fine)
import "../../styles/WorkoutHistory.css";

const API_BASE = "/api";

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

  const downloadJSON = (w) => {
    const filename = `workout-${w.Generate_ID || w.id || "unknown"}.json`;
    const content = JSON.stringify(w, null, 2);
    const url = "data:text/json;charset=utf-8," + encodeURIComponent(content);
    const a = document.createElement("a");
    a.href = url;
    a.download = filename;
    a.click();
  };

  return (
    <div className="history-page">
      <div className="history-header">
        <h2>Your Generated Workouts</h2>
        <p className="subtitle">All plans you created — view, download or re-open</p>
      </div>

      <div className="history-controls">
        <input
          className="h-search"
          type="text"
          placeholder="Search by goal, muscle, or note..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
        />
      </div>

      <div className="history-table-wrap">
        <table className="history-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Goal</th>
              <th>Target</th>
              <th>Days</th>
              <th>Duration</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>

          <tbody>
            {loading ? (
              <tr><td colSpan="7" className="loading-row">Loading…</td></tr>
            ) : workouts.length === 0 ? (
              <tr><td colSpan="7" className="empty">No generated workouts found.</td></tr>
            ) : (
              workouts.map((w) => (
                <tr key={w.Generate_ID ?? w.id}>
                  <td>{w.Generate_ID ?? w.id}</td>
                  <td>{w.Goal ?? "-"}</td>
                  <td>{w.Target_Muscle ?? "-"}</td>
                  <td>{w.Workout_Days ?? "-"}</td>
                  <td>{w.Duration_Min ? `${w.Duration_Min} min` : (w.duration ? `${w.duration} min` : "-")}</td>
                  <td>{w.Created_At ?? w.createdAt ?? "-"}</td>
                  <td className="history-actions">
                    <button className="btn-view" onClick={() => openView(w.Generate_ID ?? w.id)}>View</button>
                    <button className="btn-download" onClick={() => downloadJSON(w)}>JSON</button>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      <div className="history-pagination">
        <button className="pg-btn" onClick={() => setPage(1)} disabled={page === 1}>« First</button>
        <button className="pg-btn" onClick={() => setPage(Math.max(1, page - 1))} disabled={page === 1}>‹ Prev</button>
        <span className="pg-info">Page {page} of {totalPages}</span>
        <button className="pg-btn" onClick={() => setPage(Math.min(totalPages, page + 1))} disabled={page === totalPages}>Next ›</button>
        <button className="pg-btn" onClick={() => setPage(totalPages)} disabled={page === totalPages}>Last »</button>
      </div>

      {viewOpen && selected && (
        <GeneratedWorkoutView workout={selected} onClose={() => { setViewOpen(false); setSelected(null); }} />
      )}
    </div>
  );
}
