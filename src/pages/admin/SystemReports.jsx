import { useEffect, useState } from "react";
import axios from "axios";
import "../../styles/admin/admin-common.css";
import "../../styles/admin/AdminDashboard.css";

const API_BASE = import.meta.env.VITE_API_BASE || "http://localhost/personalized-fitness-workout/backend/public";

export default function SystemReports() {
  const [loading, setLoading] = useState(true);

  // Summary Cards
  const [summary, setSummary] = useState(null);

  // Charts
  const [userGrowth, setUserGrowth] = useState([]);
  const [workoutGrowth, setWorkoutGrowth] = useState([]);
  const [apiStats, setApiStats] = useState({
    total: 0,
    success: 0,
    error: 0,
  });

  const loadReports = async () => {
    setLoading(true);

    try {
      // Dashboard Stats (users, verified, api logs, workouts)
      const resSummary = await axios.get(
        `${API_BASE}/index.php?route=admin&action=getDashboardStats`
      );

      const s = resSummary.data.stats || resSummary.data.data || {};
      setSummary({
        totalUsers: s.totalUsers ?? 0,
        verifiedUsers: s.verifiedUsers ?? 0,
        unverifiedUsers: s.unverifiedUsers ?? 0,
        totalWorkouts: s.totalWorkouts ?? 0,
        totalApiLogs: s.totalApiLogs ?? 0,
      });

      // Monthly User Growth
      const resUsers = await axios.get(
        `${API_BASE}/index.php?route=admin&action=getMonthlyUserGrowth&months=12`
      );
      setUserGrowth(resUsers.data.data || []);

      // Monthly Workout Creation
      const resWorkouts = await axios.get(
        `${API_BASE}/index.php?route=admin&action=getMonthlyWorkouts&months=12`
      );
      setWorkoutGrowth(resWorkouts.data.data || []);

      // API Stats (simple version)
      const resApi = await axios.get(
        `${API_BASE}/index.php?route=admin&action=getApiLogs&page=1&limit=1`
      );
      const totalLogs = resApi.data.pagination.total ?? 0;

      // Count Success vs Error API logs
      const resApiAll = await axios.get(
        `${API_BASE}/index.php?route=admin&action=getApiLogs&limit=99999`
      );

      const logs = resApiAll.data.logs || [];

      const successCount = logs.filter((l) => l.Status_Code == 200).length;
      const errorCount = totalLogs - successCount;

      setApiStats({
        total: totalLogs,
        success: successCount,
        error: errorCount,
      });
    } catch (err) {
      console.error("System Reports Error:", err);
    }

    setLoading(false);
  };

  useEffect(() => {
    loadReports();
  }, []);

  if (loading || !summary) {
    return (
      <div className="admin-dashboard">
        <h2>System Reports</h2>
        <div style={{textAlign: 'center', padding: '40px', color: '#6b7280', fontSize: '14px'}}>
          Loading reports...
        </div>
      </div>
    );
  }

  return (
    <div className="admin-dashboard">
      <h2>System Reports</h2>
      <p className="subtitle">Comprehensive analytics and performance metrics</p>

      {/* SUMMARY CARDS - Stats Grid */}
      <div className="stats-grid">
        <div className="stat-card users">
          <h3>{summary.totalUsers}</h3>
          <p>Total Users</p>
        </div>

        <div className="stat-card workouts">
          <h3>{summary.totalWorkouts}</h3>
          <p>Generated Workouts</p>
        </div>

        <div className="stat-card verified">
          <h3>{summary.totalApiLogs}</h3>
          <p>Total API Logs</p>
        </div>

        <div className="stat-card logs">
          <h3>{apiStats.error}</h3>
          <p>API Errors</p>
        </div>
      </div>

      {/* CHARTS SECTION */}
      <div style={{
        display: 'grid',
        gridTemplateColumns: 'repeat(auto-fit, minmax(400px, 1fr))',
        gap: '24px',
        marginTop: '24px'
      }}>

        {/* User Growth Table */}
        <div style={{
          background: 'white',
          borderRadius: '8px',
          padding: '24px',
          boxShadow: '0 1px 3px rgba(0,0,0,0.1)'
        }}>
          <h3 style={{fontSize: '18px', fontWeight: '600', marginBottom: '20px', color: '#1f2937'}}>
            User Growth (12 months)
          </h3>
          <div style={{overflowX: 'auto'}}>
            <table style={{width: '100%', borderCollapse: 'collapse'}}>
              <thead>
                <tr style={{borderBottom: '2px solid #e5e7eb'}}>
                  <th style={{padding: '12px 16px', textAlign: 'left', fontWeight: '600', fontSize: '13px', color: '#374151'}}>Month</th>
                  <th style={{padding: '12px 16px', textAlign: 'right', fontWeight: '600', fontSize: '13px', color: '#374151'}}>Registered</th>
                </tr>
              </thead>
              <tbody>
                {userGrowth.map((u) => (
                  <tr key={u.month} style={{borderBottom: '1px solid #f3f4f6'}}>
                    <td style={{padding: '12px 16px', fontSize: '14px', color: '#374151'}}>{u.month}</td>
                    <td style={{padding: '12px 16px', textAlign: 'right', fontSize: '14px', color: '#6b7280', fontWeight: '500'}}>{u.count}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        {/* Workout Growth Table */}
        <div style={{
          background: 'white',
          borderRadius: '8px',
          padding: '24px',
          boxShadow: '0 1px 3px rgba(0,0,0,0.1)'
        }}>
          <h3 style={{fontSize: '18px', fontWeight: '600', marginBottom: '20px', color: '#1f2937'}}>
            Generated Workouts (12 months)
          </h3>
          <div style={{overflowX: 'auto'}}>
            <table style={{width: '100%', borderCollapse: 'collapse'}}>
              <thead>
                <tr style={{borderBottom: '2px solid #e5e7eb'}}>
                  <th style={{padding: '12px 16px', textAlign: 'left', fontWeight: '600', fontSize: '13px', color: '#374151'}}>Month</th>
                  <th style={{padding: '12px 16px', textAlign: 'right', fontWeight: '600', fontSize: '13px', color: '#374151'}}>Total</th>
                </tr>
              </thead>
              <tbody>
                {workoutGrowth.map((w) => (
                  <tr key={w.month} style={{borderBottom: '1px solid #f3f4f6'}}>
                    <td style={{padding: '12px 16px', fontSize: '14px', color: '#374151'}}>{w.month}</td>
                    <td style={{padding: '12px 16px', textAlign: 'right', fontSize: '14px', color: '#6b7280', fontWeight: '500'}}>{w.count}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        {/* API Stats */}
        <div style={{
          background: 'white',
          borderRadius: '8px',
          padding: '24px',
          boxShadow: '0 1px 3px rgba(0,0,0,0.1)'
        }}>
          <h3 style={{fontSize: '18px', fontWeight: '600', marginBottom: '20px', color: '#1f2937'}}>
            API Performance Summary
          </h3>
          <div style={{display: 'flex', flexDirection: 'column', gap: '16px'}}>
            <div style={{
              display: 'flex',
              justifyContent: 'space-between',
              alignItems: 'center',
              padding: '12px 16px',
              background: '#f9fafb',
              borderRadius: '6px'
            }}>
              <span style={{fontSize: '14px', color: '#6b7280'}}>Total Requests:</span>
              <strong style={{fontSize: '16px', color: '#1f2937'}}>{apiStats.total}</strong>
            </div>
            <div style={{
              display: 'flex',
              justifyContent: 'space-between',
              alignItems: 'center',
              padding: '12px 16px',
              background: '#d1fae5',
              borderRadius: '6px'
            }}>
              <span style={{fontSize: '14px', color: '#065f46'}}>Success:</span>
              <strong style={{fontSize: '16px', color: '#065f46'}}>{apiStats.success}</strong>
            </div>
            <div style={{
              display: 'flex',
              justifyContent: 'space-between',
              alignItems: 'center',
              padding: '12px 16px',
              background: '#fee2e2',
              borderRadius: '6px'
            }}>
              <span style={{fontSize: '14px', color: '#991b1b'}}>Errors:</span>
              <strong style={{fontSize: '16px', color: '#991b1b'}}>{apiStats.error}</strong>
            </div>
          </div>
        </div>

      </div>
    </div>
  );
}
