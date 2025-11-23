import { useEffect, useState } from "react";
import axios from "axios";
import "../../styles/admin/admin-common.css";
import "../../styles/admin/AdminDashboard.css";
import GeneratedWorkoutView from "../../components/GeneratedWorkoutView";
import ConfirmModal from "../../components/ConfirmModal";

const API_BASE = import.meta.env.VITE_API_BASE || "http://localhost/personalized-fitness-workout/backend/public";

export default function GeneratedWorkouts() {
  const [workouts, setWorkouts] = useState([]);
  const [loading, setLoading] = useState(false);

  const [viewOpen, setViewOpen] = useState(false);
  const [selected, setSelected] = useState(null);

  const [confirmOpen, setConfirmOpen] = useState(false);
  const [toDelete, setToDelete] = useState(null);

  const [dateErrorOpen, setDateErrorOpen] = useState(false);

  const [search, setSearch] = useState("");
  const [goal, setGoal] = useState("");
  const [dateFrom, setDateFrom] = useState("");
  const [dateTo, setDateTo] = useState("");

  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);

  // Load workouts w/ filters
  const load = async () => {
    setLoading(true);

    try {
      const params = new URLSearchParams();
      params.append("page", page);
      params.append("limit", 10);

      if (search) params.append("search", search);
      if (goal) params.append("goal", goal);
      if (dateFrom) params.append("from", dateFrom);
      if (dateTo) params.append("to", dateTo);

      const res = await axios.get(
        `${API_BASE}/index.php?route=admin&action=getGeneratedWorkouts&${params.toString()}`
      );

      if (res.data.success) {
        setWorkouts(res.data.workouts);
        setTotalPages(res.data.pagination.totalPages);
      }
    } catch (err) {
      console.error(err);
    }

    setLoading(false);
  };

  useEffect(() => {
    load();
  }, [page]);

  useEffect(() => {
    setPage(1);
  }, [search, goal, dateFrom, dateTo]);

  const applyDateFilter = () => {
    if (dateFrom && dateTo && dateFrom > dateTo) {
      setDateErrorOpen(true);
      return;
    }
    load();
  };

  const resetFilters = () => {
    setSearch("");
    setGoal("");
    setDateFrom("");
    setDateTo("");
    load();
  };

  const openView = async (id) => {
    try {
      const res = await axios.get(
        `${API_BASE}/index.php?route=admin&action=getGeneratedWorkoutById&id=${id}`
      );
      if (res.data.success) {
        setSelected(res.data.workout);
        setViewOpen(true);
      }
    } catch (err) {
      console.error(err);
    }
  };

  const confirmDelete = (id) => {
    setToDelete(id);
    setConfirmOpen(true);
  };

  const doDelete = async () => {
    try {
      await axios.delete(
        `${API_BASE}/index.php?route=admin&action=deleteGeneratedWorkout&id=${toDelete}`
      );
      setConfirmOpen(false);
      setToDelete(null);
      load();
    } catch (err) {
      console.error(err);
    }
  };

  return (
    <div className="admin-dashboard">
      <h2>Generated Workouts</h2>
      <p className="subtitle">View and manage all user-generated workout plans</p>

      {/* Filters */}
      <div style={{
        display: 'flex',
        gap: '12px',
        marginBottom: '24px',
        flexWrap: 'wrap',
        alignItems: 'center'
      }}>
        <input
          type="text"
          placeholder="Search user or goal..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          style={{
            flex: '1 1 250px',
            padding: '10px 16px',
            fontSize: '14px',
            border: '1px solid #e5e7eb',
            borderRadius: '6px',
            outline: 'none'
          }}
        />

        <select 
          value={goal} 
          onChange={(e) => setGoal(e.target.value)}
          style={{
            padding: '10px 16px',
            fontSize: '14px',
            border: '1px solid #e5e7eb',
            borderRadius: '6px',
            outline: 'none',
            cursor: 'pointer'
          }}
        >
          <option value="">All Goals</option>
          <option value="weight loss">Weight Loss</option>
          <option value="muscle gain">Muscle Gain</option>
          <option value="endurance">Endurance</option>
        </select>

        <input
          type="date"
          value={dateFrom}
          onChange={(e) => setDateFrom(e.target.value)}
          style={{
            padding: '10px 16px',
            fontSize: '14px',
            border: '1px solid #e5e7eb',
            borderRadius: '6px',
            outline: 'none'
          }}
        />

        <input
          type="date"
          value={dateTo}
          onChange={(e) => setDateTo(e.target.value)}
          style={{
            padding: '10px 16px',
            fontSize: '14px',
            border: '1px solid #e5e7eb',
            borderRadius: '6px',
            outline: 'none'
          }}
        />

        <button
          onClick={applyDateFilter}
          style={{
            padding: '10px 20px',
            fontSize: '14px',
            fontWeight: '500',
            background: '#3b82f6',
            color: 'white',
            border: 'none',
            borderRadius: '6px',
            cursor: 'pointer'
          }}
        >
          Apply
        </button>

        <button
          onClick={resetFilters}
          style={{
            padding: '10px 20px',
            fontSize: '14px',
            fontWeight: '500',
            background: '#ef4444',
            color: 'white',
            border: 'none',
            borderRadius: '6px',
            cursor: 'pointer'
          }}
        >
          Reset
        </button>
      </div>

      <div className="data-table-container" style={{
        background: 'white',
        borderRadius: '8px',
        overflow: 'hidden',
        boxShadow: '0 1px 3px rgba(0,0,0,0.1)'
      }}>
        {loading ? (
          <div style={{textAlign: 'center', padding: '40px', color: '#6b7280', fontSize: '14px'}}>
            Loading…
          </div>
        ) : (
          <table className="data-table" style={{width: '100%', borderCollapse: 'collapse'}}>
            <thead>
              <tr style={{background: '#f9fafb', borderBottom: '1px solid #e5e7eb'}}>
                <th style={{padding: '16px', textAlign: 'left', fontWeight: '600', fontSize: '13px', color: '#374151'}}>ID</th>
                <th style={{padding: '16px', textAlign: 'left', fontWeight: '600', fontSize: '13px', color: '#374151'}}>User</th>
                <th style={{padding: '16px', textAlign: 'left', fontWeight: '600', fontSize: '13px', color: '#374151'}}>Goal</th>
                <th style={{padding: '16px', textAlign: 'left', fontWeight: '600', fontSize: '13px', color: '#374151'}}>Target</th>
                <th style={{padding: '16px', textAlign: 'left', fontWeight: '600', fontSize: '13px', color: '#374151'}}>Days</th>
                <th style={{padding: '16px', textAlign: 'left', fontWeight: '600', fontSize: '13px', color: '#374151'}}>Duration</th>
                <th style={{padding: '16px', textAlign: 'left', fontWeight: '600', fontSize: '13px', color: '#374151'}}>Created</th>
                <th style={{padding: '16px', textAlign: 'center', fontWeight: '600', fontSize: '13px', color: '#374151'}}>Actions</th>
              </tr>
            </thead>

            <tbody>
              {workouts.length === 0 ? (
                <tr>
                  <td colSpan="8" style={{textAlign: 'center', padding: '40px', color: '#6b7280', fontSize: '14px'}}>
                    No generated workouts found.
                  </td>
                </tr>
              ) : (
                workouts.map((w) => (
                  <tr key={w.Generate_ID} style={{borderBottom: '1px solid #f3f4f6'}}>
                    <td style={{padding: '16px', fontSize: '14px', color: '#374151'}}>{w.Generate_ID}</td>
                    <td style={{padding: '16px', fontSize: '14px', color: '#374151'}}>
                      {(
                        w.UserName ??
                        `${w.FirstName || ""} ${w.LastName || ""}`
                      ).trim() || "Unknown"}
                    </td>
                    <td style={{padding: '16px', fontSize: '14px', color: '#6b7280'}}>{w.Goal}</td>
                    <td style={{padding: '16px', fontSize: '14px', color: '#6b7280'}}>{w.Target_Muscle}</td>
                    <td style={{padding: '16px', fontSize: '14px', color: '#6b7280'}}>{w.Workout_Days}</td>
                    <td style={{padding: '16px', fontSize: '14px', color: '#6b7280'}}>{w.Duration_Min ? `${w.Duration_Min} min` : "-"}</td>
                    <td style={{padding: '16px', fontSize: '13px', color: '#6b7280'}}>{w.Created_At}</td>

                    <td style={{padding: '16px'}}>
                      <div style={{display: 'flex', gap: '8px', justifyContent: 'center'}}>
                        <button
                          onClick={() => openView(w.Generate_ID)}
                          style={{
                            padding: '8px 16px',
                            fontSize: '13px',
                            fontWeight: '500',
                            background: '#3b82f6',
                            color: 'white',
                            border: 'none',
                            borderRadius: '6px',
                            cursor: 'pointer'
                          }}
                        >
                          View
                        </button>

                        <button
                          onClick={() => confirmDelete(w.Generate_ID)}
                          style={{
                            padding: '8px 16px',
                            fontSize: '13px',
                            fontWeight: '500',
                            background: '#ef4444',
                            color: 'white',
                            border: 'none',
                            borderRadius: '6px',
                            cursor: 'pointer'
                          }}
                        >
                          Delete
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        )}
      </div>

      {/* Pagination */}
      <div className="pagination" style={{
        display: 'flex',
        justifyContent: 'center',
        alignItems: 'center',
        gap: '8px',
        marginTop: '24px'
      }}>
        <button
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
          onClick={() => setPage(page - 1)}
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

        <span style={{padding: '0 16px', fontSize: '14px', color: '#374151'}}>
          Page {page} of {totalPages}
        </span>

        <button
          onClick={() => setPage(page + 1)}
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
        <GeneratedWorkoutView
          workout={selected}
          onClose={() => {
            setViewOpen(false);
            setSelected(null);
          }}
        />
      )}

      {confirmOpen && (
        <ConfirmModal
          title="Delete Generated Workout"
          body="Are you sure you want to delete this generated workout? This action cannot be undone."
          onCancel={() => setConfirmOpen(false)}
          onConfirm={doDelete}
        />
      )}

      {dateErrorOpen && (
        <ConfirmModal
          title="Invalid Date Range"
          body="Start date cannot be later than end date."
          hideConfirm={true}
          confirmText="OK"
          onCancel={() => setDateErrorOpen(false)}
        />
      )}
    </div>
  );
}
