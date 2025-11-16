import React, { useState } from "react";
import axios from "axios";
import { Link, useNavigate } from "react-router-dom";
import "../styles/LoginRegister.css";

const API_BASE = "/api";

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
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const [loading, setLoading] = useState(false);

  const handleChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const handleNext = () => {
    if (step === 1 && (!formData.firstName || !formData.lastName || !formData.age || !formData.height || !formData.weight || !formData.gender || !formData.fitnessLevel)) {
      setError("Please fill all personal info fields.");
      return;
    }
    setError("");
    setStep(2);
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
              {step === 1 ? "Personal Info" : "Create Account"}<span className="dot">.</span>
            </h1>
            <p>
              {step === 1 ? "Step 1 of 2" : "Step 2 of 2 - Already have an account?"} <Link to="/login">Login</Link>
            </p>
          </header>

          <form className="auth-form" onSubmit={step === 2 ? handleRegister : (e) => e.preventDefault()}>
            {step === 1 ? (
              <>
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
                    <option value="" disabled hidden></option>
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
                    <option value="" disabled hidden></option>
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
                    <option value="" disabled hidden></option>
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
            ) : (
              <>
                {/* Email/Password */}
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
            )}
            {error && <p className="auth-error">{error}</p>}
            {success && <p className="auth-success">{success}</p>}
          </form>
        </div>
      </div>
    </div>
  );
};

export default Register;