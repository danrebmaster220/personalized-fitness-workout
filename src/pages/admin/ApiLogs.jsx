import { useEffect, useState, useCallback } from "react";
import axios from "axios";
import "../../styles/admin/admin-common.css";
import "../../styles/admin/AdminDashboard.css";
import ConfirmModal from "../../components/ConfirmModal";

const API_BASE = import.meta.env.VITE_API_BASE || "http://localhost/personalized-fitness-workout/backend/public";

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
  const [dateErrorOpen, setDateErrorOpen] = useState(false);

  const loadLogs = useCallback(async () => {
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
  }, [page, search, method, status, from, to]);

  useEffect(() => {
    loadLogs();
  }, [loadLogs]);

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

  const applyFilters = () => {
    // Validate date range if both dates provided
    if (from && to) {
      const f = new Date(from);
      const t = new Date(to);
      if (f.getTime() > t.getTime()) {
        setDateErrorOpen(true);
        return;
      }
    }

    // if validation passed, load logs (page is managed by effect/reset)
    loadLogs();
  };

  const viewLog = (log) => {
    setSelectedLog(log);
    setModalOpen(true);
  };

  return (
    <div className="admin-dashboard">
      <h2>API Logs</h2>
      <p className="subtitle">Track and monitor all API requests and responses</p>

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
          placeholder="Search endpoint or user..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          style={{
            flex: '1 1 300px',
            padding: '10px 16px',
            fontSize: '14px',
            border: '1px solid #e5e7eb',
            borderRadius: '6px',
            outline: 'none'
          }}
        />

        <select 
          value={method} 
          onChange={(e) => setMethod(e.target.value)}
          style={{
            padding: '10px 16px',
            fontSize: '14px',
            border: '1px solid #e5e7eb',
            borderRadius: '6px',
            outline: 'none',
            cursor: 'pointer'
          }}
        >
          <option value="">Method: All</option>
          <option value="GET">GET</option>
          <option value="POST">POST</option>
          <option value="PUT">PUT</option>
          <option value="DELETE">DELETE</option>
        </select>

        <select 
          value={status} 
          onChange={(e) => setStatus(e.target.value)}
          style={{
            padding: '10px 16px',
            fontSize: '14px',
            border: '1px solid #e5e7eb',
            borderRadius: '6px',
            outline: 'none',
            cursor: 'pointer'
          }}
        >
          <option value="">Status: All</option>
          <option value="200">200 OK</option>
          <option value="400">400 Error</option>
          <option value="500">500 Server Error</option>
        </select>

        <input 
          type="date" 
          value={from} 
          onChange={(e) => setFrom(e.target.value)}
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
          value={to} 
          onChange={(e) => setTo(e.target.value)}
          style={{
            padding: '10px 16px',
            fontSize: '14px',
            border: '1px solid #e5e7eb',
            borderRadius: '6px',
            outline: 'none'
          }}
        />

        <button 
          onClick={() => applyFilters()}
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

      {/* Date validation error modal */}
      {dateErrorOpen && (
        <ConfirmModal
          title="Invalid Date Range"
          body="Start date cannot be later than end date."
          hideConfirm={true}
          confirmText="OK"
          onCancel={() => setDateErrorOpen(false)}
        />
      )}

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
              <th style={{padding: '16px', textAlign: 'left', fontWeight: '600', fontSize: '13px', color: '#374151'}}>Method</th>
              <th style={{padding: '16px', textAlign: 'left', fontWeight: '600', fontSize: '13px', color: '#374151'}}>Endpoint</th>
              <th style={{padding: '16px', textAlign: 'left', fontWeight: '600', fontSize: '13px', color: '#374151'}}>Status</th>
              <th style={{padding: '16px', textAlign: 'left', fontWeight: '600', fontSize: '13px', color: '#374151'}}>User</th>
              <th style={{padding: '16px', textAlign: 'left', fontWeight: '600', fontSize: '13px', color: '#374151'}}>Time</th>
              <th style={{padding: '16px', textAlign: 'center', fontWeight: '600', fontSize: '13px', color: '#374151'}}>Actions</th>
            </tr>
          </thead>

          <tbody>
            {loading ? (
              <tr><td colSpan="7" style={{textAlign: 'center', padding: '40px', color: '#6b7280', fontSize: '14px'}}>Loading...</td></tr>
            ) : logs.length === 0 ? (
              <tr><td colSpan="7" style={{textAlign: 'center', padding: '40px', color: '#6b7280', fontSize: '14px'}}>No logs found.</td></tr>
            ) : (
              logs.map((log) => (
                <tr key={log.Log_ID} style={{borderBottom: '1px solid #f3f4f6'}}>
                  <td style={{padding: '16px', fontSize: '14px', color: '#374151'}}>{log.Log_ID}</td>
                  <td style={{padding: '16px', fontSize: '13px'}}>
                    <span style={{
                      padding: '4px 10px',
                      borderRadius: '4px',
                      fontWeight: '500',
                      background: log.Method === 'GET' ? '#dbeafe' : 
                                 log.Method === 'POST' ? '#d1fae5' : 
                                 log.Method === 'PUT' ? '#fef3c7' : 
                                 log.Method === 'DELETE' ? '#fee2e2' : '#f3f4f6',
                      color: log.Method === 'GET' ? '#1e40af' : 
                            log.Method === 'POST' ? '#065f46' : 
                            log.Method === 'PUT' ? '#92400e' : 
                            log.Method === 'DELETE' ? '#991b1b' : '#374151'
                    }}>
                      {log.Method || 'N/A'}
                    </span>
                  </td>
                  <td style={{padding: '16px', fontSize: '14px', color: '#6b7280'}}>{log.Endpoint || 'N/A'}</td>
                  <td style={{padding: '16px', fontSize: '13px'}}>
                    <span style={{
                      padding: '4px 10px',
                      borderRadius: '4px',
                      fontWeight: '500',
                      background: log.Status === 200 ? '#d1fae5' : '#fee2e2',
                      color: log.Status === 200 ? '#065f46' : '#991b1b'
                    }}>
                      {log.Status || 'N/A'}
                    </span>
                  </td>
                  <td style={{padding: '16px', fontSize: '14px', color: '#6b7280'}}>{log.User_Email || "Unknown"}</td>
                  <td style={{padding: '16px', fontSize: '13px', color: '#6b7280'}}>{log.Created_At || 'N/A'}</td>
                  <td style={{padding: '16px', textAlign: 'center'}}>
                    <button 
                      onClick={() => viewLog(log)}
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
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
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

      {/* Modal */}
      {modalOpen && selectedLog && (
        <div className="modal-overlay" style={{
          position: 'fixed',
          top: 0,
          left: 0,
          right: 0,
          bottom: 0,
          background: 'rgba(0,0,0,0.5)',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          zIndex: 1000
        }}>
          <div style={{
            background: 'white',
            borderRadius: '12px',
            padding: '24px',
            maxWidth: '600px',
            width: '90%',
            maxHeight: '80vh',
            overflow: 'auto'
          }}>
            <h3 style={{marginBottom: '20px', fontSize: '20px', fontWeight: 'bold'}}>Log Details</h3>

            <div style={{marginBottom: '20px'}}>
              <p style={{marginBottom: '12px'}}><strong>Method:</strong> {selectedLog.Method}</p>
              <p style={{marginBottom: '12px'}}><strong>Endpoint:</strong> {selectedLog.Endpoint}</p>
              <p style={{marginBottom: '12px'}}><strong>Status:</strong> {selectedLog.Status}</p>
              <p style={{marginBottom: '12px'}}><strong>User:</strong> {selectedLog.User_Email || "Unknown"}</p>
              <p style={{marginBottom: '12px'}}><strong>Timestamp:</strong> {selectedLog.Created_At}</p>

              <div style={{
                marginTop: '16px',
                padding: '16px',
                background: '#f9fafb',
                borderRadius: '8px',
                border: '1px solid #e5e7eb'
              }}>
                <strong style={{display: 'block', marginBottom: '8px'}}>Request Body:</strong>
                <pre style={{
                  whiteSpace: 'pre-wrap',
                  wordBreak: 'break-word',
                  fontSize: '12px',
                  color: '#374151'
                }}>
                  {JSON.stringify(JSON.parse(selectedLog.Request_Body || "{}"), null, 2)}
                </pre>
              </div>
            </div>

            <button 
              onClick={() => setModalOpen(false)}
              style={{
                padding: '10px 20px',
                fontSize: '14px',
                fontWeight: '500',
                background: '#6b7280',
                color: 'white',
                border: 'none',
                borderRadius: '6px',
                cursor: 'pointer'
              }}
            >
              Close
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
