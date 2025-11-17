import React from "react";
import "../styles/ConfirmModal.css"; // ← uses the same modal styling
import "../styles/admin/GeneratedWorkoutView.css"; // keeps your JSON formatting styles
import PrettyJSON from "./PrettyJSON";

export default function GeneratedWorkoutView({ workout, onClose }) {

  return (
    <div className="wm-backdrop generated-view">
      <div className="wm-modal">

        {/* HEADER */}
        <h3>Generated Workout #{workout.Generate_ID}</h3>

        {/* USER DETAILS */}
        <div className="gw-section">
          <strong>User:</strong> 
          { (workout.UserName ?? `${workout.FirstName || ""} ${workout.LastName || ""}`).trim() || workout.Email }
        </div>

        {/* BASIC WORKOUT DETAILS */}
        <div className="gw-section">
          <strong>Goal / Target / Place:</strong>
          <div>{workout.Goal} · {workout.Target_Muscle} · {workout.Workout_Place}</div>
        </div>

        {/* METRICS */}
        <div className="gw-section">
          <strong>Metrics:</strong>
          <div>BMI: {workout.BMI ?? "-"} | BMR: {workout.BMR ?? "-"} | TDEE: {workout.TDEE ?? "-"}</div>
        </div>

        {/* WORKOUT JSON */}
        <div className="gw-section">
          <strong>Workout Result (JSON):</strong>
          <PrettyJSON label="Workout Result" data={workout.Workout_Result} />
        </div>

        {/* MEAL JSON */}
        <div className="gw-section">
          <strong>Meal Result (JSON):</strong>
          <PrettyJSON label="Meal Result" data={workout.Meal_Result} />
        </div>

        {/* BODY CONDITION JSON */}
        <div className="gw-section">
          <strong>Body Condition Result (JSON):</strong>
          <PrettyJSON label="Body Condition Result" data={workout.Body_Condition_Result} />
        </div>

        {/* RAW AI RESPONSE */}
        <div className="gw-section">
          <strong>Raw AI Response:</strong>
          <PrettyJSON label="Raw AI Response" data={workout.Raw_AI_Response} />
        </div>

        {/* ACTION BUTTONS — SAME STYLE AS CONFIRM MODAL */}
        <div className="wm-actions" style={{ marginTop: 10 }}>
          <button className="wm-btn" onClick={onClose}>Close</button>

          <a
            className="wm-btn primary"
            href={`data:text/json;charset=utf-8,${encodeURIComponent(JSON.stringify(workout, null, 2))}`}
            download={`workout-${workout.Generate_ID}.json`}
          >
            Download JSON
          </a>
        </div>

      </div>
    </div>
  );
}
