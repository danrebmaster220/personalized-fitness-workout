// src/components/WorkoutModal.jsx
import React, { useEffect, useState } from "react";
import axios from "axios";
import "../styles/WorkoutModal.css";

const API_BASE = "/api";

export default function WorkoutModal({ open, onClose, user, onGenerated }) {
  const [form, setForm] = useState({
    age: user?.Age || "",
    gender: user?.Gender || "male",
    weight: user?.Weight || "",
    height: user?.Height || "",
    fitnessGoal: "build-muscle",
    targetMuscle: "",
    workoutPlace: "home",
    workoutDays: 3,
    duration: 30,
    equipment: [],
    diet: "no-preference",
  });

  const [bmi, setBMI] = useState(null);
  const [bmr, setBMR] = useState(null);
  const [tdee, setTDEE] = useState(null);

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

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
              <input
                name="targetMuscle"
                value={form.targetMuscle}
                onChange={handleChange}
              />
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
                onChange={handleChange}
              />
            </label>
          </div>

          <h3 className="section-title">Available Equipment</h3>

          <div className="equipment-grid">
            {["Dumbbells", "Barbell", "Resistance Bands", "Kettlebell", "Bench"].map(
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

          <h3 className="section-title">Diet Preference</h3>

          <label className="wm-field">
            <select name="diet" value={form.diet} onChange={handleChange}>
              <option value="no-preference">No Preference</option>
              <option value="high-protein">High Protein</option>
              <option value="low-carb">Low Carb</option>
              <option value="vegan">Vegan</option>
              <option value="keto">Keto</option>
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
