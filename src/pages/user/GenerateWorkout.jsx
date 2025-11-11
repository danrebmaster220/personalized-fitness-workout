// inside src/pages/user/
import React, { useState } from "react";
import WorkoutModal from "../../components/WorkoutModal"; // adjust path if your component folder differs
import axios from "axios";
import "../../styles/GenerateWorkout.css";
import "../../styles/WorkoutResult.css";

const API_BASE = "/api";

export default function GenerateWorkout() {
  const user = JSON.parse(localStorage.getItem("user")) || null;
  const [modalOpen, setModalOpen] = useState(false);
  const [result, setResult] = useState(null);
  const [resultModalOpen, setResultModalOpen] = useState(false);
  const [downloading, setDownloading] = useState(false);

  const handleGenerated = (res) => {
    // Accept multiple possible shapes from backend: res.result or res directly
    const payload = res?.result ?? res;
    // Ensure we keep an id if provided by backend
    const normalized = { ...payload, id: payload.id ?? res?.id ?? payload.Generate_ID ?? null, createdAt: payload.createdAt ?? payload.created_at ?? new Date().toISOString() };
    setResult(normalized);
    setResultModalOpen(true);
  };

  const downloadPDF = async () => {
    if (!result?.id) return alert("No saved result to download.");
    setDownloading(true);
    try {
      const resp = await axios.get(`${API_BASE}/index.php?route=user&action=downloadWorkout&id=${result.id}`);
      if (resp.data?.success && resp.data.url) {
        window.open(resp.data.url, "_blank");
      } else {
        alert(resp.data?.message || "PDF not ready.");
      }
    } catch (err) {
      console.error(err);
      alert("Download failed.");
    } finally {
      setDownloading(false);
    }
  };

  return (
    <div className="generate-container">
      <div className="generate-header">
        <h1>Generate Workout</h1>
        <button className="btn-primary" onClick={() => setModalOpen(true)}>+ Generate Workout</button>
      </div>

      <WorkoutModal open={modalOpen} onClose={() => setModalOpen(false)} user={user} onGenerated={handleGenerated} />

      {/* Fullscreen result modal (read-only, modern card layout) */}
      {resultModalOpen && result && (
        <div className="result-fullback">
          <div className="result-fullcard">
            <div className="result-fullhead">
              <div>
                <img src="/logo192.png" alt="logo" className="result-logo" /> {/* replace with your logo path */}
                <div className="result-title">{result.title || "Personalized Plan"}</div>
                <div className="result-sub">Generated: {new Date(result.createdAt).toLocaleString()}</div>
              </div>
              <button className="result-close" onClick={() => setResultModalOpen(false)}>✕</button>
            </div>

            <div className="result-body">
              {/* Cards row */}
              <div className="result-cards">
                {/* Body Stats Card */}
                <div className="card">
                  <div className="card-title">🧍 Body Stats</div>
                  <div className="card-content">
                    <div><strong>BMI:</strong> {result.bmi ?? "-"}</div>
                    <div><strong>BMR:</strong> {result.bmr ? `${result.bmr} kcal` : "-"}</div>
                    <div><strong>TDEE:</strong> {result.tdee ? `${result.tdee} kcal` : "-"}</div>
                    {result.bodyCondition && <p className="muted">{result.bodyCondition}</p>}
                  </div>
                </div>

                {/* Workout Plan Card */}
                <div className="card">
                  <div className="card-title">🏋️ Workout Plan</div>
                  <div className="card-content">
                    {Array.isArray(result.workoutPlan) && result.workoutPlan.length ? (
                      result.workoutPlan.map((item, idx) => (
                        <div key={idx} className="exercise-block">
                          <div className="exercise-name">{item.name ?? item.day ?? `Block ${idx+1}`}</div>
                          {item.exercises?.map((ex, i) => (
                            <div key={i} className="exercise-line">
                              <div>{ex.name}</div>
                              <div className="exercise-meta">{ex.sets ? `${ex.sets} sets` : ""} {ex.reps ? `× ${ex.reps}` : ""}</div>
                            </div>
                          ))}
                          {item.notes && <div className="muted small">{item.notes}</div>}
                        </div>
                      ))
                    ) : (
                      <div className="muted">No workout plan available</div>
                    )}
                  </div>
                </div>

                {/* Meal Plan Card */}
                <div className="card">
                  <div className="card-title">🍽 Meal Plan</div>
                  <div className="card-content">
                    {Array.isArray(result.mealPlan) && result.mealPlan.length ? (
                      result.mealPlan.map((m, i) => (
                        <div key={i} className="meal-line">
                          <div className="meal-name">{m.meal ?? `Meal ${i+1}`}</div>
                          <div className="muted">{m.description ?? m}</div>
                        </div>
                      ))
                    ) : <div className="muted">No meal suggestions</div>}
                  </div>
                </div>
              </div>

              {/* Tips / Notes */}
              {result.tips && result.tips.length > 0 && (
                <div className="notes">
                  <h4>Tips</h4>
                  <ul>
                    {result.tips.map((t, i) => <li key={i}>{t}</li>)}
                  </ul>
                </div>
              )}
            </div>

            <div className="result-actions">
              <div className="result-userinfo">
                <div><strong>{user?.FirstName} {user?.LastName}</strong></div>
                <div className="muted">Email: {user?.Email}</div>
              </div>

              <div className="result-buttons">
                <button className="btn-primary" onClick={downloadPDF} disabled={downloading}>
                  {downloading ? "Preparing PDF..." : "📄 Download PDF"}
                </button>
                <button className="btn-secondary" onClick={() => {
                  document.getElementById("workout-history")?.scrollIntoView({ behavior: "smooth" });
                  setResultModalOpen(false);
                }}>
                  View History
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
