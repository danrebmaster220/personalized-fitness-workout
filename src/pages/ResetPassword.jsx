import React, { useState, useEffect } from "react";
import axios from "axios";
import { useNavigate, useSearchParams } from "react-router-dom";
import "../styles/LoginRegister.css";

const API_BASE = "http://localhost/personalized-fitness-workout/backend/public";

const ResetPassword = () => {
  const [params] = useSearchParams();
  const token = params.get("token");
  const [password, setPassword] = useState("");
  const [confirm, setConfirm] = useState("");
  const [message, setMessage] = useState("");
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!password || !confirm) return setMessage("Please fill in both fields.");
    if (password !== confirm) return setMessage("Passwords do not match.");
    if (password.length < 6) return setMessage("Password must be at least 6 characters.");

    setLoading(true);
    try {
      const response = await axios.post(`${API_BASE}/user.php?action=reset`, {
        token,
        password,
      });
      const data = response.data;
      setMessage(data.message);
      if (data.success) {
        setTimeout(() => navigate("/login"), 2000);
      }
    } catch (err) {
      console.error(err);
      setMessage("Error resetting password.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="split-container">
      <div className="left-panel">
        <div className="overlay">
          <h2>
            Reset Your <span>Password</span>
          </h2>
          <p>Enter a new password for your account.</p>
        </div>
      </div>

      <div className="right-panel">
        <div className="auth-card">
          <header>
            <h1>
              New Password<span className="dot">.</span>
            </h1>
          </header>

          <form className="auth-form" onSubmit={handleSubmit}>
            <div className="input-group">
              <input
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
                autoComplete="new-password"
              />
              <label>New Password</label>
            </div>

            <div className="input-group">
              <input
                type="password"
                value={confirm}
                onChange={(e) => setConfirm(e.target.value)}
                required
                autoComplete="new-password"
              />
              <label>Confirm Password</label>
            </div>

            <button type="submit" disabled={loading}>
              {loading ? "Resetting..." : "Reset Password"}
            </button>

            {message && <p className="auth-message">{message}</p>}
          </form>
        </div>
      </div>
    </div>
  );
};

export default ResetPassword;
