import React, { useState } from "react";
import axios from "axios";
import "../styles/PrimarySection.css";

const API_BASE = "http://localhost/personalized-fitness-workout/backend/public";

const PrimarySection = () => {
  const [formData, setFormData] = useState({
    age: "",
    gender: "",
    weight: "",
    height: "",
    fitnessGoal: "",
    fitnessLevel: "",
    bodyCondition: "",
    foodPreference: "",
  });

  const [loading, setLoading] = useState(false);
  const [generatedWorkout, setGeneratedWorkout] = useState(null);
  const [error, setError] = useState("");

  // handle form field change
  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData({ ...formData, [name]: value });
  };

  // handle submit
  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError("");
    setGeneratedWorkout(null);

    try {
      const response = await axios.post(`${API_BASE}/generateWorkout.php`, formData);
      const data = response.data;

      if (data.success) {
        setGeneratedWorkout(data.workout);
      } else {
        setError(data.message || "Failed to generate workout. Try again later.");
      }
    } catch (err) {
      setError("Server error. Please try again later.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <section className="primary-section">
      <h2>Generate Your Personalized Workout Plan</h2>
      <p>Fill out the form below to receive a workout routine made just for you.</p>

      <form className="generate-form" onSubmit={handleSubmit}>
        <div className="form-row">
          <input type="number" name="age" placeholder="Age" value={formData.age} onChange={handleChange} required />
          <select name="gender" value={formData.gender} onChange={handleChange} required>
            <option value="">Gender</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
          </select>
        </div>

        <div className="form-row">
          <input type="number" name="weight" placeholder="Weight (kg)" value={formData.weight} onChange={handleChange} required />
          <input type="number" name="height" placeholder="Height (cm)" value={formData.height} onChange={handleChange} required />
        </div>

        <div className="form-row">
          <select name="fitnessGoal" value={formData.fitnessGoal} onChange={handleChange} required>
            <option value="">Fitness Goal</option>
            <option value="Lose Weight">Lose Weight</option>
            <option value="Build Muscle">Build Muscle</option>
            <option value="Maintain">Maintain</option>
          </select>

          <select name="fitnessLevel" value={formData.fitnessLevel} onChange={handleChange} required>
            <option value="">Fitness Level</option>
            <option value="Beginner">Beginner</option>
            <option value="Intermediate">Intermediate</option>
            <option value="Advanced">Advanced</option>
          </select>
        </div>

        <textarea
          name="bodyCondition"
          placeholder="Describe your body condition (e.g., back pain, knee injury, etc.)"
          value={formData.bodyCondition}
          onChange={handleChange}
          required
        ></textarea>

        <textarea
          name="foodPreference"
          placeholder="Preferred foods or diet (e.g., high protein, low carb, vegetarian)"
          value={formData.foodPreference}
          onChange={handleChange}
          required
        ></textarea>

        <button type="submit" disabled={loading}>
          {loading ? "Generating..." : "Generate Workout"}
        </button>
      </form>

      {/* Results Section */}
      {error && <p className="error-text">{error}</p>}

      {generatedWorkout && (
        <div className="generated-workout">
          <h3>Your Personalized Workout Plan</h3>
          <pre>{JSON.stringify(generatedWorkout, null, 2)}</pre>
          <button
            className="download-btn"
            onClick={() => alert("Download feature coming soon!")}
          >
            Download as PDF
          </button>
        </div>
      )}
    </section>
  );
};

export default PrimarySection;
