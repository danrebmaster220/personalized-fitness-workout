// src/components/WorkoutModal.jsx
import React, { useEffect, useState } from "react";
import axios from "axios";
import "../styles/WorkoutModal.css";

const API_BASE = import.meta.env.VITE_API_BASE || "http://localhost/personalized-fitness-workout/backend/public";

export default function WorkoutModal({ open, onClose, user, onGenerated }) {
  const [form, setForm] = useState({
    age: user?.Age || "",
    gender: user?.Gender || "male",
    weight: user?.Weight || "",
    height: user?.Height || "",
    fitnessGoal: "build-muscle",
    targetMuscle: "full-body",
    workoutPlace: "home",
    workoutDays: 3,
    duration: 30,
    equipment: [],
    diet: "no-preference",
    bodyCondition: "none",
  });

  const [bmi, setBMI] = useState(null);
  const [bmr, setBMR] = useState(null);
  const [tdee, setTDEE] = useState(null);

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [resendLoading, setResendLoading] = useState(false);
  const [resendMessage, setResendMessage] = useState("");

  // Auto-calc metrics
  useEffect(() => {
    const w = parseFloat(form.weight);
    const h = parseFloat(form.height);
    const age = parseInt(form.age);
    if (!w || !h || !age) return;

    const heightM = h / 100;
    const calcBmi = (w / (heightM * heightM)).toFixed(2);
    setBMI(calcBmi);

    let calcBmr =
      form.gender === "male"
        ? 10 * w + 6.25 * h - 5 * age + 5
        : 10 * w + 6.25 * h - 5 * age - 161;

    calcBmr = Math.round(calcBmr);
    setBMR(calcBmr);
    setTDEE(Math.round(calcBmr * 1.55));
  }, [form.weight, form.height, form.age, form.gender]);

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;

    if (type === "checkbox" && name === "equipment") {
      setForm((prev) => ({
        ...prev,
        equipment: checked
          ? [...prev.equipment, value]
          : prev.equipment.filter((e) => e !== value),
      }));
    } else {
      setForm((prev) => ({ ...prev, [name]: value }));
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError("");
    setLoading(true);

    try {
      const payload = {
        userId: user.User_ID,
        goal: form.fitnessGoal,
        targetMuscle: form.targetMuscle,
        workoutPlace: form.workoutPlace,
        workoutDays: Number(form.workoutDays),
        duration: Number(form.duration),
        equipment: form.equipment.join(", "),
        equipment_array: form.equipment,
        bodyCondition: form.bodyCondition || "none",
        weight: form.weight,
        height: form.height,
        age: form.age,
        gender: form.gender,
        diet: form.diet,
      };

      const res = await axios.post(
        `${API_BASE}/?route=workout&action=generate`,
        payload
      );

      if (res.data?.success) {
        onGenerated(res.data);
        onClose();
      } else {
        setError(res.data.message || "Generation failed");
      }
    } catch (err) {
      setError("Server error");
      console.error(err);
    }
    setLoading(false);
  };

  const handleResendVerification = async () => {
    if (!user?.User_ID) return setResendMessage("No user available");
    setResendMessage("");
    setResendLoading(true);
    try {
      const resp = await axios.get(`${API_BASE}/index.php?route=user&action=resendVerification&userId=${user.User_ID}`);
      if (resp.data?.success) {
        setResendMessage('Verification email sent. Please check your inbox.');
      } else {
        setResendMessage(resp.data?.message || 'Failed to send verification email.');
      }
    } catch (err) {
      console.error(err);
      setResendMessage('Server error while sending verification email.');
    }
    setResendLoading(false);
  };

  if (!open) return null;

  return (
    <div className="wm-backdrop">
      <div className="wm-card animate-popup">
        <div className="wm-header">
          <h2>Generate Personalized Workout</h2>
          <button className="wm-close" onClick={onClose}>
            ✕
          </button>
        </div>
        {/* If the user is not verified, show an inline banner with option to resend verification */}
        {!user?.Is_Verified && (
          <div className="wm-unverified">
            <div className="wm-unverified-text">
              Your email is not verified. You can still generate a workout, but some features may be limited.
            </div>
            <div className="wm-unverified-actions">
              <button className="btn-secondary" type="button" onClick={handleResendVerification} disabled={resendLoading}>
                {resendLoading ? 'Sending...' : 'Resend verification email'}
              </button>
              {resendMessage && <div className="wm-resend-msg">{resendMessage}</div>}
            </div>
          </div>
        )}

        <form onSubmit={handleSubmit} className="wm-form">
          <h3 className="section-title">Personal Information</h3>

          <div className="wm-grid">
            <label className="wm-field">
              <span>Age</span>
              <input name="age" value={form.age} onChange={handleChange} />
            </label>

            <label className="wm-field">
              <span>Gender</span>
              <select name="gender" value={form.gender} onChange={handleChange}>
                <option>male</option>
                <option>female</option>
                <option>other</option>
              </select>
            </label>

            <label className="wm-field">
              <span>Weight (kg)</span>
              <input name="weight" value={form.weight} onChange={handleChange} />
            </label>

            <label className="wm-field">
              <span>Height (cm)</span>
              <input name="height" value={form.height} onChange={handleChange} />
            </label>
          </div>

          {/* AUTO CALCULATED STATS */}
          <div className="metric-box">
            <div className="metric">
              <strong>BMI:</strong> {bmi || "--"}
            </div>
            <div className="metric">
              <strong>BMR:</strong> {bmr ? `${bmr} kcal` : "--"}
            </div>
            <div className="metric">
              <strong>TDEE:</strong> {tdee ? `${tdee} kcal` : "--"}
            </div>
          </div>

          <h3 className="section-title">Workout Settings</h3>

          <div className="wm-grid">
            <label className="wm-field">
              <span>Fitness Goal</span>
              <select
                name="fitnessGoal"
                value={form.fitnessGoal}
                onChange={handleChange}
              >
                <option value="build-muscle">Build Muscle</option>
                <option value="lose-weight">Lose Weight</option>
                <option value="maintain-weight">Maintain Weight</option>
                <option value="improve-endurance">Improve Endurance</option>
              </select>
            </label>

            <label className="wm-field">
              <span>Target Muscle</span>
              <select name="targetMuscle" value={form.targetMuscle} onChange={handleChange}>
                <option value="full-body">Full Body</option>
                <option value="upper-body">Upper Body</option>
                <option value="lower-body">Lower Body</option>
                <option value="chest">Chest</option>
                <option value="back">Back</option>
                <option value="shoulders">Shoulders</option>
                <option value="biceps">Biceps</option>
                <option value="triceps">Triceps</option>
                <option value="quadriceps">Quadriceps</option>
                <option value="hamstrings">Hamstrings</option>
                <option value="glutes">Glutes</option>
                <option value="calves">Calves</option>
                <option value="core">Core / Abs</option>
                <option value="cardio">Cardio / Endurance</option>
                <option value="mobility">Mobility / Flexibility</option>
              </select>
            </label>

            <label className="wm-field">
              <span>Workout Location</span>
              <select
                name="workoutPlace"
                value={form.workoutPlace}
                onChange={handleChange}
              >
                <option value="home">Home</option>
                <option value="gym">Gym</option>
              </select>
            </label>

            <label className="wm-field">
              <span>Workout Days (per week)</span>
              <input
                name="workoutDays"
                value={form.workoutDays}
                onChange={handleChange}
              />
            </label>

            <label className="wm-field">
              <span>Minutes per Session</span>
              <input
                name="duration"
                value={form.duration}
                placeholder="e.g. 30 (minutes per session)"
                onChange={handleChange}
              />
              <div className="wm-help">Minutes per session = the average minutes you plan to exercise each workout (e.g., if you train 3 days/week and set 30, you'll do ~3×30 minutes/week).</div>
            </label>
          </div>

          <h3 className="section-title">Available Equipment</h3>
          <div className="wm-help" style={{ marginBottom: 8 }}>
            Check what equipment you actually have available — this helps the generator tailor exercises to your gear.
          </div>
          <div className="equipment-grid">
            {["Dumbbells", "Barbell", "Resistance Bands", "Kettlebell", "Bench", "Pull-up Bar", "Cable Machine", "Stationary Bike", "Treadmill", "None"].map(
              (eq) => (
                <label key={eq} className="equip-item">
                  <input
                    type="checkbox"
                    name="equipment"
                    value={eq}
                    checked={form.equipment.includes(eq)}
                    onChange={handleChange}
                  />
                  {eq}
                </label>
              )
            )}
          </div>

          <h3 className="section-title">Diet Preference / Meal Focus</h3>

          <label className="wm-field">
            <select name="diet" value={form.diet} onChange={handleChange}>
              <option value="no-preference">No Preference</option>
              <option value="high-protein">High Protein</option>
              <option value="low-carb">Low Carb</option>
              <option value="vegan">Vegan</option>
              <option value="keto">Keto</option>
              <option value="mediterranean">Mediterranean</option>
              <option value="pescatarian">Pescatarian</option>
              <option value="vegetarian">Vegetarian</option>
              <option value="intermittent-fasting">Intermittent Fasting</option>
              <option value="gluten-free">Gluten Free</option>
            </select>
          </label>

          <h3 className="section-title">Health / Body Condition</h3>
          <label className="wm-field">
            <select name="bodyCondition" value={form.bodyCondition} onChange={handleChange}>
              <option value="none">None</option>
              <option value="asthma">Asthma</option>
              <option value="knee-injury">Knee injury / joint issues</option>
              <option value="back-pain">Back pain</option>
              <option value="high-blood-pressure">High blood pressure</option>
              <option value="diabetes">Diabetes</option>
              <option value="pregnancy">Pregnancy</option>
              <option value="other">Other / prefer to specify in notes</option>
            </select>
          </label>

          {error && <div className="wm-error">{error}</div>}

          <div className="wm-actions">
            <button type="button" onClick={onClose}>
              Cancel
            </button>
            <button type="submit" className="btn-primary">
              {loading ? "Generating..." : "Generate Workout"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
