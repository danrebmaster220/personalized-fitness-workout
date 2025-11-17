import React from "react";
import "../styles/admin/GeneratedWorkoutView.css";

export default function GeneratedWorkoutView({ workout, onClose }) {
  // pretty JSON helper
  const pretty = (v) => {
    try {
      return JSON.stringify(typeof v === "string" ? JSON.parse(v) : v, null, 2);
    } catch (e) {
      return typeof v === "string" ? v : JSON.stringify(v, null, 2), e;
    }
  };

  return (
    <div className="wm-backdrop gen-workout-modal">
      <div className="confirm-modal-container">
        <div style={{ display: 'flex', justifyContent:'space-between', alignItems:'center', marginBottom: 12 }}>
          <h3>Generated Workout #{workout.Generate_ID}</h3>
          <button onClick={onClose} className="btn-cancel">Close</button>
        </div>

        <div className="gw-section">
          <strong>User:</strong> { (workout.UserName ?? `${workout.FirstName || ""} ${workout.LastName || ""}`).trim() || workout.Email }
        </div>

        <div className="gw-section">
          <strong>Goal / Target / Place:</strong>
          <div>{workout.Goal} · {workout.Target_Muscle} · {workout.Workout_Place}</div>
        </div>

        <div className="gw-section">
          <strong>Metrics:</strong>
          <div>BMI: {workout.BMI ?? "-"} | BMR: {workout.BMR ?? "-"} | TDEE: {workout.TDEE ?? "-"}</div>
        </div>

        <div className="gw-section">
          <strong>Workout Result (JSON):</strong>
          <pre className="json-pre">{pretty(workout.Workout_Result)}</pre>
        </div>

        <div className="gw-section">
          <strong>Meal Result (JSON):</strong>
          <pre className="json-pre">{pretty(workout.Meal_Result)}</pre>
        </div>

        <div className="gw-section">
          <strong>Body Condition Result (JSON):</strong>
          <pre className="json-pre">{pretty(workout.Body_Condition_Result)}</pre>
        </div>

        <div className="gw-section">
          <strong>Raw AI Response:</strong>
          <pre className="json-pre">{workout.Raw_AI_Response || "—"}</pre>
        </div>

        <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, marginTop: 12 }}>
          <a className="action-btn" href={`data:text/json;charset=utf-8,${encodeURIComponent(JSON.stringify(workout, null, 2))}`} download={`workout-${workout.Generate_ID}.json`}>Download JSON</a>
        </div>
      </div>
    </div>
  );
}
