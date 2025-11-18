import { useEffect, useState } from "react";
import axios from "axios";
import "../../styles/admin/SystemReports.css";

const API_BASE = "/api";

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
    return <div className="sys-loading">Loading reports...</div>;
  }

  return (
    <div className="system-page">
      <h2>System Reports</h2>
      <p className="subtitle">Analytics and performance overview</p>

      {/* SUMMARY CARDS */}
      <div className="sys-cards">
        <div className="sys-card blue">
          <h3>{summary.totalUsers}</h3>
          <p>Total Users</p>
        </div>

        <div className="sys-card green">
          <h3>{summary.totalWorkouts}</h3>
          <p>Generated Workouts</p>
        </div>

        <div className="sys-card purple">
          <h3>{summary.totalApiLogs}</h3>
          <p>Total API Logs</p>
        </div>

        <div className="sys-card orange">
          <h3>{apiStats.error}</h3>
          <p>API Errors</p>
        </div>
      </div>

      {/* CHARTS SECTION */}
      <div className="chart-grid">

        {/* User Growth Table */}
        <div className="chart-box">
          <h3>User Growth (12 months)</h3>
          <table className="chart-table">
            <thead>
              <tr>
                <th>Month</th>
                <th>Registered</th>
              </tr>
            </thead>
            <tbody>
              {userGrowth.map((u) => (
                <tr key={u.month}>
                  <td>{u.month}</td>
                  <td>{u.count}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        {/* Workout Growth Table */}
        <div className="chart-box">
          <h3>Generated Workouts (12 months)</h3>
          <table className="chart-table">
            <thead>
              <tr>
                <th>Month</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              {workoutGrowth.map((w) => (
                <tr key={w.month}>
                  <td>{w.month}</td>
                  <td>{w.count}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        {/* API Stats */}
        <div className="chart-box">
          <h3>API Performance Summary</h3>
          <div className="api-stats">
            <div>Total Requests: <strong>{apiStats.total}</strong></div>
            <div>Success: <strong className="green-text">{apiStats.success}</strong></div>
            <div>Errors: <strong className="red-text">{apiStats.error}</strong></div>
          </div>
        </div>

      </div>
    </div>
  );
}
