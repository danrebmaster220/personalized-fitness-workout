import React, { useState } from "react";
import axios from "axios";
import { useNavigate, useSearchParams, Link } from "react-router-dom";
import "../../styles/LoginRegister.css";

const API_BASE = import.meta.env.VITE_API_BASE || "http://localhost/personalized-fitness-workout/backend/public";

const ResetPassword = () => {
  const [params] = useSearchParams();
  const token = params.get("token");
  const [password, setPassword] = useState("");
  const [confirm, setConfirm] = useState("");
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setMessage("");
    setError("");

    if (!token) {
      return setError("Invalid or missing reset token.");
    }
    if (!password || !confirm) {
      return setError("Please fill in both fields.");
    }
    if (password !== confirm) {
      return setError("Passwords do not match.");
    }
    if (password.length < 6) {
      return setError("Password must be at least 6 characters.");
    }

    setLoading(true);
    try {
      const response = await axios.post(`${API_BASE}/index.php?route=user&action=reset`, {
        token,
        password,
      });

      const data = response.data;

      if (data.success) {
        setMessage("Password reset successful! Redirecting to login...");
        setTimeout(() => navigate("/login"), 2000);
      } else {
        setError(data.message || "Failed to reset password.");
      }
    } catch (err) {
      setError(err, "Server error. Try again later.");
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
          <p>Enter a new secure password for your account.</p>
        </div>
      </div>

      <div className="right-panel">
        <div className="auth-card">
          <header>
            <h1>
              New Password<span className="dot">.</span>
            </h1>
          </header>

          {!token ? (
            <div className="auth-error">
              ❗ Invalid or expired reset link. <br />
              <Link to="/ForgotPassword">Request a new reset link</Link>
            </div>
          ) : (
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

              {error && <p className="auth-error">{error}</p>}
              {message && <p className="auth-success">{message}</p>}
            </form>
          )}
        </div>
      </div>
    </div>
  );
};

export default ResetPassword;
