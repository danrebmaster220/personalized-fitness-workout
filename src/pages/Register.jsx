import React, { useState } from "react";
import axios from "axios";
import { Link, useNavigate } from "react-router-dom";
import "../styles/LoginRegister.css";

const API_BASE = "http://localhost/personalized-fitness-workout/backend/public";

const Register = () => {
  const navigate = useNavigate();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const [loading, setLoading] = useState(false);

  const handleRegister = async (e) => {
    e.preventDefault();
    setError("");
    setSuccess("");

    if (!email || !password) {
      setError("Please fill out both email and password.");
      return;
    }

    if (password.length < 6) {
      setError("Password must be at least 6 characters.");
      return;
    }

    setLoading(true);
    try {
      const response = await axios.post(
        `${API_BASE}?route=user&action=register`,
        { email, password }
      );

      const data = response.data;

      if (data.success) {
        setSuccess("Account created successfully! Redirecting to login...");
        setEmail("");
        setPassword("");
        setTimeout(() => navigate("/login"), 1000);
      } else {
        setError(data.message || "Registration failed. Please try again.");
      }
    } catch (err) {
      console.error("Register error:", err);
      setError(
        err.response?.data?.message || "Server error. Please try again later."
      );
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
          <p>Customize your fitness journey today.</p>
        </div>
      </div>

      <div className="right-panel">
        <div className="auth-card">
          <header>
            <h1>
              Create Account<span className="dot">.</span>
            </h1>
            <p>
              Already have an account? <Link to="/login">Login</Link>
            </p>
          </header>

          <form className="auth-form" onSubmit={handleRegister}>
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
                autoComplete="new-password"
              />
              <label>Password</label>
            </div>

            <button type="submit" disabled={loading}>
              {loading ? "Creating account…" : "Register"}
            </button>

            {error && <p className="auth-error">{error}</p>}
            {success && <p className="auth-success">{success}</p>}
          </form>
        </div>
      </div>
    </div>
  );
};

export default Register;
