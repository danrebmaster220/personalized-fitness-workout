import { useEffect, useState } from "react";
import axios from "axios";
import {
  ResponsiveContainer,
  LineChart,
  Line,
  XAxis,
  YAxis,
  Tooltip,
  CartesianGrid,
  BarChart,
  Bar,
  PieChart,
  Pie,
  Cell,
  Legend
} from "recharts";
import "../../styles/admin/admin-common.css";
import "../../styles/admin/AdminDashboard.css";

const API_BASE = "/api";

const COLORS = ["#3498db", "#27ae60", "#f1c40f", "#e74c3c", "#9b59b6"];

export default function AdminDashboard() {
  const [stats, setStats] = useState({
    totalUsers: 0,
    verifiedUsers: 0,
    totalWorkouts: 0,
    totalApiLogs: 0
  });

  const [monthlyUsers, setMonthlyUsers] = useState([]);
  const [monthlyWorkouts, setMonthlyWorkouts] = useState([]);
  const [verificationBreakdown, setVerificationBreakdown] = useState([]);
  const [recentUsers, setRecentUsers] = useState([]);
  const [range, setRange] = useState(12);

  const pieData = [
    { name: "Verified", value: stats.verifiedUsers },
    { name: "Unverified", value: stats.unverifiedUsers },
  ];

  const loadStats = async () => {
    try {
      const res = await axios.get(`${API_BASE}/index.php?route=admin&action=getDashboardStats`);
      if (res.data.success) setStats(res.data.stats);
    } catch (err) {
      console.error("Dashboard stats error:", err);
    }
  };

  const updateRange = (months) => {
    setRange(months);
    loadMonthlyUsers(months);
    loadMonthlyWorkouts(months);
  };

  const loadMonthlyUsers = async (months = range) => {
    const res = await axios.get(`${API_BASE}/index.php?route=admin&action=getMonthlyUserGrowth&months=${months}`);
    if (res.data.success) setMonthlyUsers(res.data.data);
  };

  const loadMonthlyWorkouts = async (months = range) => {
    const res = await axios.get(`${API_BASE}/index.php?route=admin&action=getMonthlyWorkouts&months=${months}`);
    if (res.data.success) setMonthlyWorkouts(res.data.data);
  };

  const loadVerification = async () => {
    try {
      const res = await axios.get(`${API_BASE}/index.php?route=admin&action=getVerificationBreakdown`);
      if (res.data.success) {
        const formatted = res.data.data.map(row => ({
          name: row.verified == 1 ? "Verified" : "Unverified",
          value: +row.count
        }));
        setVerificationBreakdown(formatted);
      }
    } catch (err) {
      console.error(err);
    }
  };

  const loadRecentUsers = async () => {
    try {
      const res = await axios.get(`${API_BASE}/index.php?route=admin&action=getRecentUsers`);
      if (res.data.success) setRecentUsers(res.data.data);
    } catch (err) {
      console.error(err);
    }
  };

  const verifiedPercent = stats.totalUsers > 0 
    ? ((stats.verifiedUsers / stats.totalUsers) * 100).toFixed(0)
    : 0;

  const unverifiedPercent = stats.totalUsers > 0 
    ? ((stats.unverifiedUsers / stats.totalUsers) * 100).toFixed(0)
    : 0;

  useEffect(() => {
    loadStats();
    loadMonthlyUsers();
    loadMonthlyWorkouts();
    loadVerification();
    loadRecentUsers();
  }, []);

  return (
    <div className="admin-dashboard">
      <h2>Dashboard Overview</h2>

      <div className="stats-grid">
        <div className="stat-card users">
          <h3>{stats.totalUsers}</h3>
          <p>Total Users</p>
        </div>

        <div className="stat-card verified">
          <h3>{stats.verifiedUsers}</h3>
          <p>Verified Users</p>
        </div>

        <div className="stat-card workouts">
          <h3>{stats.totalWorkouts}</h3>
          <p>Generated Workouts</p>
        </div>

        <div className="stat-card logs">
          <h3>{stats.totalApiLogs}</h3>
          <p>API Requests Logged</p>
        </div>
      </div>

      <div className="range-filter">
        <label>Showing:</label>
        <select value={range} onChange={(e) => updateRange(Number(e.target.value))}>
          <option value={3}>Last 3 Months</option>
          <option value={6}>Last 6 Months</option>
          <option value={12}>Last 12 Months</option>
        </select>
      </div>

      <div className="charts-grid">
        <div className="chart-card big">
          <h4>Monthly New Users (Last 12)</h4>
          <ResponsiveContainer width="100%" height={240}>
            <LineChart data={monthlyUsers}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="month" />
              <YAxis />
              <Tooltip />
              <Line type="monotone" dataKey="count" stroke="#3498db" strokeWidth={2} dot />
            </LineChart>
          </ResponsiveContainer>
        </div>

        <div className="chart-card">
          <h4>Monthly Generated Workouts</h4>
          <ResponsiveContainer width="100%" height={200}>
            <BarChart data={monthlyWorkouts}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="month" />
              <YAxis />
              <Tooltip />
              <Bar dataKey="count" fill="#f1c40f" />
            </BarChart>
          </ResponsiveContainer>
        </div>

        <div className="chart-card">
          <h3>Verification Breakdown</h3>

          <div className="percent-labels">
            <p><strong>{verifiedPercent}%</strong> Verified</p>
            <p><strong>{unverifiedPercent}%</strong> Unverified</p>
          </div>

          <ResponsiveContainer width="100%" height={250}>
            <PieChart>
              <Pie
                data={pieData}
                cx="50%"
                cy="50%"
                outerRadius={90}
                dataKey="value"
                label={({ name, percent }) =>
                  `${name} ${(percent * 100).toFixed(0)}%`
                }
              >
                {pieData.map((entry, index) => (
                  <Cell key={index} fill={COLORS[index]} />
                ))}
              </Pie>

              <Tooltip />
            </PieChart>
          </ResponsiveContainer>
        </div>
      </div>

      <div className="recent-section">
        <h4>Recent Users</h4>
        <div className="recent-list">
          {recentUsers.length === 0 ? (
            <p>No recent users.</p>
          ) : (
            recentUsers.map(u => (
              <div className="recent-item" key={u.User_ID}>
                <div className="ri-left">
                  <strong>{u.FirstName} {u.LastName}</strong>
                  <div className="small">{u.Email}</div>
                </div>
                <div className="ri-right">
                  <span className={`pill ${u.Is_Verified ? "ok" : "no"}`}>{u.Is_Verified ? "Verified" : "Unverified"}</span>
                  <div className="small">{new Date(u.Created_At).toLocaleString()}</div>
                </div>
              </div>
            ))
          )}
        </div>
      </div>
    </div>
  );
}
