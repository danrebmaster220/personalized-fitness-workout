import React from "react";
import "../styles/GenerateWorkout.css"; // reuse the beautiful white modal styling

const API_BASE = import.meta.env.VITE_API_BASE || "http://localhost/personalized-fitness-workout/backend/public";

export default function GeneratedWorkoutView({ workout, onClose }) {

  // Parse JSON strings from database
  const parseJSON = (data) => {
    if (!data) return null;
    if (typeof data === 'string') {
      try {
        return JSON.parse(data);
      } catch {
        return null;
      }
    }
    return data;
  };

  const workoutData = parseJSON(workout.Workout_Result);
  const mealData = parseJSON(workout.Meal_Result);
  const bodyConditionData = parseJSON(workout.Body_Condition_Result);

  const workoutPlan = workoutData?.weeklyPlan || workoutData?.plan || [];
  const mealPlan = mealData?.meals || [];
  const bodyCondition = bodyConditionData || {};

  const downloadPDF = () => {
    // Open download URL directly - simpler and more reliable
    window.open(`${API_BASE}/index.php?route=user&action=downloadWorkout&id=${workout.Generate_ID}`, '_blank');
  };

  return (
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
            <div className="result-title">Workout #{workout.Generate_ID}</div>
            <div className="result-sub">Generated: {new Date(workout.Created_At).toLocaleString()}</div>
          </div>
          <button className="result-close" onClick={onClose}>✕</button>
        </div>

        <div className="result-body">
          {/* Health Summary Banner - NEW! */}
          {bodyCondition && (
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
                  <div style={{fontSize: '24px', fontWeight: 'bold'}}>{bodyCondition.bmi ?? workout.BMI ?? '-'}</div>
                </div>
                <div>
                  <div style={{fontSize: '13px', opacity: 0.9}}>BMR</div>
                  <div style={{fontSize: '20px', fontWeight: 'bold'}}>{workout.BMR ?? '-'} <span style={{fontSize: '14px', opacity: 0.8}}>kcal</span></div>
                </div>
                <div>
                  <div style={{fontSize: '13px', opacity: 0.9}}>TDEE</div>
                  <div style={{fontSize: '20px', fontWeight: 'bold'}}>{workout.TDEE ?? '-'} <span style={{fontSize: '14px', opacity: 0.8}}>kcal</span></div>
                </div>
                <div>
                  <div style={{fontSize: '13px', opacity: 0.9}}>Category</div>
                  <div style={{fontSize: '18px', fontWeight: 'bold'}}>
                    {bodyCondition.category ?? 'Normal'}
                  </div>
                </div>
                <div style={{flex: 1, minWidth: '250px'}}>
                  <div style={{fontSize: '13px', opacity: 0.9, marginBottom: '4px'}}>Assessment</div>
                  <div style={{fontSize: '14px', lineHeight: '1.5'}}>
                    {bodyCondition.assessment ?? 'Your body metrics are within healthy ranges.'}
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* Cards row */}
          <div className="result-cards">
            {/* Body Stats Card */}
            <div className="card">
              <div className="card-title">🧍 Body Stats</div>
              <div className="card-content">
                <div><strong>Goal:</strong> {workout.Goal}</div>
                <div><strong>Target Muscle:</strong> {workout.Target_Muscle}</div>
                <div><strong>Location:</strong> {workout.Workout_Place}</div>
                <div style={{marginTop: '8px', paddingTop: '8px', borderTop: '1px solid #e5e7eb'}}>
                  <div><strong>BMI:</strong> {bodyCondition?.bmi ?? workout.BMI ?? "-"}</div>
                  <div><strong>BMR:</strong> {workout.BMR ? `${workout.BMR} kcal` : "-"}</div>
                  <div><strong>TDEE:</strong> {workout.TDEE ? `${workout.TDEE} kcal` : "-"}</div>
                </div>
                {bodyCondition?.category && <div style={{marginTop: '6px'}}><strong>Category:</strong> {bodyCondition.category}</div>}
                {bodyCondition?.assessment && <p className="muted" style={{marginTop: '8px'}}>{bodyCondition.assessment}</p>}
              </div>
            </div>

            {/* Workout Plan Card */}
            <div className="card">
              <div className="card-title">🏋️ Workout Plan</div>
              <div className="card-content">
                {Array.isArray(workoutPlan) && workoutPlan.length ? (
                  workoutPlan.map((item, idx) => (
                    <div key={idx} className="exercise-block">
                      <div className="exercise-name">{item.name ?? item.day ?? `Day ${idx+1}`}</div>
                      {item.focus && <div className="muted small">Focus: {item.focus}</div>}
                      {item.exercises?.map((ex, i) => (
                        <div key={i} className="exercise-line">
                          <div>{ex.name}</div>
                          <div className="exercise-meta">
                            {ex.sets ? `${ex.sets} sets` : ""} {ex.reps ? `× ${ex.reps}` : ""}
                            {ex.duration ? ` ${ex.duration}` : ""}
                          </div>
                        </div>
                      ))}
                      {item.notes && <div className="muted small" style={{marginTop: '4px'}}>{item.notes}</div>}
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
                {mealData?.dailyCalories && (
                  <div style={{marginBottom: '10px', padding: '8px', background: '#f0fdf4', borderRadius: '6px'}}>
                    <strong>Daily Target:</strong> {mealData.dailyCalories} kcal
                  </div>
                )}
                {Array.isArray(mealPlan) && mealPlan.length ? (
                  mealPlan.map((m, i) => (
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
                        <div className="muted small">{m.items.join(', ')}</div>
                      )}
                    </div>
                  ))
                ) : <div className="muted">No meal suggestions</div>}
              </div>
            </div>
          </div>

          {/* Recommendations */}
          {bodyCondition?.recommendations && Array.isArray(bodyCondition.recommendations) && bodyCondition.recommendations.length > 0 && (
            <div className="card" style={{marginTop: '14px'}}>
              <div className="card-title">💡 Recommendations</div>
              <div className="card-content">
                <ul style={{marginLeft: '20px', lineHeight: '1.6'}}>
                  {bodyCondition.recommendations.map((rec, i) => <li key={i}>{rec}</li>)}
                </ul>
              </div>
            </div>
          )}
        </div>

        <div className="result-actions">
          <div className="result-userinfo">
            <strong>Plan Settings:</strong> {workout.Workout_Days} days/week · {workout.Duration_Min} min/session
          </div>
          <div className="result-buttons">
            <button className="btn-secondary" onClick={onClose}>Close</button>
            <button 
              className="btn-primary" 
              onClick={downloadPDF}
            >
              📄 Download PDF
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
