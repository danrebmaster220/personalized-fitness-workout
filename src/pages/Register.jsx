import React, { useState, useEffect } from "react";
import axios from "axios";
import { Link, useNavigate, useLocation } from "react-router-dom";
import "../styles/LoginRegister.css";
import { useSettings } from '../contexts/SettingsContext';
import AppLogo from '../components/AppLogo';

const API_BASE = import.meta.env.VITE_API_BASE || "http://localhost/personalized-fitness-workout/backend/public";

const Register = () => {
  const navigate = useNavigate();
  const [step, setStep] = useState(1); // Track step
  const [formData, setFormData] = useState({
    firstName: "",
    lastName: "",
    age: "",
    height: "",
    weight: "",
    gender: "",
    fitnessLevel: "",
    activityLevel: "", 
    email: "",
    password: "",
    confirmPassword: "",
  });
  const [isGoogleSignup, setIsGoogleSignup] = useState(false);
  const [googleUserId, setGoogleUserId] = useState(null);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const [loading, setLoading] = useState(false);
  const { settings } = useSettings();

  const handleChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const handleNext = () => {
    if (step === 1) {
      if (!formData.firstName || !formData.lastName || !formData.age || !formData.height || !formData.weight || !formData.gender || !formData.fitnessLevel) {
        setError("Please fill all personal info fields.");
        return;
      }
      setError("");
      // For Google signups we complete profile in a single step (no password)
      if (isGoogleSignup && googleUserId) {
        submitGoogleProfile();
        return;
      }
      setStep(2);
    }
  };

  // Parse query params to prefill form for Google signups
  const location = useLocation();
  useEffect(() => {
    const params = new URLSearchParams(location.search);
    const isGoogle = params.get('google') === '1';
    if (isGoogle) {
      // Try to fetch session user from backend (google_callback now creates a session)
      (async () => {
        try {
          const res = await axios.get(`${API_BASE}/index.php?route=user&action=me`, { withCredentials: true });
          if (res.data?.success && res.data.user) {
            const u = res.data.user;
            setFormData((prev) => ({ ...prev, firstName: u.FirstName || '', lastName: u.LastName || '', email: u.Email || '' }));
            setGoogleUserId(u.User_ID || null);
            setIsGoogleSignup(true);
            setStep(1);
          } else {
            // Fallback to query params if session not available
            const fn = params.get('firstName') || '';
            const ln = params.get('lastName') || '';
            const email = params.get('email') || '';
            const uid = params.get('userId') || null;
            setFormData((prev) => ({ ...prev, firstName: fn, lastName: ln, email }));
            setIsGoogleSignup(true);
            setGoogleUserId(uid);
            setStep(1);
          }
        } catch {
          const fn = params.get('firstName') || '';
          const ln = params.get('lastName') || '';
          const email = params.get('email') || '';
          const uid = params.get('userId') || null;
          setFormData((prev) => ({ ...prev, firstName: fn, lastName: ln, email }));
          setIsGoogleSignup(true);
          setGoogleUserId(uid);
          setStep(1);
        }
      })();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const submitGoogleProfile = async () => {
    setLoading(true);
    setError("");
    try {
      const payload = {
        userId: googleUserId,
        FirstName: formData.firstName,
        LastName: formData.lastName,
        Age: formData.age || null,
        Height: formData.height || null,
        Weight: formData.weight || null,
        Gender: formData.gender || null,
        Fitness_Level: formData.fitnessLevel || null,
        Activity_Level: formData.activityLevel || null,
      };

  const response = await axios.post(`${API_BASE}/index.php?route=user&action=updateProfile`, payload, { withCredentials: true });
      const data = response.data;
      if (data.success) {
        setSuccess("Profile updated. Redirecting to dashboard...");
        // Store returned user info in localStorage so the app shows updated profile
        if (data.user) {
          const merged = { ...(JSON.parse(localStorage.getItem('user')) || {}), ...data.user };
          localStorage.setItem('user', JSON.stringify(merged));
          // notify other tabs/components
          window.dispatchEvent(new Event('storage'));
        }
        setTimeout(() => navigate('/dashboard'), 1200);
      } else {
        setError(data.message || 'Failed to update profile.');
      }
    } catch (err) {
      console.error('Update profile error:', err);
      setError(err.response?.data?.message || 'Server error.');
    } finally {
      setLoading(false);
    }
  };

  const handleRegister = async (e) => {
    e.preventDefault();
    setError("");
    setSuccess("");

    if (!formData.email || !formData.password || formData.password !== formData.confirmPassword) {
      setError("Please fill all fields and ensure passwords match.");
      return;
    }

    setLoading(true);
    try {
      const response = await axios.post(
        `${API_BASE}/index.php?route=user&action=register`,
        {
          firstName: formData.firstName,
          lastName: formData.lastName,
          age: formData.age,
          height: formData.height,
          weight: formData.weight,
          gender: formData.gender,
          fitnessLevel: formData.fitnessLevel,
          activityLevel: formData.activityLevel,
          email: formData.email,
          password: formData.password
        }
      );

      console.log("Register API Response:", response.data);

      const data = response.data;

      if (data.success) {
        setSuccess("Account created successfully! Check your email to verify.");
        setTimeout(() => navigate("/login"), 1500);
      } else {
        setError(data.message || "Registration failed.");
      }
    } catch (err) {
      console.error("Register error response:", err.response?.data);
      setError(err.response?.data?.message || "Server error. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  // Render form body as a separate function to avoid large inline ternaries
  const renderFormBody = () => {
    if (isGoogleSignup) {
      return (
        <>
          {/* Google signup: show only personal info and readonly email */}
          {/* Personal Info Fields */}
          <div className="input-group">
            <input
              type="text"
              name="firstName"
              value={formData.firstName}
              onChange={handleChange}
              required
            />
            <label>First Name</label>
          </div>
          <div className="input-group">
            <input
              type="text"
              name="lastName"
              value={formData.lastName}
              onChange={handleChange}
              required
            />
            <label>Last Name</label>
          </div>
          <div className="input-group">
            <input
              type="number"
              name="age"
              value={formData.age}
              onChange={handleChange}
              required
            />
            <label>Age</label>
          </div>
          <div className="input-group">
            <input
              type="number"
              step="0.1"
              name="height"
              value={formData.height}
              onChange={handleChange}
              required
            />
            <label>Height (cm)</label>
          </div>
          <div className="input-group">
            <input
              type="number"
              step="0.1"
              name="weight"
              value={formData.weight}
              onChange={handleChange}
              required
            />
            <label>Weight (kg)</label>
          </div>
          <div className="input-group">
            <select
              name="gender"
              value={formData.gender}
              onChange={handleChange}
              required
            >
                    <option value="" disabled>Select Gender</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
              <option value="other">Other</option>
            </select>
            <label>Gender</label>
          </div>
          <div className="input-group">
            <select
              name="fitnessLevel"
              value={formData.fitnessLevel}
              onChange={handleChange}
              required
            >
                    <option value="" disabled>Select Fitness Level</option>
              <option value="beginner">Beginner</option>
              <option value="intermediate">Intermediate</option>
              <option value="advanced">Advanced</option>
            </select>
            <label>Fitness Level</label>
          </div>
          <div className="input-group">
            <select
              name="activityLevel"
              value={formData.activityLevel}
              onChange={handleChange}
              required
            >
                    <option value="" disabled>Select Activity Level</option>
              <option value="sedentary">Sedentary (No exercise)</option>
              <option value="light">Light (1–3 days/week)</option>
              <option value="moderate">Moderate (3–5 days/week)</option>
              <option value="active">Active (6–7 days/week)</option>
              <option value="very-active">Very Active/Athlete</option>
            </select>
            <label>Activity Level</label>
          </div>
          <div className="input-group">
            <input
              type="email"
              name="email"
              value={formData.email}
              readOnly
              disabled
            />
            <label>Email (from Google)</label>
          </div>
          <button type="submit" disabled={loading}>
            {loading ? "Saving…" : "Finish and continue"}
          </button>
        </>
      );
    }

    if (step === 1) {
      return (
        <>
          {/* Manual registration - Step 1: Personal Info */}
          <div className="input-group">
            <input
              type="text"
              name="firstName"
              value={formData.firstName}
              onChange={handleChange}
              required
            />
            <label>First Name</label>
          </div>
          <div className="input-group">
            <input
              type="text"
              name="lastName"
              value={formData.lastName}
              onChange={handleChange}
              required
            />
            <label>Last Name</label>
          </div>
          <div className="input-group">
            <input
              type="number"
              name="age"
              value={formData.age}
              onChange={handleChange}
              required
            />
            <label>Age</label>
          </div>
          <div className="input-group">
            <input
              type="number"
              step="0.1"
              name="height"
              value={formData.height}
              onChange={handleChange}
              required
            />
            <label>Height (cm)</label>
          </div>
          <div className="input-group">
            <input
              type="number"
              step="0.1"
              name="weight"
              value={formData.weight}
              onChange={handleChange}
              required
            />
            <label>Weight (kg)</label>
          </div>
          <div className="input-group">
            <select
              name="gender"
              value={formData.gender}
              onChange={handleChange}
              required
            >
                    <option value="" disabled>Select Gender</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
              <option value="other">Other</option>
            </select>
            <label>Gender</label>
          </div>
          <div className="input-group">
            <select
              name="fitnessLevel"
              value={formData.fitnessLevel}
              onChange={handleChange}
              required
            >
                    <option value="" disabled>Select Fitness Level</option>
              <option value="beginner">Beginner</option>
              <option value="intermediate">Intermediate</option>
              <option value="advanced">Advanced</option>
            </select>
            <label>Fitness Level</label>
          </div>
          <div className="input-group">
            <select
              name="activityLevel"
              value={formData.activityLevel}
              onChange={handleChange}
              required
            >
                    <option value="" disabled>Select Activity Level</option>
              <option value="sedentary">Sedentary (No exercise)</option>
              <option value="light">Light (1–3 days/week)</option>
              <option value="moderate">Moderate (3–5 days/week)</option>
              <option value="active">Active (6–7 days/week)</option>
              <option value="very-active">Very Active/Athlete</option>
            </select>
            <label>Activity Level</label>
          </div>
          <button type="button" onClick={handleNext}>Next</button>
        </>
      );
    }

    // Step 2: Manual registration email/password
    return (
      <>
        {/* Manual registration - Step 2: Email/Password */}
        <div className="input-group">
          <input
            type="email"
            name="email"
            value={formData.email}
            onChange={handleChange}
            required
            autoComplete="email"
          />
          <label>Email</label>
        </div>
        <div className="input-group">
          <input
            type="password"
            name="password"
            value={formData.password}
            onChange={handleChange}
            required
            autoComplete="new-password"
          />
          <label>Password</label>
        </div>
        <div className="input-group">
          <input
            type="password"
            name="confirmPassword"
            value={formData.confirmPassword}
            onChange={handleChange}
            required
            autoComplete="new-password"
          />
          <label>Confirm Password</label>
        </div>
        <button type="submit" disabled={loading}>
          {loading ? "Creating account…" : "Register"}
        </button>
      </>
    );
  };

  return (
    <div className="split-container">
      <div className="left-panel">
          <div className="overlay">
              <h2>
                Welcome to <span style={{display:'inline-block'}}><AppLogo appName={settings?.app_name || 'FitSync'} className="left-panel" /></span>
              </h2>
              <p>Customize your fitness journey today</p>
        </div>
      </div>

      <div className="right-panel">
        <div className="auth-card">
          <header>
            <h1>
              {isGoogleSignup ? "Complete your profile" : (step === 1 ? "Personal Info" : "Create Account")}<span className="dot">.</span>
            </h1>
            <p>
              {isGoogleSignup ? "Complete the fields below to finish creating your account." : (step === 1 ? "Step 1 of 2" : "Step 2 of 2 - Already have an account?")} <Link to="/login">Login</Link>
            </p>
          </header>

          <form className="auth-form" onSubmit={isGoogleSignup ? (e) => { e.preventDefault(); submitGoogleProfile(); } : (step === 2 ? handleRegister : (e) => e.preventDefault())}>
            {renderFormBody()}
            {error && <p className="auth-error">{error}</p>}
            {success && <p className="auth-success">{success}</p>}
          </form>
        </div>
      </div>
    </div>
  );
};

export default Register;