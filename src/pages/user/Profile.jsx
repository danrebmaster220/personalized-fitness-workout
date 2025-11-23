import React, { useEffect, useState, useRef } from "react";
import axios from "axios";
import "../../styles/Profile.css";
import "../../styles/admin/admin-common.css";

const API_BASE = import.meta.env.VITE_API_BASE || "http://localhost/personalized-fitness-workout/backend/public";

export default function Profile() {
  const stored = JSON.parse(localStorage.getItem("user")) || null;
  const userId = stored?.User_ID || stored?.UserId || null;

  const [loading, setLoading] = useState(false);
  const [profile, setProfile] = useState(stored || null);
  const [editMode, setEditMode] = useState(false);
  const [saving, setSaving] = useState(false);
  const [msg, setMsg] = useState(null);
  const [error, setError] = useState(null);

  // Image upload
  const fileRef = useRef();
  const [uploading, setUploading] = useState(false);

  // Security (change password)
  const [pwd, setPwd] = useState({ oldPassword: "", newPassword: "", confirmPassword: "" });
  const [pwdLoading, setPwdLoading] = useState(false);
  const [pwdMsg, setPwdMsg] = useState(null);

  // Email change / verification
  const [editingEmail, setEditingEmail] = useState(false);
  const [newEmail, setNewEmail] = useState(profile?.Email || "");
  const [emailLoading, setEmailLoading] = useState(false);
  const [emailMsg, setEmailMsg] = useState(null);

  // Fetch profile from backend (fresh)
  const fetchProfile = async () => {
    if (!userId) return;
    setLoading(true);
    try {
      // backend expects action=getProfile (maps to getUserProfile($userId))
      const res = await axios.get(`${API_BASE}/index.php?route=user&action=getProfile&userId=${userId}`);
      if (res.data?.success && res.data.profile) {
        setProfile(res.data.profile);
        // update localStorage to keep app in sync
        const merged = { ...(JSON.parse(localStorage.getItem("user")) || {}), ...res.data.profile };
        localStorage.setItem("user", JSON.stringify(merged));
        // Notify other tabs / components
        window.dispatchEvent(new Event("storage"));
      } else {
        setError(res.data?.message || "Unable to load profile");
      }
    } catch (e) {
      console.error(e);
      setError("Network error while fetching profile");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    // initial load
    fetchProfile();
    // listen for storage events to auto refresh (e.g. sidebar)
    const onStorage = () => {
      const latest = JSON.parse(localStorage.getItem("user"));
      if (latest) setProfile(prev => ({ ...prev, ...latest }));
    };
    window.addEventListener("storage", onStorage);
    return () => window.removeEventListener("storage", onStorage);
    // eslint-disable-next-line
  }, [userId]);

  // keep newEmail in sync when profile updates
  useEffect(() => {
    setNewEmail(profile?.Email || "");
  }, [profile?.Email]);

  // handle form local edits
  const handleChange = (e) => {
    const { name, value } = e.target;
    setProfile(prev => ({ ...prev, [name]: value }));
  };

  // Save profile (calls updateProfile)
  const handleSave = async () => {
    if (!userId) return;
    setSaving(true);
    setMsg(null);
    setError(null);

    // backend expects PascalCase DB field names in the JSON body
    const payload = {
      userId,
      FirstName: profile.FirstName,
      LastName: profile.LastName,
      Age: profile.Age,
      Height: profile.Height,
      Weight: profile.Weight,
      Gender: profile.Gender,
      Fitness_Level: profile.Fitness_Level,
      Activity_Level: profile.Activity_Level
    };

    try {
      const res = await axios.post(`${API_BASE}/index.php?route=user&action=updateProfile`, payload);
      if (res.data?.success) {
        setMsg("Profile updated");
        setEditMode(false);
        await fetchProfile();
      } else {
        setError(res.data?.message || "Failed to update profile");
      }
    } catch (e) {
      console.error(e);
      setError("Network/server error while saving profile");
    } finally {
      setSaving(false);
    }
  };

  // Upload image handler
  const handleUploadImage = async (file) => {
    if (!userId || !file) return;
    setUploading(true);
    setMsg(null);
    setError(null);

    try {
      const fd = new FormData();
      fd.append("userId", userId);
      fd.append("image", file);

      const res = await axios.post(`${API_BASE}/index.php?route=user&action=uploadImage`, fd, {
        headers: { "Content-Type": "multipart/form-data" }
      });

      if (res.data?.success && res.data.image) {
        // update profile state + localStorage
        setProfile(prev => ({ ...prev, Profile_Image: res.data.image }));
        const latest = { ...(JSON.parse(localStorage.getItem("user")) || {}), Profile_Image: res.data.image };
        localStorage.setItem("user", JSON.stringify(latest));
        // notify other tabs/components (storage event for other tabs, custom event for same-tab listeners)
  // notify other tabs/components: storage event for other tabs, custom event for same-tab listeners
  window.dispatchEvent(new Event("storage"));
  window.dispatchEvent(new Event("user-updated"));
        setMsg("Profile image updated");
      } else {
        setError(res.data?.message || "Upload failed");
      }
    } catch (e) {
      console.error(e);
      setError("Upload error");
    } finally {
      setUploading(false);
    }
  };

  const onFileChange = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    if (!["image/jpeg","image/jpg","image/png"].includes(file.type)) {
      setError("Only JPG/PNG images allowed");
      return;
    }
    if (file.size > 3 * 1024 * 1024) {
      setError("Max file size is 3MB");
      return;
    }
    handleUploadImage(file);
  };

  // Change password
  const handleChangePassword = async () => {
    setPwdMsg(null);
    setError(null);

    // For Google OAuth users setting up password for the first time
    const isGoogleUser = profile?.Login_Method === 'google' && !profile?.Password;
    
    if (!isGoogleUser) {
      // Normal users must provide old password
      if (!pwd.oldPassword || !pwd.newPassword) {
        setPwdMsg("Please fill both old and new password.");
        return;
      }
    } else {
      // Google users only need new password
      if (!pwd.newPassword) {
        setPwdMsg("Please enter a new password.");
        return;
      }
    }
    
    if (pwd.newPassword !== pwd.confirmPassword) {
      setPwdMsg("New password and confirm do not match.");
      return;
    }
    
    if (pwd.newPassword.length < 6) {
      setPwdMsg("Password must be at least 6 characters.");
      return;
    }

    setPwdLoading(true);
    try {
      const payload = {
        userId,
        oldPassword: pwd.oldPassword,
        newPassword: pwd.newPassword
      };
      const res = await axios.post(`${API_BASE}/index.php?route=user&action=changePassword`, payload);
      if (res.data?.success) {
        setPwdMsg("Password changed successfully");
        setPwd({ oldPassword: "", newPassword: "", confirmPassword: "" });
      } else {
        setPwdMsg(res.data?.message || "Failed to change password");
      }
    } catch (e) {
      console.error(e);
      setPwdMsg("Server error while changing password");
    } finally {
      setPwdLoading(false);
    }
  };

  // Resend verification email
  const handleResendVerification = async () => {
    if (!userId) return;
    setEmailLoading(true);
    setEmailMsg(null);
    try {
      const res = await axios.get(`${API_BASE}/index.php?route=user&action=resendVerification&userId=${userId}`);
      if (res.data?.success) {
        setEmailMsg('Verification email sent. Please check your inbox.');
      } else {
        setEmailMsg(res.data?.message || 'Failed to send verification email.');
      }
    } catch (e) {
      console.error(e);
      setEmailMsg('Server error while sending verification email');
    } finally {
      setEmailLoading(false);
    }
  };

  // Change email flow
  const handleChangeEmail = async () => {
    if (!userId || !newEmail) return;
    setEmailLoading(true);
    setEmailMsg(null);
    try {
      const payload = { userId, email: newEmail };
      const res = await axios.post(`${API_BASE}/index.php?route=user&action=changeEmail`, payload);
      if (res.data?.success) {
        setEmailMsg('Email updated. Verification sent to new address.');
        setEditingEmail(false);
        await fetchProfile();
      } else {
        setEmailMsg(res.data?.message || 'Failed to update email');
      }
    } catch (e) {
      console.error(e);
      setEmailMsg('Server error while updating email');
    } finally {
      setEmailLoading(false);
    }
  };

  // helper for image src
  const imageSrc = (p) => {
    // default blank avatar (SVG data URI)
    const DEFAULT = "data:image/svg+xml;utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Crect fill='%230b1220' width='24' height='24'/%3E%3Ccircle cx='12' cy='8' r='3.2' fill='%23cbd5e1'/%3E%3Cpath d='M4 20c0-4 4-6 8-6s8 2 8 6' fill='%23cbd5e1'/%3E%3C/svg%3E";
    if (!p) return DEFAULT;
    if (p.Profile_Image) {
      if (/^https?:\/\//i.test(p.Profile_Image)) return p.Profile_Image;
      if (import.meta.env.VITE_API_URL) return `${import.meta.env.VITE_API_URL}${p.Profile_Image}`;
      return p.Profile_Image;
    }
    return DEFAULT;
  };

  return (
    
    <div className="admin-dashboard">
      <h2>Profile</h2>
      <p className="subtitle">Manage your account details</p>

      <section className="content-area">
        <div className="profile-grid-top">
          <div className="profile-card">
            <div className="profile-image-box">
              <img className="profile-preview" src={imageSrc(profile)} alt="Profile" />
              <div style={{ flex: 1 }}>
                <div className="profile-meta">
                  <div className="profile-name">{profile?.FirstName} {profile?.LastName}</div>
                  <div className="profile-email">{profile?.Email}</div>
                </div>

                <div className="image-actions">
                  <input
                    type="file"
                    accept="image/*"
                    ref={fileRef}
                    id="profileImageInput"
                    onChange={onFileChange}
                    style={{ display: "none" }}
                  />
                  <button className="btn-primary" onClick={() => fileRef.current?.click()} disabled={uploading}>
                    {uploading ? "Uploading..." : "Change Photo"}
                  </button>
                </div>

                <div className="small muted" style={{ marginTop: 8 }}>
                  Tip: Use a clear headshot. Max 3MB. JPG/PNG.
                </div>
              </div>
            </div>
          </div>

          <div className="profile-card profile-info">
            <div className="card-header">
              <h3>Personal Information</h3>
              <div className="card-actions">
                {!editMode ? (
                  <button className="btn-secondary" onClick={() => setEditMode(true)}>Edit</button>
                ) : (
                  <>
                    <button className="btn-secondary" onClick={() => { setEditMode(false); fetchProfile(); }}>Cancel</button>
                    <button className="btn-primary" onClick={handleSave} disabled={saving}>
                      {saving ? "Saving..." : "Save"}
                    </button>
                  </>
                )}
              </div>
            </div>

            {msg && <div className="success">{msg}</div>}
            {error && <div className="error">{error}</div>}

            <div className="profile-grid">
              <label>
                <span className="lbl">First name</span>
                <input
                  name="FirstName"
                  value={profile?.FirstName || ""}
                  onChange={handleChange}
                  disabled={!editMode}
                />
              </label>

              <label>
                <span className="lbl">Last name</span>
                <input
                  name="LastName"
                  value={profile?.LastName || ""}
                  onChange={handleChange}
                  disabled={!editMode}
                />
              </label>

              <label>
                <span className="lbl">Email</span>
                <input value={profile?.Email || ""} disabled />
              </label>

              <label>
                <span className="lbl">Gender</span>
                <select name="Gender" value={profile?.Gender || "male"} onChange={handleChange} disabled={!editMode}>
                  <option value="male">Male</option>
                  <option value="female">Female</option>
                  <option value="other">Other</option>
                </select>
              </label>

              <label>
                <span className="lbl">Height (cm)</span>
                <input name="Height" value={profile?.Height || ""} onChange={handleChange} disabled={!editMode} />
              </label>

              <label>
                <span className="lbl">Weight (kg)</span>
                <input name="Weight" value={profile?.Weight || ""} onChange={handleChange} disabled={!editMode} />
              </label>

              <label>
                <span className="lbl">Age</span>
                <input name="Age" value={profile?.Age || ""} onChange={handleChange} disabled={!editMode} />
              </label>

              <label>
                <span className="lbl">Fitness Level</span>
                <select name="Fitness_Level" value={profile?.Fitness_Level || ""} onChange={handleChange} disabled={!editMode}>
                  <option value="">Select level</option>
                  <option value="beginner">Beginner</option>
                  <option value="intermediate">Intermediate</option>
                  <option value="advanced">Advanced</option>
                  <option value="athlete">Athlete</option>
                </select>
              </label>

              <label>
                <span className="lbl">Activity Level</span>
                <select name="Activity_Level" value={profile?.Activity_Level || ""} onChange={handleChange} disabled={!editMode}>
                  <option value="">Select</option>
                  <option value="low">Low</option>
                  <option value="moderate">Moderate</option>
                  <option value="high">High</option>
                  <option value="very-high">Very high</option>
                </select>
              </label>

              <div className="calc-stats wm-field-full">
                <strong>Calculated</strong>

                <div className="calc-grid">
                  <div>
                    <span className="stat-label">BMI</span>
                    <div className="stat-value">
                      {Number(profile?.Weight) > 0 && Number(profile?.Height) > 0
                        ? (Number(profile.Weight) / ((Number(profile.Height) / 100) ** 2)).toFixed(2)
                        : "—"}
                    </div>
                  </div>

                  <div>
                    <span className="stat-label">BMR</span>
                    <div className="stat-value">
                      {Number(profile?.Weight) > 0 &&
                      Number(profile?.Height) > 0 &&
                      Number(profile?.Age) > 0
                        ? calculateBMR(
                            Number(profile.Weight),
                            Number(profile.Height),
                            Number(profile.Age),
                            profile.Gender
                          ) + " kcal"
                        : "—"}
                    </div>
                  </div>

                  <div>
                    <span className="stat-label">TDEE (est.)</span>
                    <div className="stat-value">
                      {Number(profile?.Weight) > 0 &&
                      Number(profile?.Height) > 0 &&
                      Number(profile?.Age) > 0 &&
                      profile?.Activity_Level
                        ? estimateTDEE({
                            Weight: Number(profile.Weight),
                            Height: Number(profile.Height),
                            Age: Number(profile.Age),
                            Gender: profile.Gender,
                            Activity_Level: profile.Activity_Level
                          }) + " kcal"
                        : "—"}
                    </div>
                  </div>
                </div>

                <small className="muted">
                  These values are calculated client-side for preview. The AI uses
                  server-side values when generating workouts.
                </small>
              </div>
            </div>
          </div>
        </div>

        <div className="profile-card security-card">
          <h3>Security</h3>

          <div className="email-row" style={{ marginBottom: 12 }}>
            <div><strong>Email:</strong> {profile?.Email}</div>
            <div style={{ marginTop: 6 }}>
              {profile?.Is_Verified ? (
                <span className="pill" style={{ background: '#e6ffef', color: '#087a3b' }}>Verified</span>
              ) : (
                <>
                  <span className="pill muted">Not verified</span>
                  <button className="btn-secondary" style={{ marginLeft: 8 }} onClick={handleResendVerification} disabled={emailLoading}>Resend verification</button>
                  <button className="btn-secondary" style={{ marginLeft: 8 }} onClick={() => setEditingEmail(true)}>Change email</button>
                </>
              )}
            </div>
            {emailMsg && <div className="muted" style={{ marginTop: 8 }}>{emailMsg}</div>}
            {editingEmail && (
              <div className="email-edit-row" style={{ marginTop: 12 }}>
                <input 
                  type="email"
                  className="email-input"
                  value={newEmail} 
                  onChange={(e) => setNewEmail(e.target.value)}
                  placeholder="Enter new email address"
                />
                <button className="btn-primary email-save-btn" onClick={handleChangeEmail} disabled={emailLoading}>
                  {emailLoading ? 'Saving...' : 'Save'}
                </button>
                <button className="btn-secondary" onClick={() => setEditingEmail(false)}>Cancel</button>
              </div>
            )}
          </div>

          {/* Wrap password inputs in a form to satisfy browser semantics and password managers */}
          <form
            className="security-grid"
            onSubmit={(e) => { e.preventDefault(); handleChangePassword(); }}
          >
            {/* Hidden username field helps password managers and accessibility */}
            <input type="text" name="username" autoComplete="username" style={{ position: 'absolute', left: -9999, width: 1, height: 1, opacity: 0 }} />
            
            {/* If user has no password (Google OAuth user), allow them to SET one */}
            {profile?.Login_Method === 'google' && !profile?.Password ? (
              <>
                <div className="wm-field-full" style={{ marginBottom: 12, padding: '12px', background: '#e6f3ff', borderRadius: 6, border: '1px solid #0066cc' }}>
                  <strong style={{ color: '#0066cc' }}>📧 Enable Email Login</strong>
                  <p style={{ margin: '8px 0 0', fontSize: '0.9rem', color: '#555' }}>
                    You're currently signed in with Google. Set up a password to also login with your email address.
                  </p>
                </div>
                
                <label>
                  <span className="lbl">New password</span>
                  <input
                    type="password"
                    name="newPassword"
                    autoComplete="new-password"
                    value={pwd.newPassword}
                    onChange={(e) => setPwd(p => ({ ...p, newPassword: e.target.value }))}
                    placeholder="Create a password"
                  />
                </label>

                <label>
                  <span className="lbl">Confirm password</span>
                  <input
                    type="password"
                    name="confirmPassword"
                    autoComplete="new-password"
                    value={pwd.confirmPassword}
                    onChange={(e) => setPwd(p => ({ ...p, confirmPassword: e.target.value }))}
                    placeholder="Confirm your password"
                  />
                </label>

                <div className="security-actions">
                  <button type="submit" className="btn-primary" disabled={pwdLoading}>
                    {pwdLoading ? "Setting up..." : "Set up password"}
                  </button>
                  <div className="muted" style={{ marginTop: 8 }}>{pwdMsg}</div>
                </div>
              </>
            ) : (
              <>
                {/* Normal change password for users with existing password */}
                <label>
                  <span className="lbl">Current password</span>
                  <input
                    type="password"
                    name="oldPassword"
                    autoComplete="current-password"
                    value={pwd.oldPassword}
                    onChange={(e) => setPwd(p => ({ ...p, oldPassword: e.target.value }))}
                  />
                </label>

                <label>
                  <span className="lbl">New password</span>
                  <input
                    type="password"
                    name="newPassword"
                    autoComplete="new-password"
                    value={pwd.newPassword}
                    onChange={(e) => setPwd(p => ({ ...p, newPassword: e.target.value }))}
                  />
                </label>

                <label>
                  <span className="lbl">Confirm new</span>
                  <input
                    type="password"
                    name="confirmPassword"
                    autoComplete="new-password"
                    value={pwd.confirmPassword}
                    onChange={(e) => setPwd(p => ({ ...p, confirmPassword: e.target.value }))}
                  />
                </label>

                <div className="security-actions">
                  <button type="submit" className="btn-primary" disabled={pwdLoading}>
                    {pwdLoading ? "Saving..." : "Change password"}
                  </button>
                  <div className="muted" style={{ marginTop: 8 }}>{pwdMsg}</div>
                </div>
              </>
            )}
          </form>
        </div>
      </section>
    </div>
  );
}

/* ----- Helpers (inside same file) ----- */
function calculateBMR(weight, height, age, gender) {
  const h = Number(height);
  const w = Number(weight);
  const a = Number(age);
  if (!w || !h || !a) return "—";
  if ((gender || "male").toLowerCase() === "male") {
    return Math.round(10 * w + 6.25 * h - 5 * a + 5);
  }
  return Math.round(10 * w + 6.25 * h - 5 * a - 161);
}

function estimateTDEE(profile) {
  const bmr = calculateBMR(profile.Weight, profile.Height, profile.Age, profile.Gender);
  if (!bmr || isNaN(bmr)) return "—";
  const activity = (profile.Activity_Level || "moderate").toLowerCase();
  const multiplier = activity === "low" ? 1.2 : activity === "high" ? 1.725 : 1.55;
  return Math.round(bmr * multiplier);
}
