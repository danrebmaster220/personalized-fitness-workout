import React, { useState, useEffect } from "react";
import axios from "axios";
import { Link, useNavigate } from "react-router-dom";
import "../styles/LoginRegister.css";
import { useSettings } from '../contexts/SettingsContext';
import AppLogo from '../components/AppLogo';
import GoogleLoginButton from "../components/GoogleLoginButton";

const API_BASE = import.meta.env.VITE_API_BASE || "http://localhost/personalized-fitness-workout/backend/public";

const Login = () => {
  const navigate = useNavigate();
  // On mount, check if user already has a session (logged in)
  const [alreadyLoggedIn, setAlreadyLoggedIn] = React.useState(false);
  const [currentUserRole, setCurrentUserRole] = React.useState(null);
  const { settings } = useSettings();

  React.useEffect(() => {
    let mounted = true;
    const check = async () => {
      try {
        const res = await axios.get(`${API_BASE}/index.php?route=user&action=me`, { withCredentials: true });
        if (!mounted) return;
        if (res.data?.success && res.data.user) {
          // Store user locally but DO NOT auto-redirect. Show the login page with an informative message
          localStorage.setItem('user', JSON.stringify(res.data.user));
          setAlreadyLoggedIn(true);
          setCurrentUserRole(res.data.user.Role || null);
        }
      } catch {
        // not logged in, ignore
      }
    };
    check();
    return () => { mounted = false; };
  }, []);
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const [loading, setLoading] = useState(false);

  // remove aggressive auto-redirect. If there's a server-side session (e.g. after OAuth),
  // the above effect will mark `alreadyLoggedIn` so the UI can offer choices to the user.

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
        if (data.user.Role === "admin") {
          setTimeout(() => navigate("/admin/dashboard"), 1000);
        } else {
          setTimeout(() => navigate("/dashboard"), 1000);
        }
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

  const handleContinue = () => {
    if (currentUserRole === 'admin') {
      navigate('/admin/dashboard');
    } else {
      navigate('/dashboard');
    }
  };

  const handleLogout = async () => {
    try {
      await axios.get(`${API_BASE}/index.php?route=user&action=logout`, { withCredentials: true });
    } catch (e) {
      console.error(e);
    }
    localStorage.removeItem('user');
    setAlreadyLoggedIn(false);
    setCurrentUserRole(null);
    // stay on the login page so user can sign in with a different account
  };

  return (
    <div className="split-container">
      <div className="left-panel">
        <div className="overlay">
            <h2>
              Welcome to <span style={{display:'inline-block'}}><AppLogo appName={settings?.app_name || 'FitSync'} className="left-panel" /></span>
            </h2>
            <p>{settings?.home_description || 'Stay fit. Stay focused. Stay strong.'}</p>
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

          {alreadyLoggedIn ? (
            <div className="auth-form">
              <p className="auth-info">You are already logged in.</p>
              <div style={{ display: 'flex', gap: '8px', justifyContent: 'center', marginTop: 12 }}>
                <button type="button" className="btn" onClick={handleContinue}>Continue to Dashboard</button>
                <button type="button" className="btn btn-secondary" onClick={handleLogout}>Logout</button>
              </div>
            </div>
          ) : (
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
          )}
          <div style={{ textAlign: 'center' }}>
            <GoogleLoginButton />
          </div>
        </div>
      </div>
    </div>
  );
};

export default Login;