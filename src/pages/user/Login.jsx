import React, { useState } from "react";
import axios from "axios";
import { Link, useNavigate } from "react-router-dom";
import "../../styles/LoginRegister.css";

const API_BASE = "/api";

const Login = () => {
  const navigate = useNavigate();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const [loading, setLoading] = useState(false);

  const handleLogin = async (e) => {
    e.preventDefault();
    setError("");
    setSuccess("");

    if (!email || !password) {
      setError("Please fill out both email and password.");
      return;
    }

    setLoading(true);
    try {
      const response = await axios.post(`${API_BASE}/index.php?route=user&action=login`, {
        email,
        password,
      });

      const data = response.data;

      if (data.success) {
        localStorage.setItem("user", JSON.stringify(data.user));
        setSuccess(data.message);
        setTimeout(() => navigate("/dashboard"), 1000);
      } else {
        setError(data.message || "Invalid email or password.");
      }
    } catch (err) {
      console.error("Login error:", err);
      setError(err.response?.data?.message || "Server error. Please try again later.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="split-container">
      <div className="left-panel">
        <div className="overlay">
          <h2>
            Welcome to <span>FitSync</span>
          </h2>
          <p>Stay fit. Stay focused. Stay strong.</p>
        </div>
      </div>

      <div className="right-panel">
        <div className="auth-card">
          <header>
            <h1>
              Welcome Back<span className="dot">.</span>
            </h1>
            <p>
              Don’t have an account? <Link to="/register">Register</Link>
            </p>
          </header>

          <form className="auth-form" onSubmit={handleLogin}>
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

            <div className="input-group">
              <input
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
                autoComplete="current-password"
              />
              <label>Password</label>
            </div>

            <button type="submit" disabled={loading}>
              {loading ? "Signing in…" : "Login"}
            </button>

            <p className="forgot-password">
              <Link to="/ForgotPassword">Forgot Password?</Link>
            </p>

            {error && <p className="auth-error">{error}</p>}
            {success && <p className="auth-success">{success}</p>}
          </form>
        </div>
      </div>
    </div>
  );
};

export default Login;