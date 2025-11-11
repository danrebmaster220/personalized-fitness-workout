// inside src/components/
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
    fitnessLevel: user?.Fitness_Level || "beginner",
    fitnessGoal: "build-muscle",
    targetMuscle: "",
    workoutPlace: "home",
    workoutDays: 3,
    duration: 30,
    equipment: [],
    activityLevel: "moderate",
    diet: "no-preference",
    mealsPerDay: 3,
    calorieTarget: ""
  });

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  useEffect(() => {
    if (user) {
      setForm(f => ({
        ...f,
        age: user.Age,
        gender: user.Gender,
        weight: user.Weight,
        height: user.Height,
        fitnessLevel: user.Fitness_Level
      }));
    }
  }, [user]);

  const handleChange = e => {
    const { name, value, type, checked } = e.target;
    if (type === "checkbox" && name === "equipment") {
      setForm(prev => ({
        ...prev,
        equipment: checked
          ? [...prev.equipment, value]
          : prev.equipment.filter(e => e !== value),
      }));
    } else {
      setForm(prev => ({ ...prev, [name]: value }));
    }
  };

  const handleSubmit = async e => {
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
        condition: "",
        allergies: "",
        weight: form.weight,
        height: form.height,
        age: form.age,
        gender: form.gender,
        diet: form.diet
      };

      const res = await axios.post(`${API_BASE}/?route=workout&action=generate`, payload);

      if (res.data?.success) {
        onGenerated(res.data.result);
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
      <div className="wm-card">
        <div className="wm-header">
          <h3>Generate Personalized Plan</h3>
          <button className="wm-close" onClick={onClose}>✕</button>
        </div>

        <form onSubmit={handleSubmit} className="wm-form">
          <div className="wm-grid">

            <label className="wm-field"><span>Age</span>
              <input name="age" value={form.age} onChange={handleChange}/>
            </label>

            <label className="wm-field"><span>Gender</span>
              <select name="gender" value={form.gender} onChange={handleChange}>
                <option>male</option>
                <option>female</option>
                <option>other</option>
              </select>
            </label>

            <label className="wm-field"><span>Weight (kg)</span>
              <input name="weight" value={form.weight} onChange={handleChange}/>
            </label>

            <label className="wm-field"><span>Height (cm)</span>
              <input name="height" value={form.height} onChange={handleChange}/>
            </label>

            <label className="wm-field"><span>Fitness Goal</span>
              <select name="fitnessGoal" onChange={handleChange}>
                <option>build-muscle</option>
                <option>lose-weight</option>
                <option>maintain-weight</option>
                <option>improve-endurance</option>
              </select>
            </label>

            <label className="wm-field"><span>Target Muscle</span>
              <input name="targetMuscle" onChange={handleChange}/>
            </label>

            <label className="wm-field"><span>Workout Days</span>
              <input name="workoutDays" onChange={handleChange}/>
            </label>

            <label className="wm-field"><span>Minutes</span>
              <input name="duration" onChange={handleChange}/>
            </label>

          </div>

          {error && <div className="wm-error">{error}</div>}

          <div className="wm-actions">
            <button type="button" onClick={onClose}>Cancel</button>
            <button type="submit">{loading ? "Generating..." : "Generate Workout"}</button>
          </div>
        </form>
      </div>
    </div>
  );
}
