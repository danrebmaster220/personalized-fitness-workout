import React, { useEffect, useState } from "react";
import axios from "axios";
import "../../styles/admin/admin-common.css";
import "../../styles/admin/AdminDashboard.css";

const API_BASE =
  import.meta.env.VITE_API_BASE ||
  "http://localhost/personalized-fitness-workout/backend/public";

const Settings = () => {
  const [settings, setSettings] = useState([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [edited, setEdited] = useState({});
  const [message, setMessage] = useState(null);
  const [isAdminAuth, setIsAdminAuth] = useState(false);

  useEffect(() => {
    // ensure axios sends cookies for backend session
    axios.defaults.withCredentials = true;
    fetchSettings();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const fetchSettings = async () => {
    setLoading(true);
    setMessage(null);
    try {
      const res = await axios.get(
        `${API_BASE}/index.php?route=admin&action=getSettings`,
        { withCredentials: true }
      );
      if (res.data && res.data.success) {
        setSettings(res.data.settings || []);
        setIsAdminAuth(true);
      } else {
        setSettings([]);
        setMessage(res.data?.message || "Not authenticated (admin)");
        setIsAdminAuth(false);
      }
    } catch (err) {
      console.error(err);
      setSettings([]);
      setMessage("Error loading settings. Make sure you are logged in as admin.");
      setIsAdminAuth(false);
    } finally {
      setLoading(false);
    }
  };

  const onChange = (k, v) => {
    setEdited((prev) => ({ ...prev, [k]: v }));
  };

  const handleSave = async () => {
    if (!isAdminAuth) {
      setMessage("Not authenticated (admin)");
      return;
    }

    const updates = [];
    for (const s of settings) {
      const key = s.k;
      if (Object.prototype.hasOwnProperty.call(edited, key)) {
        let value = edited[key];

        if (s.type === "json") {
          try {
            if (typeof value === "string") {
              value = value === "" ? null : JSON.parse(value);
            }
          } catch (e) {
            setMessage(`Invalid JSON for ${key}: ${e.message}`);
            return;
          }
        }

        updates.push({ k: key, v: value, type: s.type || "string" });
      }
    }

    if (updates.length === 0) {
      setMessage("No changes to save");
      return;
    }

    setSaving(true);
    setMessage(null);
    try {
      const res = await axios.post(
        `${API_BASE}/index.php?route=admin&action=saveSettings`,
        { updates, reason: "Admin UI" },
        { withCredentials: true, headers: { "Content-Type": "application/json" } }
      );
      if (res.data && res.data.success) {
        setMessage("Settings saved");
        setEdited({});
        fetchSettings();
      } else {
        setMessage(res.data?.message || "Save failed");
      }
    } catch (err) {
      console.error(err);
      setMessage("Error saving settings");
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="admin-dashboard">
        <h2>Settings</h2>
        <div style={{textAlign: 'center', padding: '40px', color: '#6b7280', fontSize: '14px'}}>
          Loading settings...
        </div>
      </div>
    );
  }

  const notAuth =
    !isAdminAuth && message && message.toString().toLowerCase().includes("not authenticated");

  return (
    <div className="admin-dashboard">
      <h2>Settings</h2>
      <p className="subtitle">Configure application settings and preferences</p>

      {message && (
        <div style={{
          padding: '16px',
          marginBottom: '24px',
          borderRadius: '8px',
          background: message.toLowerCase().includes('success') || message.toLowerCase().includes('saved') 
            ? '#d1fae5' 
            : '#fee2e2',
          color: message.toLowerCase().includes('success') || message.toLowerCase().includes('saved')
            ? '#065f46'
            : '#991b1b',
          fontSize: '14px',
          fontWeight: '500'
        }}>
          {message}
          {notAuth && (
            <div style={{ marginTop: 12 }}>
              <a 
                href="/login" 
                style={{
                  display: 'inline-block',
                  padding: '8px 16px',
                  background: '#3b82f6',
                  color: 'white',
                  textDecoration: 'none',
                  borderRadius: '6px',
                  fontSize: '14px',
                  fontWeight: '500'
                }}
              >
                Login as admin
              </a>
            </div>
          )}
        </div>
      )}

      {isAdminAuth ? (
        <>
          <div style={{
            background: 'white',
            borderRadius: '8px',
            padding: '24px',
            boxShadow: '0 1px 3px rgba(0,0,0,0.1)',
            marginBottom: '24px'
          }}>
            {settings.map((s, index) => {
              const isSecret = Number(s.is_secret) === 1;
              const currentValue = Object.prototype.hasOwnProperty.call(edited, s.k)
                ? edited[s.k]
                : s.v ?? "";

              return (
                <div 
                  key={s.k}
                  style={{
                    display: 'grid',
                    gridTemplateColumns: '200px 1fr',
                    gap: '24px',
                    padding: '20px 0',
                    borderBottom: index < settings.length - 1 ? '1px solid #f3f4f6' : 'none',
                    alignItems: 'start'
                  }}
                >
                  <label style={{
                    fontSize: '14px',
                    fontWeight: '600',
                    color: '#374151',
                    paddingTop: '10px'
                  }}>
                    {s.k}
                  </label>
                  <div style={{width: '100%'}}>
                    {isSecret ? (
                      <input
                        type="password"
                        value={currentValue}
                        placeholder={s.masked ? "••••••" : ""}
                        onChange={(e) => onChange(s.k, e.target.value)}
                        style={{
                          width: '100%',
                          padding: '10px 16px',
                          fontSize: '14px',
                          border: '1px solid #e5e7eb',
                          borderRadius: '6px',
                          outline: 'none'
                        }}
                      />
                    ) : s.type === "int" ? (
                      <input
                        type="number"
                        value={currentValue}
                        onChange={(e) => onChange(s.k, e.target.value)}
                        style={{
                          width: '100%',
                          padding: '10px 16px',
                          fontSize: '14px',
                          border: '1px solid #e5e7eb',
                          borderRadius: '6px',
                          outline: 'none'
                        }}
                      />
                    ) : s.type === "json" ? (
                      <textarea
                        value={
                          Object.prototype.hasOwnProperty.call(edited, s.k)
                            ? edited[s.k]
                            : JSON.stringify(s.v ?? "", null, 2)
                        }
                        onChange={(e) => onChange(s.k, e.target.value)}
                        rows={4}
                        style={{
                          width: '100%',
                          padding: '10px 16px',
                          fontSize: '14px',
                          border: '1px solid #e5e7eb',
                          borderRadius: '6px',
                          outline: 'none',
                          fontFamily: 'monospace',
                          resize: 'vertical'
                        }}
                      />
                    ) : (
                      <input
                        type="text"
                        value={currentValue}
                        onChange={(e) => onChange(s.k, e.target.value)}
                        style={{
                          width: '100%',
                          padding: '10px 16px',
                          fontSize: '14px',
                          border: '1px solid #e5e7eb',
                          borderRadius: '6px',
                          outline: 'none'
                        }}
                      />
                    )}
                    {s.description && (
                      <div style={{
                        marginTop: '6px',
                        fontSize: '13px',
                        color: '#6b7280',
                        lineHeight: '1.5'
                      }}>
                        {s.description}
                      </div>
                    )}
                  </div>
                </div>
              );
            })}
          </div>

          <div style={{ display: 'flex', justifyContent: 'flex-end' }}>
            <button 
              onClick={handleSave} 
              disabled={saving}
              style={{
                padding: '12px 24px',
                fontSize: '14px',
                fontWeight: '500',
                background: saving ? '#9ca3af' : '#3b82f6',
                color: 'white',
                border: 'none',
                borderRadius: '6px',
                cursor: saving ? 'not-allowed' : 'pointer',
                transition: 'background 0.2s'
              }}
            >
              {saving ? "Saving…" : "Save Settings"}
            </button>
          </div>
        </>
      ) : (
        <div style={{
          textAlign: 'center',
          padding: '40px',
          background: 'white',
          borderRadius: '8px',
          boxShadow: '0 1px 3px rgba(0,0,0,0.1)',
          color: '#6b7280',
          fontSize: '14px'
        }}>
          You must be logged in as an admin to view and edit settings.
        </div>
      )}
    </div>
  );
};

export default Settings;
