import React, { useEffect, useState } from "react";
import axios from "axios";
import { useSearchParams, useNavigate } from "react-router-dom";
import "../styles/LoginRegister.css";

const API_BASE = "http://localhost/personalized-fitness-workout/backend/public";

const EmailVerified = () => {
  const [params] = useSearchParams();
  const token = params.get("token");
  const [message, setMessage] = useState("Verifying your account...");
  const navigate = useNavigate();

  useEffect(() => {
    const verifyEmail = async () => {
      try {
        const response = await axios.get(`${API_BASE}/user.php?action=verify&token=${token}`);
        const data = response.data;
        setMessage(data.message);
        if (data.success) {
          setTimeout(() => navigate("/login"), 2000);
        }
      } catch (err) {
        console.error(err);
        setMessage("Verification failed or token expired.");
      }
    };
    verifyEmail();
  }, [token, navigate]);

  return (
    <div className="split-container">
      <div className="left-panel">
        <div className="overlay">
          <h2>
            Email <span>Verification</span>
          </h2>
          <p>Please wait while we confirm your account.</p>
        </div>
      </div>

      <div className="right-panel">
        <div className="auth-card center">
          <h1>{message}</h1>
        </div>
      </div>
    </div>
  );
};

export default EmailVerified;
