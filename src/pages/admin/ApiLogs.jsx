import { useEffect, useState } from "react";
import axios from "axios";
import "../../styles/admin/ApiLogs.css";

const API_BASE = "/api";

export default function ApiLogs() {
  const [logs, setLogs] = useState([]);
  const [loading, setLoading] = useState(false);

  // Filters
  const [search, setSearch] = useState("");
  const [method, setMethod] = useState("");
  const [status, setStatus] = useState("");
  const [from, setFrom] = useState("");
  const [to, setTo] = useState("");

  // Pagination
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);

  // Modal viewer
  const [modalOpen, setModalOpen] = useState(false);
  const [selectedLog, setSelectedLog] = useState(null);

  const loadLogs = async () => {
    setLoading(true);
    try {
      const params = new URLSearchParams();
      params.append("page", page);
      params.append("limit", 10);

      if (search) params.append("search", search);
      if (method) params.append("method", method);
      if (status) params.append("status", status);
      if (from) params.append("from", from);
      if (to) params.append("to", to);

      const res = await axios.get(
        `${API_BASE}/index.php?route=admin&action=getApiLogs&${params.toString()}`
      );

      if (res.data.success) {
        setLogs(res.data.logs);
        setTotalPages(res.data.pagination.totalPages);
      }
    } catch (err) {
      console.error("API Logs error:", err);
    }
    setLoading(false);
  };

  useEffect(() => {
    loadLogs();
  }, [page]);

  // Reset page when filters change
  useEffect(() => {
    setPage(1);
  }, [search, method, status, from, to]);

  const resetFilters = () => {
    setSearch("");
    setMethod("");
    setStatus("");
    setFrom("");
    setTo("");
    setPage(1);
    loadLogs();
  };

  const viewLog = (log) => {
    setSelectedLog(log);
    setModalOpen(true);
  };

  return (
    <div className="api-page">
      <h2>API Logs</h2>
      <p className="subtitle">View your backend API request logs</p>

      {/* Filters */}
      <div className="api-filters">
        <input
          type="text"
          placeholder="Search endpoint or user..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
        />

        <select value={method} onChange={(e) => setMethod(e.target.value)}>
          <option value="">Method: All</option>
          <option value="GET">GET</option>
          <option value="POST">POST</option>
          <option value="PUT">PUT</option>
          <option value="DELETE">DELETE</option>
        </select>

        <select value={status} onChange={(e) => setStatus(e.target.value)}>
          <option value="">Status: All</option>
          <option value="200">200 OK</option>
          <option value="400">400 Error</option>
          <option value="500">500 Server Error</option>
        </select>

        <input type="date" value={from} onChange={(e) => setFrom(e.target.value)} />
        <input type="date" value={to} onChange={(e) => setTo(e.target.value)} />

        <button className="filter-btn" onClick={loadLogs}>Apply</button>
        <button className="reset-btn" onClick={resetFilters}>Reset</button>
      </div>

      <div className="api-table-wrapper">
        <table className="api-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Method</th>
              <th>Endpoint</th>
              <th>Status</th>
              <th>User</th>
              <th>Time</th>
              <th>Actions</th>
            </tr>
          </thead>

          <tbody>
            {loading ? (
              <tr><td colSpan="7" className="loading-row">Loading...</td></tr>
            ) : logs.length === 0 ? (
              <tr><td colSpan="7" className="empty">No logs found.</td></tr>
            ) : (
              logs.map((log) => (
                <tr key={log.Log_ID}>
                  <td>{log.Log_ID}</td>
                  <td className={`method ${log.Method.toLowerCase()}`}>{log.Method}</td>
                  <td>{log.Endpoint}</td>
                  <td className={`status ${log.Status === 200 ? "ok" : "error"}`}>
                    {log.Status}
                  </td>
                  <td>{log.User_Email || "Unknown"}</td>
                  <td>{log.Created_At}</td>
                  <td>
                    <button className="view-btn" onClick={() => viewLog(log)}>View</button>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {/* Pagination */}
      <div className="pagination">
        <button
          className="pg-btn"
          onClick={() => setPage(1)}
          disabled={page === 1}
        >
          « First
        </button>

        <button
          className="pg-btn"
          onClick={() => setPage(page - 1)}
          disabled={page === 1}
        >
          ‹ Prev
        </button>

        <span className="pg-page">
          Page {page} of {totalPages}
        </span>

        <button
          className="pg-btn"
          onClick={() => setPage(page + 1)}
          disabled={page === totalPages}
        >
          Next ›
        </button>

        <button
          className="pg-btn"
          onClick={() => setPage(totalPages)}
          disabled={page === totalPages}
        >
          Last »
        </button>
      </div>

      {/* Modal */}
      {modalOpen && selectedLog && (
        <div className="modal-overlay">
          <div className="modal-box">
            <h3>Log Details</h3>

            <div className="log-details">
              <p><strong>Method:</strong> {selectedLog.Method}</p>
              <p><strong>Endpoint:</strong> {selectedLog.Endpoint}</p>
              <p><strong>Status:</strong> {selectedLog.Status}</p>
              <p><strong>User:</strong> {selectedLog.User_Email || "Unknown"}</p>
              <p><strong>Timestamp:</strong> {selectedLog.Created_At}</p>

              <div className="json-block">
                <pre>{JSON.stringify(JSON.parse(selectedLog.Request_Body || "{}"), null, 2)}</pre>
              </div>
            </div>

            <button className="close-btn" onClick={() => setModalOpen(false)}>
              Close
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
