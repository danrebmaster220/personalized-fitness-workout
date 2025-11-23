// inside src/pages/user/
import React, { useState } from "react";
import { useNavigate } from "react-router-dom";
import WorkoutModal from "../../components/WorkoutModal"; // adjust path if your component folder differs
import axios from "axios";
import "../../styles/GenerateWorkout.css";
import "../../styles/WorkoutResult.css";

const API_BASE = import.meta.env.VITE_API_BASE || "http://localhost/personalized-fitness-workout/backend/public";

export default function GenerateWorkout() {
  const user = JSON.parse(localStorage.getItem("user")) || null;
  const navigate = useNavigate();
  const [modalOpen, setModalOpen] = useState(false);
  const [result, setResult] = useState(null);
  const [resultModalOpen, setResultModalOpen] = useState(false);

  const handleGenerated = (res) => {
    // Accept multiple possible shapes from backend: res.result or res directly
    const payload = res?.result ?? res;

    // Normalize common AI shapes into the UI-friendly shape used below.
    const normalized = { ...payload };

    // Map workout -> workoutPlan (support several common keys)
    if (!normalized.workoutPlan) {
      if (normalized.workout?.weeklyPlan) normalized.workoutPlan = normalized.workout.weeklyPlan;
      else if (normalized.workout?.workoutPlan) normalized.workoutPlan = normalized.workout.workoutPlan;
      else if (normalized.workout?.plan) normalized.workoutPlan = normalized.workout.plan;
      else if (Array.isArray(normalized.workout)) normalized.workoutPlan = normalized.workout;
      else if (normalized.Workout_Result) {
        try {
          const parsed = typeof normalized.Workout_Result === 'string' 
            ? JSON.parse(normalized.Workout_Result) 
            : normalized.Workout_Result;
          normalized.workoutPlan = parsed.weeklyPlan || parsed;
        } catch {
          normalized.workoutPlan = [];
        }
      }
    }

    // Map meal -> mealPlan
    if (!normalized.mealPlan) {
      if (normalized.meal?.meals) normalized.mealPlan = normalized.meal.meals;
      else if (Array.isArray(normalized.meal)) normalized.mealPlan = normalized.meal;
      else if (normalized.Meal_Result) {
        try {
          const parsed = typeof normalized.Meal_Result === 'string' 
            ? JSON.parse(normalized.Meal_Result) 
            : normalized.Meal_Result;
          normalized.mealPlan = parsed.meals || parsed;
        } catch {
          normalized.mealPlan = [];
        }
      }
    }

    // Map bodyCondition - handle object structure
    if (!normalized.bodyConditionText) {
      const bc = normalized.bodyCondition ?? normalized.Body_Condition_Result ?? normalized.body ?? null;
      if (bc) {
        if (typeof bc === 'string') {
          try {
            const parsed = JSON.parse(bc);
            normalized.bodyConditionText = parsed.assessment || parsed.category || JSON.stringify(parsed);
            normalized.bodyCondition = parsed;
          } catch {
            normalized.bodyConditionText = bc;
          }
        } else if (typeof bc === 'object') {
          normalized.bodyConditionText = bc.assessment || bc.category || '';
          normalized.bodyCondition = bc;
        }
      }
    }

    // Ensure we keep an id if provided by backend
    normalized.id = normalized.id ?? payload.id ?? res?.id ?? payload.Generate_ID ?? null;
    normalized.createdAt = normalized.createdAt ?? payload.createdAt ?? payload.created_at ?? new Date().toISOString();

    setResult(normalized);
    setResultModalOpen(true);
  };

  const downloadPDF = async () => {
    if (!result?.id) return alert("No saved result to download.");
    // Open download URL directly in new window
    window.open(`${API_BASE}/index.php?route=user&action=downloadWorkout&id=${result.id}`, '_blank');
  };

  const handleOpenModal = async () => {
    const latestUser = JSON.parse(localStorage.getItem('user')) || user;
    if (!latestUser) {
      // Not logged in — redirect to login
      return navigate('/login');
    }

    // Open the modal for all logged-in users, verified or not.
    setModalOpen(true);
  };

  return (
    <div className="admin-dashboard">
      <h2>Generate Workout</h2>
      <p className="subtitle">Create a personalized workout plan tailored to your goals</p>

      {/* Stats Grid - Info Cards */}
      <div className="stats-grid">
        {/* What You'll Get Card */}
        <div className="stat-card users">
          <div style={{fontSize: '24px', marginBottom: '8px'}}>🎯</div>
          <h3 style={{fontSize: '15px', fontWeight: '600', marginBottom: '10px'}}>What You'll Get</h3>
          <p style={{fontSize: '13px', lineHeight: '1.6', margin: 0}}>
            Personalized workout plan, custom meal suggestions, body assessment & PDF report
          </p>
        </div>

        {/* How It Works Card */}
        <div className="stat-card workouts">
          <div style={{fontSize: '24px', marginBottom: '8px'}}>⚡</div>
          <h3 style={{fontSize: '15px', fontWeight: '600', marginBottom: '10px'}}>How It Works</h3>
          <p style={{fontSize: '13px', lineHeight: '1.6', margin: 0}}>
            Enter details → AI generates plan (30s) → Review results → Access anytime
          </p>
        </div>

        {/* Pro Tips Card */}
        <div className="stat-card verified" style={{background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', color: 'white'}}>
          <div style={{fontSize: '24px', marginBottom: '8px'}}>💡</div>
          <h3 style={{fontSize: '15px', fontWeight: '600', marginBottom: '10px'}}>Pro Tips</h3>
          <p style={{fontSize: '13px', lineHeight: '1.6', margin: 0}}>
            Be specific about goals, mention health conditions & list available equipment
          </p>
        </div>

        {/* Quick Action Card */}
        <div className="stat-card logs">
          <div style={{fontSize: '24px', marginBottom: '8px'}}>✨</div>
          <h3 style={{fontSize: '15px', fontWeight: '600', marginBottom: '10px'}}>AI-Powered</h3>
          <p style={{fontSize: '13px', lineHeight: '1.6', margin: 0}}>
            Personalized plans generated using advanced AI technology
          </p>
        </div>
      </div>

      {/* Generate Button Section */}
      <div className="recent-section">
        <h4>Ready to Create Your Personalized Plan?</h4>
        <div style={{
          background: 'white',
          borderRadius: '8px',
          padding: '32px',
          textAlign: 'center',
          boxShadow: '0 1px 3px rgba(0,0,0,0.1)'
        }}>
          <p style={{color: '#6b7280', marginBottom: '20px', fontSize: '14px'}}>
            Click below to start generating your AI-powered workout and meal plan
          </p>
          <button 
            className="btn-primary" 
            onClick={handleOpenModal}
            style={{
              padding: '14px 32px',
              fontSize: '16px',
              fontWeight: 'bold',
              background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
              border: 'none',
              borderRadius: '8px',
              color: 'white',
              cursor: 'pointer',
              boxShadow: '0 4px 12px rgba(102, 126, 234, 0.4)',
              transition: 'transform 0.2s, box-shadow 0.2s'
            }}
            onMouseEnter={(e) => {
              e.target.style.transform = 'translateY(-2px)';
              e.target.style.boxShadow = '0 6px 16px rgba(102, 126, 234, 0.5)';
            }}
            onMouseLeave={(e) => {
              e.target.style.transform = 'translateY(0)';
              e.target.style.boxShadow = '0 4px 12px rgba(102, 126, 234, 0.4)';
            }}
          >
            ✨ Generate Workout Plan
          </button>
        </div>
      </div>

      <WorkoutModal open={modalOpen} onClose={() => setModalOpen(false)} user={user} onGenerated={handleGenerated} />

      {/* Fullscreen result modal (read-only, modern card layout) */}
      {resultModalOpen && result && (
        <div className="result-fullback">
          <div className="result-fullcard">
            <div className="result-fullhead">
              <div>
                <div style={{
                  fontSize: '28px', 
                  fontWeight: 'bold', 
                  marginBottom: '4px'
                }}>
                  FitSync
                </div>
                <div className="result-title">{result.title || "Personalized Plan"}</div>
                <div className="result-sub">Generated: {new Date(result.createdAt).toLocaleString()}</div>
              </div>
              <button className="result-close" onClick={() => setResultModalOpen(false)}>✕</button>
            </div>

            <div className="result-body">
              {/* Health Summary Banner - NEW! */}
              {result.bodyCondition && (
                <div style={{
                  background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                  color: 'white',
                  padding: '16px 20px',
                  borderRadius: '10px',
                  marginBottom: '16px',
                  boxShadow: '0 4px 6px rgba(0,0,0,0.1)'
                }}>
                  <div style={{fontSize: '18px', fontWeight: 'bold', marginBottom: '8px'}}>
                    📊 Your Health Assessment
                  </div>
                  <div style={{display: 'flex', gap: '20px', flexWrap: 'wrap', alignItems: 'flex-start'}}>
                    <div>
                      <div style={{fontSize: '13px', opacity: 0.9}}>BMI</div>
                      <div style={{fontSize: '24px', fontWeight: 'bold'}}>{result.bodyCondition.bmi ?? result.bmi ?? '-'}</div>
                    </div>
                    <div>
                      <div style={{fontSize: '13px', opacity: 0.9}}>Category</div>
                      <div style={{fontSize: '20px', fontWeight: 'bold'}}>
                        {result.bodyCondition.category ?? 'Normal'}
                      </div>
                    </div>
                    <div style={{flex: 1, minWidth: '250px'}}>
                      <div style={{fontSize: '13px', opacity: 0.9, marginBottom: '4px'}}>Assessment</div>
                      <div style={{fontSize: '14px', lineHeight: '1.5'}}>
                        {result.bodyConditionText ?? result.bodyCondition.assessment ?? 'Your body metrics are within healthy ranges.'}
                      </div>
                    </div>
                  </div>
                </div>
              )}

              {/* Cards row */}
              <div className="result-cards">
                {/* Body Stats Card */}
                <div className="card">
                  <div className="card-title">🧍 Body Metrics</div>
                  <div className="card-content">
                    <div><strong>BMI:</strong> {result.bodyCondition?.bmi ?? result.bmi ?? "-"}</div>
                    <div><strong>BMR:</strong> {result.bmr ? `${result.bmr} kcal/day` : "-"}</div>
                    <div><strong>TDEE:</strong> {result.tdee ? `${result.tdee} kcal/day` : "-"}</div>
                    {result.bodyCondition?.recommendations && Array.isArray(result.bodyCondition.recommendations) && result.bodyCondition.recommendations.length > 0 && (
                      <div style={{marginTop: '12px', paddingTop: '12px', borderTop: '1px solid #e5e7eb'}}>
                        <strong style={{color: '#667eea'}}>💡 Key Tips:</strong>
                        <ul style={{marginTop: '6px', marginBottom: 0, paddingLeft: '20px', fontSize: '13px', lineHeight: '1.6'}}>
                          {result.bodyCondition.recommendations.slice(0, 3).map((rec, i) => <li key={i}>{rec}</li>)}
                        </ul>
                      </div>
                    )}
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
                          <div className="meal-name">
                            {m.meal ?? `Meal ${i+1}`}
                            {m.calories && <span className="meal-calories"> ({m.calories} cal)</span>}
                          </div>
                          {m.description && <div className="muted">{m.description}</div>}
                          {m.foods && Array.isArray(m.foods) && (
                            <div className="muted small">
                              {m.foods.map((food, j) => (
                                <span key={j}>
                                  {typeof food === 'string' ? food : food.name || food.item || ''}
                                  {j < m.foods.length - 1 ? ', ' : ''}
                                </span>
                              ))}
                            </div>
                          )}
                          {m.items && Array.isArray(m.items) && (
                            <div className="muted small">
                              {m.items.join(', ')}
                            </div>
                          )}
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
                <button className="btn-primary" onClick={downloadPDF}>
                  📄 Download PDF
                </button>
                <button className="btn-secondary" onClick={() => {
                  setResultModalOpen(false);
                  navigate('/workout-history');
                }}>
                  📋 View History
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
