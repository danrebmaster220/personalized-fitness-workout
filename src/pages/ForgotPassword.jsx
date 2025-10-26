import React, { useState } from "react";
import axios from "axios";
import "../styles/LoginRegister.css";

const API_BASE = "http://localhost/personalized-fitness-workout/backend/public";

const ForgotPassword = () => {
  const [email, setEmail] = useState("");
  const [message, setMessage] = useState("");
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setMessage("");
    if (!email) return setMessage("Please enter your email.");

    setLoading(true);
    try {
      const response = await axios.post(`${API_BASE}/user.php?action=forgot`, { email });
      const data = response.data;
      setMessage(data.message || "Check your email for reset instructions.");
    } catch (err) {
      console.error(err);
      setMessage("Server error. Please try again later.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="split-container">
      <div className="left-panel">
        <div className="overlay">
          <h2>
            Forgot Your <span>Password?</span>
          </h2>
          <p>We'll send a reset link to your email.</p>
        </div>
      </div>

      <div className="right-panel">
        <div className="auth-card">
          <header>
            <h1>
              Reset Password<span className="dot">.</span>
            </h1>
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
              {loading ? "Sending..." : "Send Reset Link"}
            </button>

            {message && <p className="auth-message">{message}</p>}
          </form>
        </div>
      </div>
    </div>
  );
};

export default ForgotPassword;
