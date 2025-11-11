// inside src/pages/user/
import { useEffect, useState } from "react";
import axios from "axios";
import "../../styles/WorkoutHistory.css";

const API_BASE = "/api";

export default function WorkoutHistory() {
  const user = JSON.parse(localStorage.getItem("user"));
  const userId = user?.User_ID;

  const [history, setHistory] = useState([]);
  const [view, setView] = useState(null); // selected workout

  useEffect(() => {
    axios
      .get(`${API_BASE}/?route=workout&action=history&userId=${userId}`)
      .then(res => {
        if (res.data.success) setHistory(res.data.data);
      });
  }, []);

  const openView = async (id) => {
    const res = await axios.get(`${API_BASE}/?route=workout&action=getOne&id=${id}`);
    if (res.data.success) setView(res.data.data);
  };

  const downloadPDF = async (id) => {
    const res = await axios.get(`${API_BASE}/?route=workout&action=getOne&id=${id}`);
    if (!res.data.success) return alert("Cannot download");

    const workout = res.data.data;

    const html = `
      <h2>Workout Report</h2>
      <h4>Created: ${workout.Created_At}</h4>
      <h3>Workout Plan</h3><pre>${workout.Workout_Result}</pre>
      <h3>Meal Plan</h3><pre>${workout.Meal_Result}</pre>
      <h3>Body Condition</h3><pre>${workout.Body_Condition_Result}</pre>
    `;

    const blob = new Blob([html], { type: "text/html" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `Workout-${id}.html`;
    a.click();
  };

  return (
    <div className="page-container">
      <h2>Workout History</h2>

      {history.map(w => (
        <div key={w.Generate_ID} className="history-card">
          <div>
            <strong>{w.Goal}</strong> • {w.Target_Muscle} • {w.Workout_Place}
            <p className="date">{new Date(w.Created_At).toLocaleString()}</p>
          </div>
          <div className="history-actions">
            <button onClick={() => openView(w.Generate_ID)}>View</button>
            <button onClick={() => downloadPDF(w.Generate_ID)}>Download</button>
          </div>
        </div>
      ))}

      {view && (
        <div className="modal-overlay" onClick={() => setView(null)}>
          <div className="modal-box scrollable" onClick={e=>e.stopPropagation()}>
            <h3>Workout Plan</h3>
            <pre>{view.Workout_Result}</pre>
            <h3>Meal Plan</h3>
            <pre>{view.Meal_Result}</pre>
            <h3>Body Condition</h3>
            <pre>{view.Body_Condition_Result}</pre>

            <div className="modal-footer">
              <button onClick={() => downloadPDF(view.Generate_ID)}>Download PDF</button>
              <button className="close" onClick={() => setView(null)}>Close</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
