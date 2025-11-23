import React, { useState } from "react";
import axios from "axios";
import { Link } from "react-router-dom";
import "../../styles/LoginRegister.css";

const API_BASE = import.meta.env.VITE_API_BASE || "http://localhost/personalized-fitness-workout/backend/public";

const ForgotPassword = () => {
  const [email, setEmail] = useState("");
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError("");
    setSuccess("");

    if (!email) {
      setError("Please enter your email.");
      return;
    }

    setLoading(true);
    try {
      const response = await axios.post(`${API_BASE}/index.php?route=user&action=forgot`, { email });
      const data = response.data;
      setSuccess(data.message || "Check your email for reset instructions.");
    } catch (err) {
      console.error(err);
      setError(err.response?.data?.message || "Server error. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="split-container">

      {/* Left panel stays consistent */}
      <div className="left-panel">
        <div className="overlay">
          <h2>Forgot Your <span>Password?</span></h2>
          <p>No worries, we’ll send you a reset link.</p>
        </div>
      </div>

      {/* Right panel changes content */}
      <div className="right-panel">
        <div className="auth-card">
          <header>
            <h1>Reset Password<span className="dot">.</span></h1>
            <p>Remembered it? <Link to="/login">Back to Login</Link></p>
          </header>

          <form className="auth-form" onSubmit={handleSubmit}>
            <div className="input-group">
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
                autoComplete="email"
              />
              <label>Email</label>
            </div>

            <button type="submit" disabled={loading}>
              {loading ? "Sending…" : "Send Reset Link"}
            </button>

            {error && <p className="auth-error">{error}</p>}
            {success && <p className="auth-success">{success}</p>}
          </form>
        </div>
      </div>
    </div>
  );
};

export default ForgotPassword;
